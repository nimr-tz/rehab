<?php

namespace App\Http\Controllers;

use App\Models\AbstractSubmission;
use App\Services\ProceedingsCorrectionService;
use App\Support\AbstractBodyFormatter;
use App\Support\TitleFormatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Camera-ready corrections: lets an author fix what goes into the conference
 * proceedings, without reopening the submission form.
 *
 * Admins use the same form to correct any entry on an author's behalf. They are
 * not bound by the correction window — they are needed most after it closes,
 * for the authors who never got round to it.
 */
class ProceedingsCorrectionController extends Controller
{
    public function __construct(
        private readonly ProceedingsCorrectionService $corrections
    ) {}

    /**
     * The correction form for one accepted abstract.
     */
    public function edit(AbstractSubmission $abstract)
    {
        $asAdmin = $this->corrections->canAdminister(Auth::user());

        if ($blocked = $this->blocked($abstract, $asAdmin)) {
            return $blocked;
        }

        return view('abstracts.proceedings-correction', [
            'abstract' => $abstract->loadMissing('user'),
            'current' => $this->corrections->snapshot($abstract),
            'closesAt' => $this->corrections->closesAt(),
            'sectionLabels' => AbstractBodyFormatter::recognisedLabels(),
            'asAdmin' => $asAdmin,
            // Admins return to the filtered, paginated list they came from.
            'backUrl' => $asAdmin
                ? $this->entriesUrl(url()->previous())
                : route('abstracts.show', $abstract),
        ]);
    }

    /**
     * Save a correction. Status stays 'accepted' and the conference code is
     * never touched — see ProceedingsCorrectionService for why.
     */
    public function update(Request $request, AbstractSubmission $abstract)
    {
        $user = Auth::user();
        $asAdmin = $this->corrections->canAdminister($user);

        if ($blocked = $this->blocked($abstract, $asAdmin)) {
            return $blocked;
        }

        $validated = $request->validate($this->corrections->validationRules());

        $changed = $this->corrections->apply($abstract, $validated, $user);

        $done = $asAdmin
            ? redirect()->to($this->entriesUrl($request->input('return_to')))
            : redirect()->route('abstracts.show', $abstract);

        if (empty($changed)) {
            return $done->with('success', 'No changes were needed — the entry is unchanged.');
        }

        Log::info('Proceedings correction saved', [
            'abstract_id' => $abstract->id,
            'conference_code' => $abstract->conference_code,
            'user_id' => $user->id,
            'on_behalf_of_author' => $user->id !== $abstract->user_id,
            'changed_fields' => $changed,
        ]);

        return $done->with('success', $asAdmin
            ? "Entry {$abstract->conference_code} updated. It will appear in the next build of the conference proceedings."
            : 'Your proceedings entry has been updated. It will appear in the next build of the conference proceedings.');
    }

    /**
     * Render the entry exactly as the proceedings volume will typeset it.
     *
     * Shares App\Support\AbstractBodyFormatter with the volume itself, so what
     * is shown here is what gets printed — including how the IMRaD section
     * parser interprets the headings.
     */
    public function preview(Request $request, AbstractSubmission $abstract)
    {
        $user = Auth::user();

        if ($abstract->user_id !== $user->id && ! $this->corrections->canAdminister($user)) {
            abort(403);
        }

        $validated = $request->validate($this->corrections->validationRules());
        $payload = $this->corrections->normalizePayload($validated);

        // Affiliation superscripts are numbered the same way as the book:
        // main author first, then each distinct co-author institute in order.
        $affiliations = [];
        $next = 1;

        $mainAffiliation = TitleFormatter::affiliation($payload['author_institute'] ?? '');
        if ($mainAffiliation !== '') {
            $affiliations[$mainAffiliation] = $next++;
        }

        $coauthors = $payload['coauthors'] ?? [];
        foreach ($coauthors as $coauthor) {
            $affiliation = TitleFormatter::affiliation($coauthor['institute'] ?? '');
            if ($affiliation !== '' && ! isset($affiliations[$affiliation])) {
                $affiliations[$affiliation] = $next++;
            }
        }

        return response()->json([
            'conference_code' => $abstract->conference_code,
            'title' => TitleFormatter::sentenceCase($payload['title'] ?? ''),
            'authors' => $this->authorLine($payload, $affiliations),
            'affiliations' => collect($affiliations)
                ->map(fn ($id, $name) => ['id' => $id, 'name' => TitleFormatter::tidyAffiliation($name)])
                ->sortBy('id')
                ->values(),
            'body_html' => AbstractBodyFormatter::toHtml(
                AbstractBodyFormatter::plainText($payload['description'] ?? '')
            ),
            'keywords' => TitleFormatter::keywords($payload['keywords'] ?? ''),
        ]);
    }

    /**
     * Why this request may not proceed, or null if it may.
     *
     * Authors may only touch their own entries, and only while the window is
     * open. Admins may touch any eligible entry at any time.
     */
    private function blocked(AbstractSubmission $abstract, bool $asAdmin)
    {
        if (! $asAdmin && $abstract->user_id !== Auth::id()) {
            abort(403, 'You can only edit your own abstracts.');
        }

        if (! $this->corrections->isEligible($abstract)) {
            $message = 'Proceedings corrections are only available for accepted abstracts that have been assigned a conference code.';

            return $asAdmin
                ? redirect()->route('admin.proceedings.entries')->with('error', $message)
                : redirect()->route('abstracts.show', $abstract)->with('error', $message);
        }

        if (! $asAdmin && ! $this->corrections->isOpen()) {
            return redirect()->route('abstracts.show', $abstract)
                ->with('error', $this->corrections->closedMessage());
        }

        return null;
    }

    /**
     * The admin entries list, keeping the given URL's filters and page if it
     * points at that list. Anything else — including an off-site URL — falls
     * back to the bare list, so this cannot be used as an open redirect.
     */
    private function entriesUrl(?string $candidate): string
    {
        $list = route('admin.proceedings.entries');

        return is_string($candidate) && ($candidate === $list || str_starts_with($candidate, $list.'?'))
            ? $candidate
            : $list;
    }

    /**
     * "Name¹, Co-author², …" the way the book renders the author line.
     */
    private function authorLine(array $payload, array $affiliations): string
    {
        $superscript = function (string $institute) use ($affiliations): string {
            $affiliation = TitleFormatter::affiliation($institute);

            return ($affiliation !== '' && isset($affiliations[$affiliation]))
                ? '<sup>'.$affiliations[$affiliation].'</sup>'
                : '';
        };

        $line = '<strong>'.e(TitleFormatter::personName($payload['author_name'] ?? '')).'</strong>'
            .$superscript($payload['author_institute'] ?? '');

        foreach ($payload['coauthors'] ?? [] as $coauthor) {
            $name = trim((string) ($coauthor['name'] ?? ''));
            if ($name === '') {
                continue;
            }

            $line .= ', '.e(TitleFormatter::personName($name)).$superscript($coauthor['institute'] ?? '');
        }

        return $line;
    }
}
