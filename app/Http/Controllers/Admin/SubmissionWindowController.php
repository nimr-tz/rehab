<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SubmissionWindowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubmissionWindowController extends Controller
{
    public function show(SubmissionWindowService $submissionWindowService)
    {
        return view('admin.submission-window.show', [
            'status' => $submissionWindowService->status(),
        ]);
    }

    public function open(Request $request, SubmissionWindowService $submissionWindowService)
    {
        $validated = $request->validate([
            'duration_value' => 'nullable|integer|min:1',
            'duration_unit' => 'nullable|in:days',
            'preset' => 'nullable|string',
        ]);

        if (!empty($validated['preset'])) {
            [$presetValue, $presetUnit] = array_pad(explode(':', $validated['preset'], 2), 2, null);
            $validated['duration_value'] = is_numeric($presetValue) ? (int) $presetValue : null;
            $validated['duration_unit'] = $presetUnit;
        }

        if (empty($validated['duration_value']) || empty($validated['duration_unit'])) {
            return redirect()
                ->back()
                ->withErrors([
                    'duration_value' => 'Please choose how long the override should stay open.',
                ])
                ->withInput();
        }

        $limits = [
            'days' => 3,
        ];

        $value = (int) $validated['duration_value'];
        $unit = $validated['duration_unit'];

        if ($value > $limits[$unit]) {
            return redirect()
                ->back()
                ->withErrors([
                    'duration_value' => match ($unit) {
                        'days' => 'Days can be between 1 and 3.',
                    },
                ])
                ->withInput();
        }

        if ($unit !== 'days') {
            return redirect()
                ->back()
                ->withErrors([
                    'duration_value' => 'Submission overrides now use days only.',
                ])
                ->withInput();
        }

        if (!$submissionWindowService->supportsOverrides()) {
            return redirect()->back()->with('error', 'Submission override storage is not available yet. Please run the latest migrations first.');
        }

        $minutes = $value * 1440;

        $setting = $submissionWindowService->openOverride($minutes, Auth::id());

        $durationLabel = $value . ' ' . rtrim($unit, 's') . ($value === 1 ? '' : 's');

        return redirect()
            ->route('admin.submission-window.show')
            ->with('success', 'Submission window opened for ' . $durationLabel . ' until ' . optional($setting?->override_until)->format('F d, Y H:i') . ' EAT.');
    }

    public function close(SubmissionWindowService $submissionWindowService)
    {
        if (!$submissionWindowService->supportsOverrides()) {
            return redirect()->back()->with('error', 'Submission override storage is not available yet. Please run the latest migrations first.');
        }

        $submissionWindowService->closeOverride(Auth::id());

        return redirect()
            ->route('admin.submission-window.show')
            ->with('success', 'Submission override window closed.');
    }
}
