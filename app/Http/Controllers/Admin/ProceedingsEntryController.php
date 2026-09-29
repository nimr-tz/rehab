<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use Illuminate\Http\Request;

/**
 * Every entry bound for the conference proceedings, so admins can find and
 * correct any of them on an author's behalf.
 */
class ProceedingsEntryController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:200',
            'inclusion' => 'nullable|in:included,excluded',
            'corrected' => 'nullable|in:yes,no',
        ]);

        $base = AbstractSubmission::query()
            ->where('status', 'accepted')
            ->whereNotNull('conference_code');

        $entries = (clone $base)
            ->with(['user:id,first_name,last_name,email', 'proceedingsCorrectedBy:id,first_name,last_name'])
            ->when($filters['q'] ?? null, function ($query, $term) {
                $like = '%'.trim($term).'%';

                $query->where(function ($q) use ($like) {
                    $q->where('conference_code', 'like', $like)
                        ->orWhere('title', 'like', $like)
                        ->orWhere('author_name', 'like', $like)
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', $like));
                });
            })
            ->when(($filters['inclusion'] ?? null) === 'included', fn ($q) => $q->where('include_in_proceedings', true))
            ->when(($filters['inclusion'] ?? null) === 'excluded', fn ($q) => $q->where('include_in_proceedings', false))
            ->when(($filters['corrected'] ?? null) === 'yes', fn ($q) => $q->whereNotNull('proceedings_corrected_at'))
            ->when(($filters['corrected'] ?? null) === 'no', fn ($q) => $q->whereNull('proceedings_corrected_at'))
            ->orderBy('conference_code')
            ->paginate(25)
            ->withQueryString();

        $counts = [
            'total' => (clone $base)->count(),
            'included' => (clone $base)->where('include_in_proceedings', true)->count(),
            'corrected' => (clone $base)->whereNotNull('proceedings_corrected_at')->count(),
        ];

        return view('admin.proceedings.entries', compact('entries', 'filters', 'counts'));
    }
}
