<?php

namespace App\Http\Controllers;

use App\Models\InvitationLetter;
use App\Models\AbstractSubmission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class InvitationController extends Controller
{
    /**
     * Show the invitation letter request form/status.
     */
    public function index()
    {
        $user = Auth::user();
        $invitation = InvitationLetter::where('user_id', $user->id)->first();
        $abstracts = AbstractSubmission::where('user_id', $user->id)
            ->latest()
            ->get();
        $acceptedAbstracts = $abstracts->whereIn('status', ['accepted', 'approved']);

        return view('user.invitation', compact('user', 'invitation', 'abstracts', 'acceptedAbstracts'));
    }

    /**
     * Store a new invitation letter request.
     */
    public function store(Request $request)
    {
        return back()->with('error', 'The invitation portal is currently closed. Please check back later.');
    }

    /**
     * Download the invitation letter as PDF.
     */
    public function download(Request $request)
    {
        $user = Auth::user();
        $invitation = InvitationLetter::where('user_id', $user->id)->first();

        if (!$invitation) {
            $invitation = new InvitationLetter([
                'user_id' => $user->id,
                'title' => $user->title,
                'passport_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                'nationality' => $user->country,
                'institute' => $user->institute ?: $user->affiliation,
                'status' => 'approved',
            ]);
        }

        $letterType = $request->query('type', 'participant');
        $abstract = null;

        if ($letterType === 'presenter') {
            $abstractId = $request->query('abstract_id');

            if (!$abstractId) {
                return back()->with('error', 'Please select an accepted abstract before downloading a presenter invitation letter.');
            }

            $abstract = AbstractSubmission::where('user_id', $user->id)
                ->whereIn('status', ['accepted', 'approved'])
                ->where('id', $abstractId)
                ->first();

            if (!$abstract) {
                return back()->with('error', 'Presenter invitation letters are only available for accepted abstracts.');
            }
        }

        if ($invitation->exists) {
            $invitation->forceFill([
                'downloaded_at' => now(),
                'download_count' => ((int) $invitation->download_count) + 1,
            ])->save();
        }

        $filenameParts = [
            config('conference.file_prefix'),
            $abstract ? 'presenter-invitation-letter' : 'participant-invitation-letter',
            (string) str($invitation->passport_name ?: $user->full_name)->slug(),
        ];

        if ($abstract?->conference_code) {
            $filenameParts[] = (string) str($abstract->conference_code)->slug();
        }

        $filename = implode('-', array_filter($filenameParts)) . '.pdf';

        return Pdf::loadView('pdf.invitation-letter', [
            'letter' => $invitation,
            'abstract' => $abstract,
            'user' => $user,
        ])->setPaper('a4')->download($filename);
    }
}
