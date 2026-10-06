<?php

namespace App\Http\Controllers\Awards;

use App\Http\Controllers\Controller;
use App\Models\AwardCategory;
use App\Models\AwardEntry;
use App\Services\AwardService;
use App\Services\DocumentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/** The committee's shortlists, recipients, choice of winners and announcement. */
class EntryController extends Controller
{
    /** Shortlists abstracts for a presentation award, or names a recipient for an honour. */
    public function store(Request $request, AwardCategory $category, AwardService $awards): RedirectResponse
    {
        try {
            if ($category->isPresentation()) {
                $data = $request->validate(['abstracts' => ['required', 'array'], 'abstracts.*' => ['integer']], [
                    'abstracts.required' => 'Tick at least one abstract to shortlist.',
                ]);
                $count = $awards->shortlist($category, $data['abstracts']);

                return back()->with('status', $count.' '.str('finalist')->plural($count).' added to the shortlist.');
            }

            $data = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'institution' => ['nullable', 'string', 'max:160'],
                'email' => ['nullable', 'email', 'max:160'],
                'citation' => ['required', 'string', 'max:2000'],
            ]);
            $awards->addRecipient($category, $data);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['entries' => $e->getMessage()]);
        }

        return back()->with('status', $data['name'].' added. Give them a place below, then announce.');
    }

    public function destroy(AwardCategory $category, AwardEntry $entry, AwardService $awards): RedirectResponse
    {
        abort_unless($entry->award_category_id === $category->id, 404);

        try {
            $awards->removeEntry($entry);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['entries' => $e->getMessage()]);
        }

        return back()->with('status', $entry->name.' removed from the '.$category->name.'.');
    }

    public function places(Request $request, AwardCategory $category, AwardService $awards): RedirectResponse
    {
        $data = $request->validate(['places' => ['array'], 'places.*' => ['nullable', 'integer']]);

        try {
            $awards->choosePlaces($category, $data['places'] ?? []);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['places' => $e->getMessage()]);
        }

        return back()->with('status', 'Winners saved. Announce them when the committee is ready.');
    }

    public function announce(AwardCategory $category, AwardService $awards): RedirectResponse
    {
        try {
            $awards->announce($category);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['places' => $e->getMessage()]);
        }

        return back()->with('status', 'The winners of the '.$category->name.' are announced on the public awards page, and each winner has been emailed.');
    }

    public function withdraw(AwardCategory $category, AwardService $awards): RedirectResponse
    {
        $awards->withdrawAnnouncement($category);

        return back()->with('status', 'The announcement is withdrawn and the winners are hidden from the public page. Make your changes, then announce again.');
    }

    /** Certificates for every winner, including recipients without a portal account. */
    public function certificate(AwardCategory $category, AwardEntry $entry, DocumentService $documents): Response
    {
        abort_unless($entry->award_category_id === $category->id && $entry->isWinner(), 404);

        return $documents->awardCertificate($entry);
    }
}
