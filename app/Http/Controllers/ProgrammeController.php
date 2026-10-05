<?php

namespace App\Http\Controllers;

use App\Support\Summit;
use Illuminate\View\View;

class ProgrammeController extends Controller
{
    public function __invoke(Summit $summit): View
    {
        $edition = $summit->edition();
        $sessions = $edition
            ? $edition->sessions()->with(['topic', 'abstracts.authors', 'abstracts.topic'])->get()
            : collect();

        return view('programme', [
            'days' => $sessions->groupBy(fn ($session) => $session->starts_at->toDateString()),
        ]);
    }
}
