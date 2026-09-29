<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DuplicateDetectionService;

class DuplicateWatchlistController extends Controller
{
    public function __construct(private DuplicateDetectionService $duplicateDetectionService)
    {
    }

    public function index()
    {
        $accountFlags = $this->duplicateDetectionService->detectDuplicateAccounts();
        $abstractFlags = $this->duplicateDetectionService->detectDuplicateAbstracts();

        $summary = [
            'account_pairs'    => $accountFlags->count(),
            'abstract_pairs'   => $abstractFlags->count(),
            'high_confidence'  => $accountFlags->where('confidence', 'High')->count() + $abstractFlags->where('confidence', 'High')->count(),
            'medium_confidence' => $accountFlags->where('confidence', 'Medium')->count() + $abstractFlags->where('confidence', 'Medium')->count(),
        ];

        return view('admin.duplicates.index', compact('accountFlags', 'abstractFlags', 'summary'));
    }
}
