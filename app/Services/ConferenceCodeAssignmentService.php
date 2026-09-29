<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConferenceCodeAssignmentService
{
    public function __construct(
        private readonly SessionTopicDetectionService $topicDetector
    ) {}

    /**
     * Called automatically when an abstract is accepted.
     * Detects the code prefix for this abstract then resyncs all abstracts
     * sharing the same prefix so ordering stays consistent.
     */
    public function onAbstractAccepted(AbstractSubmission $abstract): void
    {
        $prefix = $this->resolvePrefix($abstract);

        if ($prefix) {
            $this->resyncPrefix($prefix);
        } else {
            $this->assignFallbackCode($abstract);
        }
    }

    /**
     * Resync every known code prefix. Used by admin "resync all" actions.
     */
    public function resyncAll(): int
    {
        // Clear all non-final codes to TMP first so no stale code from a
        // previous prefix blocks assignment when an abstract changes prefix.
        $nonFinal = AbstractSubmission::where('status', 'accepted')
            ->where('is_invited', false)
            ->where('code_is_final', false)
            ->whereNotNull('conference_code')
            ->get();

        DB::transaction(function () use ($nonFinal) {
            foreach ($nonFinal as $abstract) {
                $abstract->forceFill([
                    'conference_code' => 'TMP-' . $abstract->id . '-' . substr(md5((string) $abstract->id), 0, 4),
                ])->save();
            }
        });

        $codes = array_keys(SessionTopicDetectionService::codes());
        $total = 0;

        foreach ($codes as $prefix) {
            $total += $this->resyncPrefix($prefix);
        }

        // Assign fallback codes to any abstract still on a TMP code
        // (topic not in the configured list).
        AbstractSubmission::where('status', 'accepted')
            ->where('is_invited', false)
            ->where('code_is_final', false)
            ->where('conference_code', 'like', 'TMP-%')
            ->get()
            ->each(fn (AbstractSubmission $a) => $this->assignFallbackCode($a));

        return $total;
    }

    /**
     * Finalize codes from actual session placement.
     *
     * Re-issues codes in program order (session date → session start_time →
     * session_order within session), then unplaced abstracts by existing code.
     * Sets code_is_final = true on every abstract touched.
     */
    public function finalizeFromSessionOrder(): int
    {
        $codes = array_keys(SessionTopicDetectionService::codes());
        $total = 0;

        foreach ($codes as $prefix) {
            $all = AbstractSubmission::where('status', 'accepted')
                ->where('is_invited', false)
                ->with('session')
                ->get()
                ->filter(fn (AbstractSubmission $a) => $this->resolvePrefix($a) === $prefix);

            if ($all->isEmpty()) {
                continue;
            }

            $placed = $all->whereNotNull('session_id')->sort(function (AbstractSubmission $a, AbstractSubmission $b) {
                $dateA = $a->session?->schedule_days[0] ?? '9999-12-31';
                $dateB = $b->session?->schedule_days[0] ?? '9999-12-31';
                if ($dateA !== $dateB) {
                    return strcmp($dateA, $dateB);
                }

                $timeA = $a->session?->start_time ?? '23:59';
                $timeB = $b->session?->start_time ?? '23:59';
                if ($timeA !== $timeB) {
                    return strcmp((string) $timeA, (string) $timeB);
                }

                return ($a->session_order ?? 999) <=> ($b->session_order ?? 999);
            })->values();

            $unplaced = $all->whereNull('session_id')->sortBy('conference_code')->values();
            $ordered  = $placed->concat($unplaced);

            $plans   = [];
            $counter = 1;

            foreach ($ordered as $abstract) {
                $plans[] = [
                    'abstract' => $abstract,
                    'new_code' => $prefix . '-' . str_pad($counter++, 3, '0', STR_PAD_LEFT),
                ];
            }

            if (empty($plans)) {
                continue;
            }

            DB::transaction(function () use ($plans) {
                foreach ($plans as $plan) {
                    $plan['abstract']->forceFill([
                        'conference_code' => 'TMP-' . $plan['abstract']->id . '-' . substr(md5((string) $plan['abstract']->id), 0, 4),
                    ])->save();
                }

                foreach ($plans as $plan) {
                    $plan['abstract']->forceFill([
                        'conference_code'  => $plan['new_code'],
                        'code_is_final'    => true,
                        'code_assigned_by' => Auth::id() ?? $plan['abstract']->code_assigned_by,
                        'code_assigned_at' => now(),
                    ])->save();
                }
            });

            $total += count($plans);
        }

        return $total;
    }

    /**
     * Lock a single abstract's code so resync will not change it.
     */
    public function lockCode(AbstractSubmission $abstract): void
    {
        $abstract->forceFill(['code_is_final' => true])->save();
    }

    /**
     * Unlock a single abstract's code so the next resync can reassign it.
     */
    public function unlockCode(AbstractSubmission $abstract): void
    {
        $abstract->forceFill(['code_is_final' => false])->save();
    }

    /**
     * Resync all provisional codes for one prefix (e.g. 'HBR').
     *
     * - Skips invited presenters.
     * - Skips abstracts where code_is_final = true.
     * - Clusters by session_topic, keeps same-author abstracts consecutive.
     * - Two-phase write (TMP → real) avoids unique-constraint conflicts.
     */
    public function resyncPrefix(string $prefix): int
    {
        $knownCodes = SessionTopicDetectionService::codes();

        if (! isset($knownCodes[$prefix])) {
            Log::warning("ConferenceCodeAssignmentService: unknown prefix '{$prefix}'");

            return 0;
        }

        $all = AbstractSubmission::where('status', 'accepted')
            ->where('is_invited', false)
            ->get();

        $abstracts = $all->filter(fn (AbstractSubmission $a) => $this->resolvePrefix($a) === $prefix);

        if ($abstracts->isEmpty()) {
            return 0;
        }

        // Ensure session topics are populated
        foreach ($abstracts as $abstract) {
            if (! $abstract->session_topic || $abstract->session_topic_source === 'auto') {
                $this->topicDetector->syncSuggestedTopic($abstract);
                $abstract->refresh();
            }
        }

        $finalAbstracts       = $abstracts->where('code_is_final', true)->whereNotNull('conference_code');
        $provisionalAbstracts = $abstracts->where('code_is_final', false);

        $takenNumbers = $finalAbstracts
            ->map(function (AbstractSubmission $a) use ($prefix) {
                if (Str::startsWith($a->conference_code, $prefix . '-')) {
                    return (int) Str::afterLast($a->conference_code, '-');
                }

                return null;
            })
            ->filter()
            ->values()
            ->all();

        $sorted  = $this->sortByTopicThenAuthor($provisionalAbstracts);
        $plans   = [];
        $counter = 1;

        foreach ($sorted as $abstract) {
            while (in_array($counter, $takenNumbers, true)) {
                $counter++;
            }

            $newCode = $prefix . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            $counter++;

            if ($abstract->conference_code !== $newCode) {
                $plans[] = ['abstract' => $abstract, 'new_code' => $newCode];
            }
        }

        if (empty($plans)) {
            return 0;
        }

        $this->applyPlans($plans);

        return count($plans);
    }

    // ─── Prefix Resolution ───────────────────────────────────────────────────────

    /**
     * Determine which code prefix an abstract should get.
     * Uses the abstract's topic (see SessionTopicDetectionService).
     * Falls back to null if nothing matches (gets the fallback code).
     */
    public function resolvePrefix(AbstractSubmission $abstract): ?string
    {
        // If code is final, derive prefix from the existing code
        if ($abstract->code_is_final && $abstract->conference_code) {
            $parts = explode('-', $abstract->conference_code);
            if (count($parts) >= 2) {
                return $parts[0];
            }
        }

        return $this->topicDetector->detectCode($abstract);
    }

    // ─── Sorting ─────────────────────────────────────────────────────────────────

    private function sortByTopicThenAuthor(Collection $abstracts): Collection
    {
        $withTopic    = $abstracts->whereNotNull('session_topic');
        $withoutTopic = $abstracts->whereNull('session_topic');

        $byTopic = $withTopic
            ->groupBy('session_topic')
            ->sortByDesc(fn (Collection $group) => $group->count());

        $sorted = collect();

        foreach ($byTopic as $group) {
            $sorted = $sorted->concat($this->sortGroupByAuthor($group));
        }

        if ($withoutTopic->isNotEmpty()) {
            $sorted = $sorted->concat($this->sortGroupByAuthor($withoutTopic));
        }

        return $sorted;
    }

    private function sortGroupByAuthor(Collection $group): Collection
    {
        return $group->sort(function (AbstractSubmission $a, AbstractSubmission $b) {
            $cmp = strcmp(
                mb_strtolower(trim($a->author_name)),
                mb_strtolower(trim($b->author_name))
            );

            if ($cmp !== 0) {
                return $cmp;
            }

            return ($a->created_at?->timestamp ?? 0) <=> ($b->created_at?->timestamp ?? 0);
        })->values();
    }

    // ─── Write ───────────────────────────────────────────────────────────────────

    private function applyPlans(array $plans): void
    {
        $assignedBy = Auth::id();

        DB::transaction(function () use ($plans, $assignedBy) {
            foreach ($plans as $plan) {
                $plan['abstract']->forceFill([
                    'conference_code' => 'TMP-' . $plan['abstract']->id . '-' . substr(md5((string) $plan['abstract']->id), 0, 4),
                ])->save();
            }

            foreach ($plans as $plan) {
                $plan['abstract']->forceFill([
                    'conference_code'  => $plan['new_code'],
                    'code_assigned_by' => $assignedBy ?? $plan['abstract']->code_assigned_by,
                    'code_assigned_at' => now(),
                ])->save();
            }
        });
    }

    private function assignFallbackCode(AbstractSubmission $abstract): void
    {
        if ($abstract->conference_code) {
            return;
        }

        $last = AbstractSubmission::where('conference_code', 'like', config('conference.code') . '-%')
            ->orderByRaw("CAST(SUBSTRING_INDEX(conference_code, '-', -1) AS UNSIGNED) DESC")
            ->value('conference_code');

        $next = $last ? ((int) Str::afterLast($last, '-') + 1) : 1;

        $abstract->forceFill([
            'conference_code'  => config('conference.code') . '-' . str_pad($next, 3, '0', STR_PAD_LEFT),
            'code_assigned_by' => Auth::id(),
            'code_assigned_at' => now(),
        ])->save();

        Log::warning(
            "ConferenceCodeAssignmentService: abstract #{$abstract->id} " .
            "title '{$abstract->title}' did not match any known code prefix — assigned fallback code."
        );
    }
}
