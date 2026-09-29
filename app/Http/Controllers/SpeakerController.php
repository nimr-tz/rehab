<?php

namespace App\Http\Controllers;

use App\Models\Speaker;
use App\Models\AbstractSubmission;
use Illuminate\Http\Request;

class SpeakerController extends Controller
{
    /**
     * Display the public speakers page.
     */
    public function index(Request $request)
    {
        // Get invited speakers
        $invitedSpeakers = Speaker::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        // Get accepted abstract presenters
        $presenters = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('author_name')
            ->get()
            ->unique('author_name');

        return view('speakers.index', compact('invitedSpeakers', 'presenters'));
    }

}

