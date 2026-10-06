<?php

namespace App\Http\Controllers;

use App\Enums\AwardKind;
use App\Models\AwardCategory;
use App\Models\AwardEntry;
use App\Models\Edition;
use App\Services\AwardService;
use App\Services\DocumentService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * The public awards page, and each person's own awards, nominations and
 * certificates in the portal.
 */
class AwardController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        // The current summit, and earlier summits that announced winners.
        $editions = Edition::query()
            ->where(fn ($q) => $q->where('is_current', true)->orWhereHas('awardCategories', fn ($q) => $q->announced()))
            ->orderByDesc('year')
            ->get();

        $edition = $request->filled('year')
            ? $editions->firstWhere('year', (int) $request->query('year')) ?? abort(404)
            : $summit->edition();

        return view('awards.index', [
            'edition' => $edition,
            'editions' => $editions,
            'categories' => $edition ? $edition->awardCategories()->with('winners.abstract')->get() : collect(),
        ]);
    }

    public function mine(Request $request, Summit $summit): View
    {
        $user = $request->user();
        $edition = $summit->edition();

        // Shortlisted presentations, and honours once announced. Nominations stay confidential.
        $entries = AwardEntry::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhereHas('abstract', fn ($q) => $q->where('user_id', $user->id)))
            ->whereHas('category', fn ($q) => $q->where(fn ($q) => $q->where('kind', AwardKind::Presentation)->orWhereNotNull('announced_at')))
            ->with('category.edition', 'abstract')
            ->latest('id')
            ->get()
            ->filter(fn (AwardEntry $entry) => $entry->category->isPresentation() || $entry->isWinner())
            ->values();

        $open = $edition ? $edition->awardCategories()->get()->filter->acceptsNominations()->values() : collect();

        return view('awards.mine', [
            'edition' => $edition,
            'entries' => $entries,
            'open' => $open,
            'nominations' => $edition
                ? AwardEntry::where('nominated_by', $user->id)
                    ->whereHas('category', fn ($q) => $q->where('edition_id', $edition->id))
                    ->with('category')->latest('id')->get()
                : collect(),
        ]);
    }

    public function nominate(Request $request, AwardService $awards, Summit $summit): RedirectResponse
    {
        $data = $request->validate([
            'award_category_id' => ['required', Rule::exists('award_categories', 'id')->where('edition_id', $summit->edition()?->id)],
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email', 'max:160'],
            'citation' => ['required', 'string', 'min:40', 'max:2000'],
        ], [
            'award_category_id.required' => 'Choose the award.',
            'award_category_id.exists' => 'Choose one of this summit’s awards.',
            'citation.min' => 'Tell the committee a little more: at least a couple of sentences on why they deserve the award.',
        ]);

        $category = AwardCategory::findOrFail($data['award_category_id']);

        try {
            $awards->nominate($category, $request->user(), $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['name' => $e->getMessage()]);
        }

        return redirect()->route('awards.mine')->with('status', 'Thank you. Your nomination of '.$data['name'].' for the '.$category->name.' has been sent to the committee.');
    }

    public function certificate(Request $request, AwardEntry $entry, DocumentService $documents): Response
    {
        abort_unless($entry->belongsToUser($request->user()) && $entry->isWinner(), 404);

        return $documents->awardCertificate($entry);
    }
}
