<?php

namespace App\Http\Controllers\Scientific;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class ReviewerController extends Controller
{
    public function __invoke(): View
    {
        $reviewers = User::role('reviewer')
            ->withCount([
                'reviewAssignments as assigned_count',
                'reviewAssignments as completed_count' => fn ($q) => $q->whereNotNull('completed_at'),
            ])
            ->orderBy('last_name')
            ->get();

        return view('scientific.reviewers', ['reviewers' => $reviewers]);
    }
}
