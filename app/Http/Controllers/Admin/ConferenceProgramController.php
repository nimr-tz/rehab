<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\DetectSessionTopics;
use App\Jobs\GenerateAbstractBookPdf;
use App\Models\AbstractSubmission;
use App\Models\ConferenceSession;
use App\Services\AbstractBookGenerationService;
use App\Services\ConferenceCodeSyncService;
use App\Services\EmailNotificationService;
use App\Services\PresentationDownloadService;
use App\Services\SessionTopicDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Pagination\LengthAwarePaginator;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Str;

class ConferenceProgramController extends Controller
{
    protected $emailService;

    public function __construct(\App\Services\EmailNotificationService $emailService)
    {
        $this->emailService = $emailService;
    }
    /**
     * Display conference program overview
     */
    public function index(AbstractBookGenerationService $abstractBook)
    {
        $stats = [
            'total_abstracts' => AbstractSubmission::count(),
            'accepted_abstracts' => AbstractSubmission::where('status', 'accepted')->count(),
            'assigned_codes' => AbstractSubmission::where('status', 'accepted')
                ->whereNotNull('conference_code')
                ->count(),
            'pending_codes' => AbstractSubmission::where('status', 'accepted')
                ->whereNull('conference_code')
                ->count(),
            'total_sessions' => ConferenceSession::count(),
        ];

        // Get recent activity for code assignments
        $recentActivity = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->whereNotNull('code_assigned_at')
            ->with(['user'])
            ->orderBy('code_assigned_at', 'desc')
            ->limit(5)
            ->get();

        $abstractBookStatus = $abstractBook->status();

        // Camera-ready corrections: how many book-bound entries authors have
        // changed since the last generation run, so a regeneration is only run
        // when it would actually change the book.
        $corrections = app(\App\Services\ProceedingsCorrectionService::class);
        $lastGeneratedAt = !empty($abstractBookStatus['completed_at'])
            ? \Illuminate\Support\Carbon::parse($abstractBookStatus['completed_at'])
            : null;

        $proceedingsCorrections = [
            'is_open' => $corrections->isOpen(),
            'closes_at' => $corrections->closesAt(),
            'changed_since_last_build' => $corrections->correctedSince($lastGeneratedAt),
        ];

        // Conference proceedings (Word) — a separate volume from the abstract
        // book, containing only abstracts whose author opted in.
        $proceedingsStats = app(\App\Services\ConferenceProceedingsService::class)->stats();

        return view('admin.conference-program.index', compact('stats', 'recentActivity', 'abstractBookStatus', 'proceedingsCorrections', 'proceedingsStats'));
    }

    /**
     * Display abstracts with assigned codes
     */
    public function assignedCodes(Request $request)
    {
        $query = $this->buildAssignedCodesQuery($request, false);
        $status = $request->get('assignment_status', 'all');
        $abstractCollection = $query->get()->map(function (AbstractSubmission $abstract) {
            $abstract->normalized_subtheme = $abstract->normalized_subtheme;
            return $abstract;
        });

        if ($request->filled('subtheme')) {
            $selectedSubtheme = trim((string) $request->subtheme);
            $abstractCollection = $abstractCollection->filter(function (AbstractSubmission $abstract) use ($selectedSubtheme) {
                return (string) $abstract->normalized_subtheme === $selectedSubtheme;
            })->values();
        }

        $sortedCollection = $abstractCollection
            ->sortBy(function (AbstractSubmission $abstract) {
                return sprintf(
                    '%s-%s',
                    $abstract->conference_code === null ? '1' : '0',
                    strtoupper((string) ($abstract->conference_code ?? 'ZZZZZZ'))
                );
            })
            ->values();

        if ($request->boolean('export_csv')) {
            return $this->streamAssignedCodesCsv($sortedCollection);
        }

        $perPage = 50;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $abstracts = new LengthAwarePaginator(
            $sortedCollection->forPage($currentPage, $perPage)->values(),
            $sortedCollection->count(),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );

        $filteredTotal = $abstractCollection->count();
        $filteredAssigned = $abstractCollection->whereNotNull('conference_code')->count();
        $filteredPending = $abstractCollection->whereNull('conference_code')->count();
        $filteredOral = $abstractCollection->filter(function (AbstractSubmission $abstract) {
            return strtolower(trim((string) $abstract->presentation_mode)) === 'oral';
        })->count();
        $filteredPoster = $abstractCollection->filter(function (AbstractSubmission $abstract) {
            return strtolower(trim((string) $abstract->presentation_mode)) === 'poster';
        })->count();
        $filteredAudioPoster = $abstractCollection->filter(function (AbstractSubmission $abstract) {
            return str_replace('_', ' ', strtolower(trim((string) $abstract->presentation_mode))) === 'audio poster';
        })->count();

        $filteredSubthemeCounts = $abstractCollection
            ->groupBy(fn (AbstractSubmission $abstract) => $abstract->normalized_subtheme ?: 'Unspecified Subtheme')
            ->map(fn ($group) => $group->count())
            ->sortDesc();

        // Get stats
        $totalAccepted = AbstractSubmission::where('status', 'accepted')->count();
        $totalAssigned = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->count();
        $totalPending = AbstractSubmission::where('status', 'accepted')
            ->whereNull('conference_code')
            ->count();

        $selectedCount = AbstractSubmission::where('status', 'accepted')
            ->where('committee_selected', true)
            ->count();

        $subthemes = collect(AbstractSubmission::canonicalSubthemeNames());
        $allSubthemes = collect(AbstractSubmission::canonicalSubthemeNames())->values();

        $topicStats = [
            'with_topic' => AbstractSubmission::where('status', 'accepted')->whereNotNull('session_topic')->count(),
            'without_topic' => AbstractSubmission::where('status', 'accepted')->whereNull('session_topic')->count(),
        ];

        $subthemeRecommendationCount = AbstractSubmission::where('status', 'accepted')
            ->whereHas('reviews', function ($query) {
                $query->where('status', 'submitted')
                    ->where('subtheme_relevance', 'suggest_change')
                    ->whereNotNull('suggested_subtheme')
                    ->whereRaw("TRIM(suggested_subtheme) <> ''");
            })
            ->count();

        $genericCodeCount = AbstractSubmission::where('status', 'accepted')
            ->where('conference_code', 'like', config('conference.code') . '-%')
            ->count();

        return view('admin.conference-program.assigned', compact(
            'abstracts',
            'totalAccepted',
            'totalAssigned',
            'totalPending',
            'filteredTotal',
            'filteredAssigned',
            'filteredPending',
            'filteredOral',
            'filteredPoster',
            'filteredAudioPoster',
            'filteredSubthemeCounts',
            'selectedCount',
            'subthemes',
            'allSubthemes',
            'status',
            'topicStats',
            'subthemeRecommendationCount',
            'genericCodeCount'
        ));
    }

    public function detectSessionTopics(Request $request)
    {
        $abstractIds = $this->buildAssignedCodesQuery($request)->pluck('id')->all();

        DetectSessionTopics::dispatch($abstractIds);

        return redirect()->back()->with('success',
            'Topic detection and code resync queued for ' . count($abstractIds) . ' abstracts. Results will apply within a minute.'
        );
    }

    /**
     * Update assigned conference codes
     */
    public function updateCodes(Request $request)
    {
        $validated = $request->validate([
            'changes' => 'required|array',
            'changes.*.abstract_id' => 'required|exists:abstract_submissions,id',
            'changes.*.conference_code' => 'required|string|max:20',
            'changes.*.subtheme' => 'required|string|max:255',
            'changes.*.presentation_mode' => 'required|in:Oral,Poster,Audio Poster',
            'changes.*.committee_selected' => 'boolean',
            'changes.*.session_topic' => 'nullable|string|max:120',
        ]);

        $updated = 0;
        $errors = [];
        $duplicates = [];

        foreach ($validated['changes'] as $change) {
            try {
                $abstract = AbstractSubmission::find($change['abstract_id']);
                $originalSubtheme = $abstract->subtheme;
                $originalTopic = trim((string) $abstract->session_topic);
                $incomingTopic = trim((string) ($change['session_topic'] ?? ''));
                $conferenceCode = $this->normalizeConferenceCode($change['conference_code']);

                if (!$conferenceCode) {
                    throw new \InvalidArgumentException('Conference code cannot be empty.');
                }

                if ($duplicate = $this->findDuplicateConferenceCode($conferenceCode, $abstract->id)) {
                    throw new \InvalidArgumentException("Conference code {$conferenceCode} is already used by abstract #{$duplicate->id}.");
                }

                $codeChanged = $abstract->conference_code !== $conferenceCode;

                $updateData = [
                    'conference_code' => $conferenceCode,
                    'subtheme' => $change['subtheme'],
                    'presentation_mode' => $change['presentation_mode'],
                    'committee_selected' => $change['committee_selected'] ?? false,
                ];

                if ($codeChanged) {
                    $updateData['code_is_final'] = true;
                    $updateData['code_assigned_by'] = Auth::id();
                    $updateData['code_assigned_at'] = now();
                }

                // Update the abstract
                $abstract->update($updateData);

                $abstract->refresh();
                $topicService = app(SessionTopicDetectionService::class);

                if ($incomingTopic !== $originalTopic) {
                    $topicService->applyManualTopic($abstract, $change['session_topic'] ?? null);
                } elseif ($originalSubtheme !== $change['subtheme']) {
                    $topicService->syncSuggestedTopic($abstract, force: true);
                }

                $this->notifyAuthorIfSubthemeChanged($abstract, $originalSubtheme, $change['subtheme']);

                $updated++;

            } catch (\Exception $e) {
                $errors[] = "Error updating abstract {$change['abstract_id']}: " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some changes could not be saved. ' . implode('; ', $errors)
            ], 422);
        }

        $resynced = app(ConferenceCodeSyncService::class)->resyncAcceptedCodes();

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'message' => "Successfully updated {$updated} conference codes!",
            'resynced' => $resynced,
        ]);
    }

    /**
     * Display abstracts pending code assignment
     */
    public function pendingCodes()
    {
        // Get all pending abstracts (no pagination for bulk operations)
        $abstracts = AbstractSubmission::where('status', 'accepted')
            ->whereNull('conference_code')
            ->with(['user'])
            ->orderBy('subtheme', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $totalPending = $abstracts->count();

        // Get subtheme counts for filter
        $subthemeCounts = $abstracts->groupBy('subtheme')
            ->map(fn($group) => $group->count())
            ->sortKeys();

        // Get all unique subthemes for dropdown
        $allSubthemes = AbstractSubmission::distinct()
            ->pluck('subtheme')
            ->sort()
            ->values();

        return view('admin.conference-program.pending-bulk', compact('abstracts', 'totalPending', 'subthemeCounts', 'allSubthemes'));
    }

    /**
     * Bulk save conference codes
     */
    public function bulkSaveCodes(Request $request)
    {
        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.abstract_id' => 'required|exists:abstract_submissions,id',
            'assignments.*.conference_code' => 'required|string|max:20',
            'assignments.*.committee_selected' => 'boolean',
            'assignments.*.presentation_mode' => 'nullable|in:Oral,Poster',
            'assignments.*.subtheme' => 'nullable|string|max:255',
            'assignments.*.session_topic' => 'nullable|string|max:120',
        ]);

        $saved = 0;
        $errors = [];
        foreach ($validated['assignments'] as $assignment) {
            try {
                $abstract = AbstractSubmission::find($assignment['abstract_id']);
                $originalSubtheme = $abstract->subtheme;
                $originalTopic = trim((string) $abstract->session_topic);
                $conferenceCode = $this->normalizeConferenceCode($assignment['conference_code']);

                if (!$conferenceCode) {
                    throw new \InvalidArgumentException('Conference code cannot be empty.');
                }

                if ($duplicate = $this->findDuplicateConferenceCode($conferenceCode, $abstract->id)) {
                    throw new \InvalidArgumentException("Conference code {$conferenceCode} is already used by abstract #{$duplicate->id}.");
                }

                // Check if abstract is still eligible
                if ($abstract->status !== 'accepted' || $abstract->conference_code) {
                    continue;
                }

                // Prepare update data
                $updateData = [
                    'conference_code' => $conferenceCode,
                    'committee_selected' => $assignment['committee_selected'] ?? false,
                    'code_assigned_by' => Auth::id(),
                    'code_assigned_at' => now(),
                    'code_is_final' => true,
                ];

                // Add presentation mode if provided
                if (isset($assignment['presentation_mode'])) {
                    $updateData['presentation_mode'] = $assignment['presentation_mode'];
                }

                // Add subtheme if provided
                if (isset($assignment['subtheme'])) {
                    $updateData['subtheme'] = $assignment['subtheme'];
                }

                // Update the abstract
                $abstract->update($updateData);

                $abstract->refresh();
                $incomingTopic = trim((string) ($assignment['session_topic'] ?? ''));
                $topicService = app(SessionTopicDetectionService::class);
                if (array_key_exists('session_topic', $assignment) && $incomingTopic !== $originalTopic) {
                    $topicService->applyManualTopic($abstract, $assignment['session_topic']);
                } elseif (($assignment['subtheme'] ?? $originalSubtheme) !== $originalSubtheme && $abstract->session_topic_source !== 'manual') {
                    $topicService->syncSuggestedTopic($abstract);
                }

                $this->notifyAuthorIfSubthemeChanged($abstract, $originalSubtheme, $assignment['subtheme'] ?? $originalSubtheme);

                $saved++;

            } catch (\Exception $e) {
                $errors[] = "Error assigning code to abstract {$assignment['abstract_id']}: " . $e->getMessage();
            }
        }

        if (count($errors) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Some codes could not be saved. ' . implode('; ', $errors)
            ], 422);
        }

        $resynced = app(ConferenceCodeSyncService::class)->resyncAcceptedCodes();

        return response()->json([
            'success' => true,
            'saved' => $saved,
            'message' => "Successfully assigned {$saved} conference codes!",
            'resynced' => $resynced,
        ]);
    }

    /**
     * Display selected abstracts selected by the committee
     */
    public function selectedAbstracts()
    {
        $abstracts = AbstractSubmission::where('status', 'accepted')
            ->where('committee_selected', true)
            ->with(['user', 'session'])
            ->orderBy('conference_code')
            ->paginate(20);

        return view('admin.conference-program.selected', compact('abstracts'));
    }

    /**
     * Display program builder with drag-and-drop
     */
    public function programBuilder()
    {
        // Load all qualifying abstracts (accepted+coded regular, or invited+coded)
        $candidates = AbstractSubmission::where(function ($query) {
            $query->where(function ($q) {
                $q->where('status', 'accepted')
                  ->whereNotNull('conference_code')
                  ->where('is_invited', false);
            })->orWhere(function ($q) {
                $q->where('is_invited', true)
                  ->whereNotNull('conference_code');
            });
        })
        ->with(['user', 'invitedCreatedBy', 'session'])
        ->orderBy('is_invited')
        ->orderBy('conference_code')
        ->get();

        // Sidebar rules: show unassigned abstracts that are either invited
        // presentations or belong to a presenter whose registration is settled.
        $abstracts = $candidates->filter(function ($abstract) {
            // Only show abstracts that have not yet been placed in a session
            if (! is_null($abstract->session_id)) {
                return false;
            }

            if ($abstract->is_invited) {
                return true;
            }

            // Only presenters with a settled registration can be scheduled.
            return $abstract->user?->isPaid() ?? false;
        });

        // Get all sessions with their abstracts.
        // schedule_days is a JSON array so DB orderBy sorts it lexicographically and
        // gives wrong order — sort the collection in PHP instead.
        $sessions = ConferenceSession::with(['abstracts' => function($query) {
            $query->orderBy('session_order', 'asc')
                  ->orderBy('conference_code', 'asc');
        }])
            ->where('is_active', true)
            ->get()
            ->sortBy(function ($session) {
                $days = is_array($session->schedule_days)
                    ? $session->schedule_days
                    : json_decode((string) $session->schedule_days, true);
                $date = !empty($days) ? $days[0] : '9999-12-31';
                try {
                    $time = $session->start_time
                        ? \Carbon\Carbon::parse($session->start_time)->format('H:i')
                        : '23:59';
                } catch (\Throwable $e) {
                    $time = '23:59';
                }
                return $date . ' ' . $time;
            })
            ->values();

        // Get unique subthemes for filter
        $subthemes = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->distinct()
            ->pluck('subtheme')
            ->sort();

        // Get users who can be chairs/rapporteurs (reviewers and admins)
        $potentialChairs = \App\Models\User::whereIn('role', ['admin', 'reviewer'])
            ->orWhereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'reviewer', 'chair']);
            })
            ->select('id', 'first_name', 'last_name', 'email', 'role')
            ->orderBy('first_name')
            ->get();

        return view('admin.conference-program.builder', compact('abstracts', 'sessions', 'subthemes', 'potentialChairs'));
    }

    /**
     * Create a new session
     */
    public function createSession(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:5000',
            'session_type' => 'required|in:presentation,plenary,panel,discussion,break,lunch,poster,opening,closing,networking,meeting,other',
            'date' => 'required|date',
            'presentation_type' => 'nullable|in:oral,poster',
            'speaker' => 'nullable|string|max:255',
            'panelists' => 'nullable|string',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_location' => 'nullable|string|max:255',
            'session_chair_id' => 'nullable|exists:users,id',
            'session_chair_name' => 'nullable|string|max:255',
            'session_rapporteur_id' => 'nullable|exists:users,id',
            'session_rapporteur_name' => 'nullable|string|max:255',
            'max_abstracts' => 'nullable|integer|min:0',
        ]);

        $presentationType = $this->normalizedPresentationType(
            $validated['session_type'],
            $validated['presentation_type'] ?? null
        );
        $capacityFallback = $presentationType === 'poster' ? 50 : 0;
        $maxAbstracts = $validated['max_abstracts'] ?? $this->expectedAbstractCapacityForInput(
            $validated['session_type'],
            $presentationType,
            $validated['start_time'],
            $validated['end_time'],
            $capacityFallback
        );

        // Get user details for chair and rapporteur
        $chairUser = null;
        $rapporteurUser = null;

        if ($validated['session_chair_id'] ?? null) {
            $chairUser = \App\Models\User::find($validated['session_chair_id']);
        }

        if ($validated['session_rapporteur_id'] ?? null) {
            $rapporteurUser = \App\Models\User::find($validated['session_rapporteur_id']);
        }

        $session = ConferenceSession::create([
            'name' => $validated['name'],
            'session_type' => $validated['session_type'],
            'subtheme' => 'General', // Can be updated later
            'presentation_type' => $presentationType,
            'speaker' => $validated['speaker'] ?? null,
            'panelists' => $validated['panelists'] ?? null,
            'description' => $validated['description'] ?? null,
            'schedule_days' => [$validated['date']],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_location' => $validated['room_location'] ?? null,
            'session_chair' => $chairUser ? $chairUser->first_name . ' ' . $chairUser->last_name : ($validated['session_chair_name'] ?? null),
            'session_chair_email' => $chairUser ? $chairUser->email : null,
            'session_chair_id' => $validated['session_chair_id'] ?? null,
            'session_rapporteur' => $rapporteurUser ? $rapporteurUser->first_name . ' ' . $rapporteurUser->last_name : ($validated['session_rapporteur_name'] ?? null),
            'session_rapporteur_email' => $rapporteurUser ? $rapporteurUser->email : null,
            'session_rapporteur_id' => $validated['session_rapporteur_id'] ?? null,
            'max_abstracts' => $maxAbstracts,
            'current_abstracts' => 0,
            'status' => 'draft',
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        // Notifications are now handled manually via the "Notify" button when program is final
        return response()->json([
            'success' => true,
            'message' => 'Session created successfully!',
            'session' => $session
        ]);
    }

    /**
     * Fix session capacity counts
     */
    public function fixCapacities()
    {
        try {
            $sessions = ConferenceSession::all();
            $updated = 0;

            foreach ($sessions as $session) {
                // Update current count based on actual abstracts
                $actualCount = $session->abstracts()->count();

                $maxAbstracts = $this->expectedAbstractCapacity($session);

                if ($session->current_abstracts !== $actualCount || $session->max_abstracts !== $maxAbstracts) {
                    $session->update([
                        'current_abstracts' => $actualCount,
                        'max_abstracts' => $maxAbstracts
                    ]);
                    $updated++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully synchronized counts for {$updated} sessions."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fixing capacities: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add abstract to session
     */
    public function addToSession(Request $request)
    {
        $validated = $request->validate([
            'abstract_id' => 'required|exists:abstract_submissions,id',
            'session_id' => 'required|exists:conference_sessions,id',
        ]);

        $abstract = AbstractSubmission::find($validated['abstract_id']);
        $session = ConferenceSession::find($validated['session_id']);

        // Check if abstract is eligible
        if ($abstract->status !== 'accepted' || !$abstract->conference_code) {
            return response()->json([
                'success' => false,
                'message' => 'Abstract is not eligible for program assignment.'
            ], 422);
        }

        if (! $this->sessionAcceptsAbstracts($session)) {
            return response()->json([
                'success' => false,
                'message' => 'This program item does not accept abstract assignments.'
            ], 422);
        }

        $actualCount = $session->abstracts()->where('status', 'accepted')->count();
        $capacity = $this->expectedAbstractCapacity($session);

        // Check if session has capacity
        if ($capacity > 0 && $actualCount >= $capacity) {
            return response()->json([
                'success' => false,
                'message' => "Session is full ({$actualCount}/{$capacity})."
            ], 422);
        }

        // Subtheme mismatch check — warn but never block
        $subthemeWarning = null;
        if ($session->subtheme) {
            $abstractNormalizedSubtheme = \App\Models\AbstractSubmission::normalizeSubthemeLabel($abstract->subtheme);
            if ($abstractNormalizedSubtheme && $abstractNormalizedSubtheme !== $session->subtheme) {
                $subthemeWarning = "Note: this abstract's subtheme ({$abstractNormalizedSubtheme}) differs from the session's subtheme ({$session->subtheme}).";
            }
        }

        $oldSessionId = $abstract->session_id;

        $maxOrder = AbstractSubmission::where('session_id', $session->id)->max('session_order') ?? 0;

        $abstract->update([
            'session_id' => $session->id,
            'session_order' => $maxOrder + 1
        ]);

        // Sync the old session's timeline so its ordering stays clean.
        if ($oldSessionId && $oldSessionId !== $session->id) {
            $oldSession = ConferenceSession::find($oldSessionId);
            if ($oldSession) {
                $this->syncSessionPresentationTimeline($oldSession);
            }
        }

        // Do NOT call resequenceCodePrefixAcrossProgram here — that function
        // redistributes every abstract sharing the same code prefix across
        // sessions in "sorted" order, which fights manual placement and causes
        // the programme to show the reshuffled assignment instead of the one
        // the user just made.  Only sync the target session's timeline.
        $this->syncSessionPresentationTimeline($session->fresh());

        // Reload the abstract with updated data including session
        $abstract->refresh();
        $abstract->load('session');

        // Send notification to author if they have a user account
        if ($abstract->user_id) {
            try {
                $emailService = app(\App\Services\EmailNotificationService::class);
                $emailService->sendSessionAssignmentNotification($abstract);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to send session assignment notification: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Abstract added to session successfully!',
            'subtheme_warning' => $subthemeWarning,
            'abstract' => $abstract
        ]);
    }

    /**
     * Remove abstract from session
     */
    public function removeFromSession(Request $request)
    {
        $validated = $request->validate([
            'abstract_id' => 'required|exists:abstract_submissions,id',
            'session_id' => 'required|exists:conference_sessions,id',
        ]);

        $abstract = AbstractSubmission::find($validated['abstract_id']);
        $session = ConferenceSession::find($validated['session_id']);

        if ($abstract->session_id === $session->id) {
            $code = $abstract->conference_code;
            $abstract->update([
                'session_id' => null,
                'session_order' => null,
                'presentation_session' => null,
                'presentation_date' => null,
                'presentation_time' => null,
            ]);
            $this->compactSessionOrder($session);
            // Skip resequenceCodePrefixAcrossProgram — same reason as addToSession:
            // it would move other abstracts around and fight manual placements.

            return response()->json([
                'success' => true,
                'message' => 'Abstract removed from session successfully!'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Abstract is not in this session.'
        ], 422);
    }

    /**
     * Reorder abstracts within a session
     */
    public function reorderSession(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:conference_sessions,id',
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id'
        ]);

        $sessionId = $validated['session_id'];
        $abstractIds = $validated['abstract_ids'];

        foreach ($abstractIds as $index => $abstractId) {
            AbstractSubmission::where('id', $abstractId)
                ->where('session_id', $sessionId)
                ->update(['session_order' => $index + 1]);
        }

        $codes = AbstractSubmission::whereIn('id', $abstractIds)
            ->pluck('conference_code')
            ->filter()
            ->unique()
            ->all();

        foreach ($codes as $code) {
            $this->resequenceCodePrefixAcrossProgram($code);
        }

        $this->syncSessionPresentationTimeline(ConferenceSession::find($sessionId));

        return response()->json([
            'success' => true,
            'message' => 'Session order updated successfully'
        ]);
    }

    /**
     * Check for scheduling conflicts
     */
    public function checkConflicts(Request $request)
    {
        $sessionId = $request->input('session_id');
        $abstractId = $request->input('abstract_id');

        $session = ConferenceSession::findOrFail($sessionId);
        $abstract = AbstractSubmission::with('user')->findOrFail($abstractId);

        // Get all other sessions happening at the same time OR within a 15-minute buffer
        $conflictingSessions = ConferenceSession::where('id', '!=', $sessionId)
            ->where('is_active', true)
            ->where(function($q) use ($session) {
                // Buffer of 15 minutes between sessions
                $bufferMinutes = 15;

                // Convert to Carbon for math if they aren't already
                $start = \Carbon\Carbon::parse($session->start_time);
                $end = \Carbon\Carbon::parse($session->end_time);

                $startTimeWithBuffer = $start->copy()->subMinutes($bufferMinutes);
                $endTimeWithBuffer = $end->copy()->addMinutes($bufferMinutes);

                // Overlap check with buffer
                $q->where('schedule_days', $session->schedule_days)
                  ->where('start_time', '<', $endTimeWithBuffer)
                  ->where('end_time', '>', $startTimeWithBuffer);
            })
            ->with(['abstracts.user'])
            ->get();

        $conflicts = [];

        // Check if the author is presenting in any conflicting session
        foreach ($conflictingSessions as $confSession) {
            foreach ($confSession->abstracts as $otherAbstract) {
                // Check main author (user_id)
                if ($otherAbstract->user_id === $abstract->user_id) {
                    $conflicts[] = [
                        'type' => 'author_conflict',
                        'message' => "Author {$abstract->author_name} is also presenting in '{$confSession->name}' ({$otherAbstract->conference_code})",
                        'session_name' => $confSession->name,
                        'abstract_code' => $otherAbstract->conference_code
                    ];
                }

                // Check if author name matches (fallback for non-user authors)
                elseif (strcasecmp($otherAbstract->author_name, $abstract->author_name) === 0) {
                    $conflicts[] = [
                        'type' => 'name_match_conflict',
                        'message' => "Author name '{$abstract->author_name}' matches presenter in '{$confSession->name}' ({$otherAbstract->conference_code})",
                        'session_name' => $confSession->name,
                        'abstract_code' => $otherAbstract->conference_code
                    ];
                }
            }

            // Check if author is chair or rapporteur
            if ($confSession->session_chair === $abstract->author_name || ($abstract->user && $confSession->session_chair_email === $abstract->user->email)) {
                 $conflicts[] = [
                    'type' => 'chair_conflict',
                    'message' => "Author {$abstract->author_name} is chairing '{$confSession->name}'",
                    'session_name' => $confSession->name
                ];
            }
        }

        return response()->json([
            'has_conflicts' => count($conflicts) > 0,
            'conflicts' => $conflicts
        ]);
    }

    /**
     * Bulk assign conference codes - shows all accepted abstracts with filter option
     */
    public function assignCodesPage(Request $request)
    {
        $filter = $request->get('filter', 'all'); // 'all', 'pending', 'assigned'

        $query = AbstractSubmission::where('status', 'accepted')
            ->with(['user', 'session']);

        // Apply filter
        if ($filter === 'pending') {
            $query->whereNull('conference_code');
        } elseif ($filter === 'assigned') {
            $query->whereNotNull('conference_code');
        }

        // Apply search if provided
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('conference_code', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%");
            });
        }

        // Apply subtheme filter
        if ($request->filled('subtheme')) {
            $query->where('subtheme', $request->subtheme);
        }

        $pendingAbstracts = $query->orderBy('conference_code', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $sessions = ConferenceSession::orderBy('name')->get();

        // Get stats for filter badges
        $stats = [
            'all' => AbstractSubmission::where('status', 'accepted')->count(),
            'pending' => AbstractSubmission::where('status', 'accepted')->whereNull('conference_code')->count(),
            'assigned' => AbstractSubmission::where('status', 'accepted')->whereNotNull('conference_code')->count(),
        ];

        // Get unique subthemes for filter
        $subthemes = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('subtheme')
            ->distinct()
            ->pluck('subtheme')
            ->sort();

        return view('admin.conference-program.assign-codes', compact('pendingAbstracts', 'sessions', 'filter', 'stats', 'subthemes'));
    }

    /**
     * Save assigned codes
     */
    public function saveAssignedCodes(Request $request)
    {
        // Support the bulk assign-codes form which submits codes[abstract_id] => value
        if ($request->has('codes') && !$request->has('assignments')) {
            $codes = $request->input('codes', []);
            $assignments = [];
            foreach ($codes as $abstractId => $code) {
                $code = trim((string) $code);
                if ($code === '') {
                    continue;
                }
                $assignments[] = [
                    'abstract_id' => $abstractId,
                    'conference_code' => $code,
                ];
            }
            $request->merge(['assignments' => $assignments]);
        }

        $validated = $request->validate([
            'assignments' => 'required|array',
            'assignments.*.abstract_id' => 'required|exists:abstract_submissions,id',
            'assignments.*.conference_code' => 'required|string|max:20',
            'assignments.*.presentation_mode' => 'nullable|string|in:oral,poster,audio_poster',
            'assignments.*.subtheme' => 'nullable|string|max:255',
            'assignments.*.session_id' => 'nullable|exists:conference_sessions,id',
            'assignments.*.session_topic' => 'nullable|string|max:120',
        ]);

        $updated = 0;
        $errors = [];

        foreach ($validated['assignments'] as $assignment) {
            try {
                $abstract = AbstractSubmission::find($assignment['abstract_id']);
                $originalSubtheme = $abstract->subtheme;
                $originalTopic = trim((string) $abstract->session_topic);
                $conferenceCode = $this->normalizeConferenceCode($assignment['conference_code']);

                if (!$conferenceCode) {
                    throw new \InvalidArgumentException('Conference code cannot be empty.');
                }

                if ($duplicate = $this->findDuplicateConferenceCode($conferenceCode, $abstract->id)) {
                    throw new \InvalidArgumentException("Conference code {$conferenceCode} is already used by abstract #{$duplicate->id}.");
                }

                $updateData = [
                    'conference_code' => $conferenceCode,
                    'code_assigned_by' => Auth::id(),
                    'code_assigned_at' => now(),
                    'code_is_final' => true,
                ];

                if (isset($assignment['presentation_mode'])) {
                    $updateData['presentation_mode'] = $assignment['presentation_mode'];
                }

                if (isset($assignment['subtheme'])) {
                    $updateData['subtheme'] = $assignment['subtheme'];
                }

                if (isset($assignment['session_id'])) {
                    $session = ConferenceSession::find($assignment['session_id']);
                    $maxOrder = $session
                        ? (AbstractSubmission::where('session_id', $session->id)->max('session_order') ?? 0)
                        : 0;

                    $updateData['session_id'] = $assignment['session_id'];
                    $updateData['session_order'] = $session ? $maxOrder + 1 : null;
                }

                $abstract->update($updateData);
                $this->resequenceCodePrefixAcrossProgram($abstract->conference_code);

                $abstract->refresh();
                $incomingTopic = trim((string) ($assignment['session_topic'] ?? ''));
                $topicService = app(SessionTopicDetectionService::class);
                if (array_key_exists('session_topic', $assignment) && $incomingTopic !== $originalTopic) {
                    $topicService->applyManualTopic($abstract, $assignment['session_topic']);
                } elseif (($assignment['subtheme'] ?? $originalSubtheme) !== $originalSubtheme && $abstract->session_topic_source !== 'manual') {
                    $topicService->syncSuggestedTopic($abstract);
                }

                $this->notifyAuthorIfSubthemeChanged($abstract, $originalSubtheme, $assignment['subtheme'] ?? $originalSubtheme);
                $updated++;

            } catch (\Exception $e) {
                $errors[] = "Error updating abstract {$assignment['abstract_id']}: " . $e->getMessage();
            }
        }

        $message = "Successfully assigned codes to {$updated} abstracts.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        $resynced = app(ConferenceCodeSyncService::class)->resyncAcceptedCodes();
        $message .= " Accepted codes resynced: {$resynced}.";

        return redirect()->route('admin.conference-program.assigned')
            ->with('success', $message);
    }

    private function notifyAuthorIfSubthemeChanged(AbstractSubmission $abstract, ?string $originalSubtheme, ?string $newSubtheme): void
    {
        $original = trim((string) $originalSubtheme);
        $updated = trim((string) $newSubtheme);

        if ($original === '' || $updated === '' || $original === $updated || !$abstract->user_id) {
            return;
        }

        app(EmailNotificationService::class)->sendAbstractSubthemeChangedNotification($abstract, $original, $updated);
    }

    /**
     * Generate conference program export
     */
    public function exportProgram(Request $request)
    {
        $format = $request->get('format', 'csv');
        $exportType = $request->get('export_type', 'full_program'); // full_program, by_day, by_session

        // Get all sessions with abstracts, organized by day
        $sessions = ConferenceSession::where('is_active', true)
            ->with(['abstracts' => function($query) {
                $query->where('status', 'accepted')
                      ->whereNotNull('conference_code')
                      ->orderBy('created_at'); // This maintains the order they were added
            }])
            ->orderBy('schedule_days')
            ->orderBy('start_time')
            ->get();

        // Group sessions by day
        $sessionsByDay = $sessions->groupBy(function($session) {
            if (empty($session->schedule_days)) return 'Unassigned';
            return $session->getSafePrimaryDate() ?? $session->schedule_days[0];
        });

        if ($format === 'json') {
            return response()->json([
                'sessions_by_day' => $sessionsByDay,
                'total_sessions' => $sessions->count(),
                'total_abstracts' => $sessions->sum(fn($s) => $s->abstracts->count())
            ]);
        }

        // Generate CSV in conference.csv format
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="conference_program_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function() use ($sessionsByDay) {
            $file = fopen('php://output', 'w');

            // Generate the CSV content matching conference.csv structure
            $this->generateConferenceCSV($file, $sessionsByDay);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Generate CSV content matching conference.csv format
     */
    private function generateConferenceCSV($file, $sessionsByDay)
    {
        $dayCounter = 1;
        $totalDays = count($sessionsByDay);

        foreach ($sessionsByDay as $date => $daySessions) {
            // Day header - format: "DAY 1: TUESDAY, JUNE 9, 2026"
            $dayName = $daySessions->first()->getPrimaryDayLabel();
            $dayHeader = "DAY {$dayCounter}: {$dayName}";

            // Separate sessions by type
            $plenarySessions = $daySessions->filter(fn($s) => $s->session_type === 'plenary');
            $specialEvents = $daySessions->filter(fn($s) => in_array($s->session_type, ['break', 'lunch', 'poster', 'opening', 'networking', 'meeting']));
            $parallelSessions = $daySessions->filter(fn($s) => in_array($s->session_type, ['presentation', 'panel', 'discussion']));

            // Check if plenary header should be combined with day header (like in conference.csv line 1-2)
            if ($plenarySessions->count() > 0 && $plenarySessions->first()->room_location) {
                $plenaryHall = $plenarySessions->first()->room_location;
                fputcsv($file, ["{$dayHeader}\n{$plenaryHall}: PLENARY SESSION"]);
            } else {
                fputcsv($file, [$dayHeader]);
            }

            // 1. PLENARY SESSIONS SECTION
            if ($plenarySessions->count() > 0) {
                // Plenary header (if not combined with day header)
                if (!$plenarySessions->first()->room_location || $dayCounter > 1) {
                    $plenaryHall = $plenarySessions->first()->room_location ?? 'HALL 1';
                    fputcsv($file, ["{$plenaryHall}: PLENARY SESSION"]);
                }

                // Plenary headers
                fputcsv($file, ['Time', '', '', '', '', '', 'Presentation and Speaker']);
                fputcsv($file, ['Chair']);
                fputcsv($file, ['Rapporteur']);
                fputcsv($file, ['Time', '', '', '', '', '', 'Session']);

                // Plenary session rows
                foreach ($plenarySessions->sortBy('start_time') as $session) {
                    $timeRange = '';
                    if ($session->start_time && $session->end_time) {
                        $start = \Carbon\Carbon::parse($session->start_time)->format('H:i');
                        $end = \Carbon\Carbon::parse($session->end_time)->format('H:i');
                        $timeRange = "{$start}-{$end}";
                    }

                    $sessionName = $session->name;
                    if ($session->speaker) {
                        $sessionName .= ($sessionName ? ': ' : '') . $session->speaker;
                    }

                    fputcsv($file, [$timeRange, '', '', '', '', '', $sessionName]);
                }

                fputcsv($file, []); // Empty line
            }

            // 2. SPECIAL EVENTS (Breaks, Poster Sessions, etc.)
            foreach ($specialEvents->sortBy('start_time') as $event) {
                $timeRange = '';
                if ($event->start_time && $event->end_time) {
                    $start = \Carbon\Carbon::parse($event->start_time)->format('H:i');
                    $end = \Carbon\Carbon::parse($event->end_time)->format('H:i');
                    $timeRange = "{$start}-{$end}";
                }

                $eventName = $event->name;
                if ($event->session_type === 'break') {
                    $eventName = 'Tea Break';
                } elseif ($event->session_type === 'lunch') {
                    $eventName = 'Lunch Break';
                } elseif ($event->session_type === 'poster') {
                    $eventName = 'Poster Session';
                }

                fputcsv($file, [$timeRange, '', '', '', '', '', $eventName]);
            }

            // 3. PARALLEL SESSIONS SECTION
            if ($parallelSessions->count() > 0) {
                fputcsv($file, ['PARALLEL SESSIONS']);

                // Group parallel sessions by time slot
                $sessionsByTimeSlot = $parallelSessions->groupBy(function($session) {
                    if ($session->start_time && $session->end_time) {
                        $start = \Carbon\Carbon::parse($session->start_time)->format('H:i');
                        $end = \Carbon\Carbon::parse($session->end_time)->format('H:i');
                        return "{$start}-{$end}";
                    }
                    return 'unscheduled';
                });

                foreach ($sessionsByTimeSlot->sortKeys() as $timeSlot => $sessionsInSlot) {
                    // Get unique locations for this time slot
                    $locations = $sessionsInSlot->pluck('room_location')->filter()->unique()->values();

                    // Location row - format: "Location,,,,,,Hall (Main),,,,,Hall 1(NAME),,,,,,,,Hall2(NAME),..."
                    $locationRow = ['Location', '', '', '', '', ''];
                    foreach ($locations as $loc) {
                        $locationRow[] = $loc;
                    }
                    // Fill to match CSV format (up to 5 locations)
                    while (count($locationRow) < 11) {
                        $locationRow[] = '';
                    }
                    fputcsv($file, $locationRow);

                    // Parallel sessions row
                    $sessionRow = ['Parallel sessions (P)', '', '', '', '', ''];
                    foreach ($sessionsInSlot as $session) {
                        $sessionRow[] = $session->name;
                    }
                    // Fill to match CSV format
                    while (count($sessionRow) < 11) {
                        $sessionRow[] = '';
                    }
                    fputcsv($file, $sessionRow);

                    // Chair row
                    $chairRow = ['Chair', '', '', '', '', ''];
                    foreach ($sessionsInSlot as $session) {
                        $chairRow[] = $session->session_chair ?? '';
                    }
                    while (count($chairRow) < 11) {
                        $chairRow[] = '';
                    }
                    fputcsv($file, $chairRow);

                    // Rapporteur row
                    $rapporteurRow = ['Rapporteur', '', '', '', '', ''];
                    foreach ($sessionsInSlot as $session) {
                        $rapporteurRow[] = $session->session_rapporteur ?? '';
                    }
                    while (count($rapporteurRow) < 11) {
                        $rapporteurRow[] = '';
                    }
                    fputcsv($file, $rapporteurRow);

                    // Time row with Abstract Code header
                    $timeRow = ['Time', '', '', '', '', '', 'Abstract Code'];
                    // Add empty cells for remaining sessions
                    for ($i = 0; $i < $sessionsInSlot->count() - 1; $i++) {
                        $timeRow[] = '';
                    }
                    while (count($timeRow) < 11) {
                        $timeRow[] = '';
                    }
                    fputcsv($file, $timeRow);

                    // Get maximum number of abstracts across all sessions in this time slot
                    $maxAbstracts = $sessionsInSlot->max(fn($s) => $s->abstracts->count());

                    // Generate abstract rows with time slots
                    // Format: "14:30-14:40,,,,,,,,,,,MAL01,,,,,,,,HIV01,,,,,,,NH01,..."
                    for ($i = 0; $i < $maxAbstracts; $i++) {
                        // Calculate individual time slots (10 minutes each typically)
                        $timeSlotStart = '';
                        $timeSlotEnd = '';
                        if ($sessionsInSlot->first()->start_time) {
                            $baseStart = \Carbon\Carbon::parse($sessionsInSlot->first()->start_time);
                            $slotStart = $baseStart->copy()->addMinutes($i * 10);
                            $slotEnd = $slotStart->copy()->addMinutes(10);
                            $timeSlotStart = $slotStart->format('H:i');
                            $timeSlotEnd = $slotEnd->format('H:i');
                        }
                        $individualTimeSlot = $timeSlotStart && $timeSlotEnd ? "{$timeSlotStart}-{$timeSlotEnd}" : '';

                        $row = [$individualTimeSlot, '', '', '', '', ''];

                        foreach ($sessionsInSlot as $session) {
                            $abstract = $session->abstracts->get($i);
                            $row[] = $abstract ? $abstract->conference_code : '';
                        }

                        // Fill remaining columns
                        while (count($row) < 11) {
                            $row[] = '';
                        }

                        fputcsv($file, $row);
                    }

                    // Add discussion period after abstracts
                    if ($maxAbstracts > 0) {
                        // Calculate discussion time slot
                        $discussionStart = '';
                        $discussionEnd = '';
                        if ($sessionsInSlot->first()->start_time && $sessionsInSlot->first()->end_time) {
                            $baseStart = \Carbon\Carbon::parse($sessionsInSlot->first()->start_time);
                            $discussionStart = $baseStart->copy()->addMinutes($maxAbstracts * 10)->format('H:i');
                            $discussionEnd = \Carbon\Carbon::parse($sessionsInSlot->first()->end_time)->format('H:i');
                        }
                        $discussionTime = $discussionStart && $discussionEnd ? "{$discussionStart}-{$discussionEnd}" : '';

                        $discussionRow = [$discussionTime, '', '', '', '', '', 'Discussion'];
                        for ($i = 0; $i < $sessionsInSlot->count() - 1; $i++) {
                            $discussionRow[] = 'Discussion';
                        }
                        while (count($discussionRow) < 11) {
                            $discussionRow[] = '';
                        }
                        fputcsv($file, $discussionRow);
                    }

                    fputcsv($file, []); // Empty line between time slots
                }
            }

            // 4. CLOSING CEREMONY (if last day)
            if ($dayCounter === $totalDays) {
                $closingSessions = $daySessions->filter(fn($s) => $s->session_type === 'closing');
                if ($closingSessions->count() > 0) {
                    $closing = $closingSessions->first();
                    $closingDate = $closing->getPrimaryDayLabel();
                    $closingHall = $closing->room_location ?? 'HALL 1';

                    fputcsv($file, ["DAY {$dayCounter}: {$closingDate}: CLOSING CEREMONY: {$closingHall}"]);
                    fputcsv($file, ['Chair:']);
                    fputcsv($file, ['Rapporteur:']);
                    fputcsv($file, ['Time', '', 'Activity', '', '', '', '', '', '', '', 'Responsible']);

                    // Closing ceremony activities
                    if ($closing->abstracts->count() > 0) {
                        foreach ($closing->abstracts as $abstract) {
                            $timeRange = '';
                            if ($closing->start_time && $closing->end_time) {
                                $start = \Carbon\Carbon::parse($closing->start_time)->format('H:i');
                                $end = \Carbon\Carbon::parse($closing->end_time)->format('H:i');
                                $timeRange = "{$start}-{$end}";
                            }

                            fputcsv($file, [
                                $timeRange,
                                '',
                                $abstract->title ?? $closing->name,
                                '', '', '', '', '', '', '',
                                $closing->session_chair ?? ''
                            ]);
                        }
                    } else {
                        // Use session name as activity
                        $timeRange = '';
                        if ($closing->start_time && $closing->end_time) {
                            $start = \Carbon\Carbon::parse($closing->start_time)->format('H:i');
                            $end = \Carbon\Carbon::parse($closing->end_time)->format('H:i');
                            $timeRange = "{$start}-{$end}";
                        }

                        fputcsv($file, [
                            $timeRange,
                            '',
                            $closing->name,
                            '', '', '', '', '', '', '',
                            $closing->session_chair ?? ''
                        ]);
                    }
                }
            }

            // End of day marker (if not last day)
            if ($dayCounter < $totalDays) {
                fputcsv($file, []);
            }

            $dayCounter++;
        }

        // Add summary at the end (if needed)
        $totalOralAbstracts = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->where('presentation_mode', 'Oral')
            ->count();

        fputcsv($file, []);
        fputcsv($file, ["Total abstracts for oral presentation = {$totalOralAbstracts}"]);
    }

    /**
     * Generate Abstract Book PDF
     */
    public function generateAbstractBook(Request $request, AbstractBookGenerationService $abstractBook)
    {
        $abstractBook->markQueued(Auth::id());

        GenerateAbstractBookPdf::dispatch(Auth::id())->onQueue('default');

        $queued = ['status' => 'queued', 'message' => 'Generation started.'];

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($queued);
        }

        return redirect()
            ->route('admin.conference-program.index')
            ->with('success', 'Generation started — the download button will appear automatically when it\'s ready.');
    }

    public function abstractBookDiagnostics()
    {
        $laravelLog = storage_path('logs/laravel.log');

        $tail = function (string $file, int $lines = 40): string {
            if (! is_file($file)) {
                return '(not found)';
            }
            $content = @file($file, FILE_IGNORE_NEW_LINES) ?: [];
            return implode("\n", array_slice($content, -$lines));
        };

        // Which CLI binary candidates actually exist on this server
        $candidates = array_filter([
            getenv('PHP_CLI_BINARY') ?: null,
            PHP_BINDIR . DIRECTORY_SEPARATOR . 'php',
            dirname(PHP_BINARY) . DIRECTORY_SEPARATOR . 'php',
            '/usr/local/bin/php',
            '/usr/bin/php',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php83/root/usr/bin/php',
        ]);
        $binaries = [];
        foreach ($candidates as $c) {
            $binaries[$c] = (@is_file($c) ? 'file' : 'missing') . (@is_executable($c) ? '+exec' : '');
        }

        return response()->json([
            'php_sapi'              => PHP_SAPI,
            'php_binary'            => PHP_BINARY,
            'php_bindir'            => PHP_BINDIR,
            'web_memory_limit'      => ini_get('memory_limit'),
            'cli_binary_candidates' => $binaries,
            'queue_connection'      => config('queue.default'),
            'queue_name'            => config('queue.connections.database.queue'),
            'pending_abstract_jobs' => \DB::table('jobs')->where('payload', 'like', '%GenerateAbstractBookPdf%')->count(),
            'failed_abstract_jobs'  => \DB::table('failed_jobs')
                ->where('payload', 'like', '%GenerateAbstractBookPdf%')
                ->orWhere('exception', 'like', '%AbstractBook%')
                ->count(),
            'laravel_log_tail'      => $tail($laravelLog, 25),
        ], 200, [], JSON_PRETTY_PRINT);
    }

    public function resetAbstractBookStatus(Request $request, AbstractBookGenerationService $abstractBook)
    {
        $abstractBook->resetStuckStatus();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['status' => 'idle', 'message' => 'Status reset.']);
        }

        return redirect()->route('admin.conference-program.index')
            ->with('success', 'Generation status reset. You can now generate again.');
    }

    public function abstractBookStatus(AbstractBookGenerationService $abstractBook)
    {
        return response()->json($abstractBook->status());
    }

    public function downloadAbstractBook(AbstractBookGenerationService $abstractBook)
    {
        return $abstractBook->downloadResponse();
    }

    /**
     * Download the Conference Proceedings as a print-ready PDF.
     *
     * Separate from the abstract book: abstracts only, and only those whose
     * author opted in via include_in_proceedings.
     */
    public function downloadProceedings(\App\Services\ConferenceProceedingsService $proceedings)
    {
        return $proceedings->downloadResponse();
    }

    /**
     * Open or close the author-facing camera-ready correction window.
     *
     * Codes should be locked (finalize-codes) BEFORE opening this: a resync
     * while authors are editing would renumber abstracts that are already
     * printed in the programme.
     */
    public function toggleProceedingsCorrections(Request $request, \App\Services\ProceedingsCorrectionService $corrections)
    {
        $validated = $request->validate([
            'action' => 'required|in:open,close',
            'closes_at' => 'nullable|date|after:now',
        ]);

        if ($validated['action'] === 'open') {
            $unlockedCodes = AbstractSubmission::where('status', 'accepted')
                ->whereNotNull('conference_code')
                ->where('code_is_final', false)
                ->count();

            $corrections->open(
                !empty($validated['closes_at']) ? \Illuminate\Support\Carbon::parse($validated['closes_at']) : null,
                Auth::id()
            );

            $message = 'Proceedings corrections are now open. Authors of accepted abstracts can edit their book entry.';

            if ($unlockedCodes > 0) {
                $message .= " Warning: {$unlockedCodes} accepted abstract(s) still have unlocked codes — "
                    . 'run "Finalize codes" before any resync, or codes may be renumbered after the programme is printed.';
            }
        } else {
            $corrections->close(Auth::id());
            $message = 'Proceedings corrections are now closed.';
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'is_open' => $corrections->isOpen(),
                'closes_at' => $corrections->closesAt(),
                'message' => $message,
            ]);
        }

        return redirect()->route('admin.conference-program.index')->with('success', $message);
    }

    private function streamAssignedCodesCsv($abstracts)
    {
        $short = config('conference.short_name');
        $filename = sprintf('%s-conference-codes-%s.csv', strtolower($short), now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($abstracts) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Conference Code',
                'Title',
                'Author',
                'Subtheme',
                'Session Topic',
                'Presentation Mode',
                'Selected',
                'Assigned Date',
            ]);

            foreach ($abstracts as $abstract) {
                fputcsv($handle, [
                    $abstract->conference_code ?? '',
                    $abstract->title ?? '',
                    $abstract->author_name ?? '',
                    $abstract->normalized_subtheme ?: ($abstract->subtheme ?? ''),
                    $abstract->session_topic ?? '',
                    $abstract->presentation_mode ?? '',
                    $abstract->committee_selected ? 'Yes' : 'No',
                    $abstract->code_assigned_at ? $abstract->code_assigned_at->format('Y-m-d') : '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Generate PDF export of conference program
     */
    public function exportProgramPDF(Request $request)
    {
        $programByDay = $this->buildProgramByDay($this->getProgramExportSessions());
        $contentPdf   = $this->buildContentPdf($programByDay);

        $short    = config('conference.short_name');
        $year     = config('conference.year', date('Y'));
        $filename = "{$short}-{$year}-Conference-Programme-" . now()->format('Y-m-d') . '.pdf';

        $merged = $this->prependCoverPdf($contentPdf->output());

        return response($merged, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * View PDF in browser (stream instead of download)
     */
    public function viewProgramPDF(Request $request)
    {
        $programByDay = $this->buildProgramByDay($this->getProgramExportSessions());
        $contentPdf   = $this->buildContentPdf($programByDay);

        $short = config('conference.short_name');
        $year  = config('conference.year', date('Y'));

        $merged = $this->prependCoverPdf($contentPdf->output());

        return response($merged, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . "{$short}-{$year}-Conference-Programme.pdf" . '"',
        ]);
    }

    private function buildContentPdf($programByDay)
    {
        return Pdf::loadView('admin.conference-program.programme', compact('programByDay'))
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false);
    }

    private function prependCoverPdf(string $contentPdfString): string
    {
        $coverPath     = (($p = config('print_design.covers.programme')) ? public_path($p) : '');
        $backCoverPath = (($p = config('print_design.covers.back')) ? public_path($p) : '');

        $tempFile = tempnam(sys_get_temp_dir(), 'programme_');
        file_put_contents($tempFile, $contentPdfString);

        try {
            $fpdi = new Fpdi();
            $fpdi->SetAutoPageBreak(false);

            // Front cover
            if (file_exists($coverPath)) {
                $pageCount = $fpdi->setSourceFile($coverPath);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $fpdi->addPage('P', 'A4');
                    $tpl = $fpdi->importPage($i);
                    $fpdi->useTemplate($tpl, 0, 0, 210, 297);
                }
            }

            // Content pages
            $pageCount = $fpdi->setSourceFile($tempFile);
            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage($i);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);
            }

            // Back cover
            if (file_exists($backCoverPath)) {
                $fpdi->setSourceFile($backCoverPath);
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage(1);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);
            }

            return $fpdi->Output('', 'S');
        } finally {
            @unlink($tempFile);
        }
    }

    /**
     * Display comprehensive program view (better than builder)
     */
    public function programView()
    {
        $sessions = $this->getProgramExportSessions();
        $programByDay = $this->buildProgramByDay($sessions);

        // Calculate statistics
        $stats = [
            'total_days' => $programByDay->count(),
            'total_sessions' => $sessions->count(),
            'total_abstracts' => $sessions->sum(fn($s) => $s->abstracts->count()),
            'sessions_by_type' => $sessions->groupBy('session_type')->map->count(),
        ];

        return view('admin.conference-program.program-view', compact('programByDay', 'stats'));
    }

    private function getProgramExportSessions()
    {
        $sessions = ConferenceSession::where('is_active', true)
            ->with(['abstracts' => function ($query) {
                $query->where('status', 'accepted')
                    ->whereNotNull('conference_code')
                    ->orderByRaw('CASE WHEN session_order IS NULL THEN 1 ELSE 0 END')
                    ->orderBy('session_order')
                    ->orderBy('conference_code');
            }])
            ->orderBy('schedule_days')
            ->orderBy('start_time')
            ->orderBy('room_location')
            ->get();

        return $sessions;
    }

    private function buildProgramByDay($sessions)
    {
        $programByDay = $sessions->groupBy(function ($session) {
            if (empty($session->schedule_days)) {
                return 'Unassigned';
            }

            $days = is_string($session->schedule_days)
                ? json_decode($session->schedule_days, true)
                : $session->schedule_days;

            if (empty($days) || !is_array($days)) {
                return 'Unassigned';
            }

            return $session->getSafePrimaryDate() ?? (string) $days[0];
        });

        return $programByDay->sortKeys()->map(function ($daySessions) {
            return $daySessions->groupBy(function ($session) {
                try {
                    $startTime = $session->start_time
                        ? \Carbon\Carbon::parse($session->start_time)->format('H:i')
                        : 'TBD';
                    $endTime = $session->end_time
                        ? \Carbon\Carbon::parse($session->end_time)->format('H:i')
                        : 'TBD';
                } catch (\Throwable $e) {
                    $startTime = 'TBD';
                    $endTime = 'TBD';
                }
                return $startTime . '-' . $endTime;
            })->sortKeys();
        });
    }

    /**
     * Get program statistics
     */
    public function getProgramStats()
    {
        $stats = [
            'total_accepted' => AbstractSubmission::where('status', 'accepted')->count(),
            'with_codes' => AbstractSubmission::where('status', 'accepted')
                ->whereNotNull('conference_code')
                ->count(),
            'pending_codes' => AbstractSubmission::where('status', 'accepted')
                ->whereNull('conference_code')
                ->count(),
            'oral_presentations' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(presentation_mode) = ?', ['oral'])
                ->count(),
            'poster_presentations' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(presentation_mode) = ?', ['poster'])
                ->count(),
            'audio_poster_presentations' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(REPLACE(presentation_mode, \' \', \'_\')) = ?', ['audio_poster'])
                ->count(),
            'assigned_to_sessions' => AbstractSubmission::where('status', 'accepted')
                ->whereNotNull('session_id')
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Finalize all codes based on current session placement order.
     * Re-numbers codes per session date/time/order and locks them (code_is_final = true).
     */
    public function finalizeCodesFromSessionOrder()
    {
        $updated = app(\App\Services\ConferenceCodeAssignmentService::class)->finalizeFromSessionOrder();

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'message' => "Finalized and locked {$updated} conference codes based on session order.",
        ]);
    }

    /**
     * Toggle code_is_final on a single abstract.
     * Lock prevents the automatic resync from changing the code.
     */
    public function toggleCodeLock(Request $request, AbstractSubmission $abstract)
    {
        $service = app(\App\Services\ConferenceCodeAssignmentService::class);

        if ($abstract->code_is_final) {
            $service->unlockCode($abstract);
            $message = "Code {$abstract->conference_code} unlocked — resync may reassign it.";
        } else {
            $service->lockCode($abstract);
            $message = "Code {$abstract->conference_code} locked — resync will not change it.";
        }

        return response()->json([
            'success'       => true,
            'code_is_final' => $abstract->fresh()->code_is_final,
            'message'       => $message,
        ]);
    }

    /**
     * Search abstracts in program
     */
    public function searchAbstracts(Request $request)
    {
        $query = $request->get('q');
        $mode = $request->get('mode');
        $session = $request->get('session');

        $abstracts = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->with(['user', 'session'])
            ->when($query, function($q) use ($query) {
                $q->where(function($subQ) use ($query) {
                    $subQ->where('title', 'like', "%{$query}%")
                          ->orWhere('author_name', 'like', "%{$query}%")
                          ->orWhere('conference_code', 'like', "%{$query}%");
                });
            })
            ->when($mode, function($q) use ($mode) {
                $q->where('presentation_mode', $mode);
            })
            ->when($session, function($q) use ($session) {
                $q->where('session_id', $session);
            })
            ->orderBy('conference_code')
            ->paginate(20);

        return response()->json($abstracts);
    }

    private function buildAssignedCodesQuery(Request $request, bool $applySubthemeFilter = true)
    {
        $query = AbstractSubmission::where('status', 'accepted')
            ->with([
                'user',
                'session',
                'reviews' => function ($query) {
                    $query->where('status', 'submitted')
                        ->where('subtheme_relevance', 'suggest_change')
                        ->whereNotNull('suggested_subtheme')
                        ->whereRaw("TRIM(suggested_subtheme) <> ''")
                        ->orderByDesc('submitted_at')
                        ->orderByDesc('updated_at');
                },
            ]);

        $status = $request->get('assignment_status', 'all');
        if ($status === 'assigned') {
            $query->whereNotNull('conference_code');
        } elseif ($status === 'pending') {
            $query->whereNull('conference_code');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('conference_code', 'like', "%{$search}%")
                    ->orWhere('author_name', 'like', "%{$search}%")
                    ->orWhere('session_topic', 'like', "%{$search}%");
            });
        }

        if ($applySubthemeFilter && $request->filled('subtheme')) {
            $query->where('subtheme', $request->subtheme);
        }

        if ($request->filled('mode')) {
            $query->whereRaw('LOWER(presentation_mode) = ?', [strtolower($request->mode)]);
        }

        if ($request->filled('selected')) {
            $query->where('committee_selected', $request->selected === 'yes');
        }

        if ($request->get('subtheme_recommendation') === 'yes') {
            $query->whereHas('reviews', function ($q) {
                $q->where('status', 'submitted')
                    ->where('subtheme_relevance', 'suggest_change')
                    ->whereNotNull('suggested_subtheme')
                    ->whereRaw("TRIM(suggested_subtheme) <> ''");
            });
        }

        return $query;
    }

    /**
     * Update session details
     */
    public function updateSession(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:5000',
            'session_type' => 'required|in:presentation,plenary,panel,discussion,break,lunch,poster,opening,closing,networking,meeting,other',
            'date' => 'required|date',
            'presentation_type' => 'nullable|in:oral,poster',
            'speaker' => 'nullable|string|max:255',
            'panelists' => 'nullable|string',
            'description' => 'nullable|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'room_location' => 'nullable|string|max:255',
            'session_chair' => 'nullable|string|max:255',
            'session_chair_email' => 'nullable|email',
            'session_rapporteur' => 'nullable|string|max:255',
            'session_rapporteur_email' => 'nullable|email',
            'max_abstracts' => 'nullable|integer|min:0',
            'status' => 'nullable|in:draft,scheduled,ongoing,completed,cancelled',
        ]);

        $session = ConferenceSession::findOrFail($id);
        $oldChairId = $session->session_chair_id;
        $oldRapporteurId = $session->session_rapporteur_id;

        $presentationType = $this->normalizedPresentationType(
            $validated['session_type'],
            $validated['presentation_type'] ?? null
        );

        // Update session
        $session->update([
            'name' => $validated['name'],
            'session_type' => $validated['session_type'],
            'presentation_type' => $presentationType,
            'speaker' => $validated['speaker'] ?? null,
            'panelists' => $validated['panelists'] ?? null,
            'description' => $validated['description'] ?? null,
            'schedule_days' => [$validated['date']],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'room_location' => $validated['room_location'] ?? null,
            'session_chair' => $validated['session_chair'] ?? null,
            'session_chair_email' => $validated['session_chair_email'] ?? null,
            'session_chair_id' => $request->input('session_chair_id'),
            'session_rapporteur' => $validated['session_rapporteur'] ?? null,
            'session_rapporteur_email' => $validated['session_rapporteur_email'] ?? null,
            'session_rapporteur_id' => $request->input('session_rapporteur_id'),
            'max_abstracts' => $validated['max_abstracts'] ?? $this->expectedAbstractCapacityForInput(
                $validated['session_type'],
                $presentationType,
                $validated['start_time'],
                $validated['end_time'],
                $session->max_abstracts
            ),
            'status' => $validated['status'] ?? $session->status,
            'updated_by' => Auth::id(),
        ]);

        // Notifications are now handled manually via the "Notify" button when program is final

        return response()->json([
            'success' => true,
            'message' => 'Session updated successfully!',
            'session' => $session
        ]);
    }

    /**
     * Delete session
     */
    public function deleteSession($id)
    {
        $session = ConferenceSession::findOrFail($id);

        // Remove all abstracts from this session
        AbstractSubmission::where('session_id', $session->id)
            ->update(['session_id' => null]);

        // Delete the session
        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Session deleted successfully!'
        ]);
    }

    /**
     * Get session details for editing
     */
    public function getSession($id)
    {
        $session = ConferenceSession::findOrFail($id);

        $data = $session->toArray();
        // start_time / end_time are cast as datetime, so Eloquent serialises them
        // as full ISO strings (e.g. "2026-06-04T08:30:00.000000Z").  The edit
        // modal's <input type="time"> expects "HH:MM", so format explicitly.
        $data['start_time'] = $session->start_time ? $session->start_time->format('H:i') : null;
        $data['end_time']   = $session->end_time   ? $session->end_time->format('H:i')   : null;

        return response()->json([
            'success' => true,
            'session' => $data
        ]);
    }

    private function sessionAcceptsAbstracts(ConferenceSession $session): bool
    {
        return in_array($session->session_type, ['presentation', 'poster'], true);
    }

    private function normalizedPresentationType(string $sessionType, ?string $presentationType): ?string
    {
        if ($sessionType === 'poster') {
            return 'poster';
        }

        if ($sessionType === 'presentation') {
            return $presentationType ?: 'oral';
        }

        return 'oral';
    }

    private function expectedAbstractCapacity(ConferenceSession $session): int
    {
        return $this->expectedAbstractCapacityForInput(
            (string) $session->session_type,
            $session->presentation_type,
            $session->getRawOriginal('start_time') ?: $session->start_time,
            $session->getRawOriginal('end_time') ?: $session->end_time,
            (int) ($session->max_abstracts ?? 0)
        );
    }

    private function expectedAbstractCapacityForInput(
        string $sessionType,
        ?string $presentationType,
        mixed $startTime,
        mixed $endTime,
        int $fallback
    ): int {
        if (! in_array($sessionType, ['presentation', 'poster'], true)) {
            return 0;
        }

        if ($sessionType === 'poster') {
            return max($fallback, 0);
        }

        if ($presentationType !== 'oral') {
            return max($fallback, 0);
        }

        try {
            $start = \Carbon\Carbon::parse($startTime);
            $end = \Carbon\Carbon::parse($endTime);
            $minutes = $start->diffInMinutes($end, false);
        } catch (\Throwable $e) {
            $minutes = 0;
        }

        return $minutes > 0 ? (int) floor($minutes / 10) : max($fallback, 0);
    }

    private function normalizeConferenceCode(?string $code): ?string
    {
        $code = strtoupper(trim((string) $code));

        return $code === '' ? null : $code;
    }

    private function findDuplicateConferenceCode(string $code, int $exceptAbstractId): ?AbstractSubmission
    {
        return AbstractSubmission::where('conference_code', $code)
            ->where('id', '!=', $exceptAbstractId)
            ->first();
    }

    private function resequenceCodePrefixAcrossProgram(?string $conferenceCode): void
    {
        $prefix = $this->conferenceCodePrefix($conferenceCode);

        if (!$prefix) {
            return;
        }

        $assigned = AbstractSubmission::query()
            ->where('status', 'accepted')
            ->whereNotNull('session_id')
            ->where('conference_code', 'like', $prefix . '-%')
            ->with('session')
            ->get();

        if ($assigned->count() < 2) {
            $assigned->each(fn (AbstractSubmission $abstract) => $this->syncSessionPresentationTimeline($abstract->session));
            return;
        }

        $slots = $assigned
            ->filter(fn (AbstractSubmission $abstract) => $abstract->session)
            ->sortBy(fn (AbstractSubmission $abstract) => $this->sessionChronologyKey($abstract->session) . '|' . str_pad((string) ($abstract->session_order ?? 9999), 5, '0', STR_PAD_LEFT))
            ->map(fn (AbstractSubmission $abstract) => [
                'session_id' => $abstract->session_id,
                'session_order' => (int) ($abstract->session_order ?? 0),
            ])
            ->values();

        $codeOrdered = $assigned
            ->sortBy(fn (AbstractSubmission $abstract) => $this->conferenceCodeSortKey($abstract->conference_code))
            ->values();

        $touchedSessionIds = [];

        foreach ($codeOrdered as $index => $abstract) {
            $slot = $slots[$index] ?? null;
            if (!$slot) {
                continue;
            }

            $abstract->update([
                'session_id' => $slot['session_id'],
                'session_order' => $slot['session_order'],
            ]);

            $touchedSessionIds[$slot['session_id']] = true;
            if ($abstract->session_id) {
                $touchedSessionIds[$abstract->session_id] = true;
            }
        }

        foreach (array_keys($touchedSessionIds) as $sessionId) {
            $this->syncSessionPresentationTimeline(ConferenceSession::find($sessionId));
        }
    }

    private function syncSessionPresentationTimeline(?ConferenceSession $session): void
    {
        if (!$session) {
            return;
        }

        $date = $session->schedule_days[0] ?? null;
        $start = $session->start_time ? \Carbon\Carbon::parse($session->start_time) : null;

        $abstracts = AbstractSubmission::query()
            ->where('session_id', $session->id)
            ->orderByRaw('CASE WHEN session_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('session_order')
            ->orderBy('conference_code')
            ->get();

        foreach ($abstracts as $index => $abstract) {
            $order = $index + 1;
            $abstract->update([
                'session_order' => $order,
                'presentation_session' => $session->name,
                'presentation_date' => $date,
                'presentation_time' => $start ? $start->copy()->addMinutes($index * ($session->session_type === 'poster' ? 6 : 10))->format('H:i:s') : null,
            ]);
        }

        $session->update(['current_abstracts' => $abstracts->count()]);
    }

    private function compactSessionOrder(?ConferenceSession $session): void
    {
        $this->syncSessionPresentationTimeline($session);
    }

    private function conferenceCodePrefix(?string $conferenceCode): ?string
    {
        if (!preg_match('/^([A-Za-z]+)-\d+$/', trim((string) $conferenceCode), $matches)) {
            return null;
        }

        return strtoupper($matches[1]);
    }

    private function conferenceCodeSortKey(?string $conferenceCode): string
    {
        $code = strtoupper(trim((string) $conferenceCode));

        if (preg_match('/^([A-Z]+)-(\d+)$/', $code, $matches)) {
            return $matches[1] . '-' . str_pad($matches[2], 8, '0', STR_PAD_LEFT);
        }

        return $code;
    }

    private function sessionChronologyKey(ConferenceSession $session): string
    {
        $date = $session->schedule_days[0] ?? '9999-12-31';
        $start = $session->start_time ? \Carbon\Carbon::parse($session->start_time)->format('H:i:s') : '23:59:59';

        return implode('|', [
            $date,
            $start,
            str_pad((string) ($session->sort_order ?? 9999), 5, '0', STR_PAD_LEFT),
            str_pad((string) $session->id, 5, '0', STR_PAD_LEFT),
        ]);
    }

    /**
     * Quick add invited/guest presentation directly to program
     * This allows adding distinguished guests who don't go through the normal submission process
     */
    public function quickAddGuestPresentation(Request $request)
    {
        $validated = $request->validate([
            'author_name' => 'required|string|max:255',
            'author_institute' => 'nullable|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:10000',
            'subtheme' => 'nullable|string|max:255',
            'presentation_mode' => 'required|in:Oral,Poster',
            'conference_code' => 'required|string|max:20|unique:abstract_submissions,conference_code',
            'session_id' => 'nullable|exists:conference_sessions,id',
            'committee_selected' => 'boolean',
            'committee_notes' => 'nullable|string',
        ]);

        try {
            // Check if session has capacity if provided
            if ($validated['session_id'] ?? null) {
                $session = ConferenceSession::find($validated['session_id']);
                if ($session && ! $this->sessionAcceptsAbstracts($session)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This program item does not accept abstract assignments.'
                    ], 422);
                }

                $capacity = $session ? $this->expectedAbstractCapacity($session) : 0;
                $actualCount = $session ? $session->abstracts()->where('status', 'accepted')->count() : 0;
                if ($session && $capacity > 0 && $actualCount >= $capacity) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Session is full. Please select another session or leave unassigned for now.'
                    ], 422);
                }
            }

            $session = isset($validated['session_id']) ? ConferenceSession::find($validated['session_id']) : null;
            $nextOrder = $session
                ? (AbstractSubmission::where('session_id', $session->id)->max('session_order') ?? 0) + 1
                : null;

            // Create the invited presentation
            $abstract = AbstractSubmission::create([
                'author_name' => $validated['author_name'],
                'author_institute' => $validated['author_institute'] ?? 'Invited Speaker',
                'title' => $validated['title'],
                'description' => $validated['description'] ?? 'Invited presentation',
                'subtheme' => $validated['subtheme'] ?? 'General',
                'presentation_mode' => $validated['presentation_mode'],
                'conference_code' => strtoupper($validated['conference_code']),
                'status' => 'accepted', // Automatically accepted as invited
                'is_invited' => true, // Mark as invited presentation
                'invited_created_by' => Auth::id(),
                'invited_created_at' => now(),
                'session_id' => $validated['session_id'] ?? null,
                'session_order' => $nextOrder,
                'committee_selected' => $validated['committee_selected'] ?? false,
                'committee_notes' => $validated['committee_notes'] ?? null,
                'code_assigned_by' => Auth::id(),
                'code_assigned_at' => now(),
                'submitted_at' => now(), // For consistency
                'user_id' => null, // No user account required for invited speakers
            ]);

            if ($abstract->session_id) {
                $this->resequenceCodePrefixAcrossProgram($abstract->conference_code);
                $this->syncSessionPresentationTimeline($abstract->session);
            }

            // Load relationships for response
            $abstract->load(['session', 'invitedCreatedBy']);

            return response()->json([
                'success' => true,
                'message' => 'Invited presentation added successfully!',
                'abstract' => $abstract
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating invited presentation: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * Bulk assign abstracts to sessions
     */
    public function bulkAssignToSession(Request $request)
    {
        $validated = $request->validate([
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id',
            'session_id' => 'required|exists:conference_sessions,id',
        ]);

        $session = ConferenceSession::find($validated['session_id']);
        $assigned = 0;
        $errors = [];
        $subthemeWarnings = [];
        $affectedCodes = [];

        if (! $this->sessionAcceptsAbstracts($session)) {
            return response()->json([
                'success' => false,
                'assigned' => 0,
                'errors' => ['This program item does not accept abstract assignments.'],
                'subtheme_warnings' => [],
                'message' => 'This program item does not accept abstract assignments.'
            ], 422);
        }

        foreach ($validated['abstract_ids'] as $abstractId) {
            $abstract = AbstractSubmission::find($abstractId);

            // Check if abstract is eligible
            if ($abstract->status !== 'accepted' || !$abstract->conference_code) {
                $errors[] = "Abstract {$abstract->conference_code} is not eligible";
                continue;
            }

            // Check if session has capacity
            $capacity = $this->expectedAbstractCapacity($session);
            $actualCount = $session->abstracts()->where('status', 'accepted')->count();
            if ($capacity > 0 && $actualCount >= $capacity) {
                $errors[] = "Session is full";
                break;
            }

            // Subtheme mismatch check — warn but never block
            if ($session->subtheme) {
                $abstractNormalizedSubtheme = \App\Models\AbstractSubmission::normalizeSubthemeLabel($abstract->subtheme);
                if ($abstractNormalizedSubtheme && $abstractNormalizedSubtheme !== $session->subtheme) {
                    $subthemeWarnings[] = "{$abstract->conference_code}: subtheme mismatch ({$abstractNormalizedSubtheme} → session is {$session->subtheme})";
                }
            }

            // Assign abstract to session
            $maxOrder = AbstractSubmission::where('session_id', $session->id)->max('session_order') ?? 0;
            $abstract->update([
                'session_id'    => $session->id,
                'session_order' => $maxOrder + 1,
            ]);
            $affectedCodes[] = $abstract->conference_code;
            $session->refresh();
            $assigned++;
        }

        foreach (array_unique(array_filter($affectedCodes)) as $code) {
            $this->resequenceCodePrefixAcrossProgram($code);
        }

        $this->syncSessionPresentationTimeline($session->fresh());

        return response()->json([
            'success'           => true,
            'assigned'          => $assigned,
            'errors'            => $errors,
            'subtheme_warnings' => $subthemeWarnings,
            'message'           => "Successfully assigned {$assigned} abstracts to session!"
        ]);
    }

    /**
     * Send notifications to all authors with session assignments
     */
    public function sendBulkSessionNotifications(Request $request)
    {
        $emailService = app(\App\Services\EmailNotificationService::class);

        // Get all abstracts that are assigned to sessions and have users
        $abstracts = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('session_id')
            ->whereNotNull('user_id')
            ->with(['session', 'user'])
            ->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($abstracts as $abstract) {
            try {
                $emailService->sendSessionAssignmentNotification($abstract);
                $successCount++;
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Failed to notify author for abstract #{$abstract->id}: " . $e->getMessage());
                $failCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent {$successCount} notifications successfully. {$failCount} failed.",
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'total' => $abstracts->count()
        ]);
    }

    /**
     * Download all presentations for a specific session as a ZIP file
     */
    public function downloadSessionPresentations(Request $request, PresentationDownloadService $downloads, $sessionId)
    {
        $session = ConferenceSession::find($sessionId);
        if (!$session) {
            return redirect()->back()->with('error', 'Session not found.');
        }

        $downloads->queue('session', ['session_id' => (int) $sessionId], Auth::id());

        return $this->downloadQueuedResponse($request, $session->name);
    }

    /**
     * Queue a background ZIP of every presentation scheduled on a conference day.
     */
    public function downloadDayPresentations(Request $request, PresentationDownloadService $downloads, $day)
    {
        $day = (int) $day;
        $target = collect($downloads->conferenceDays())->firstWhere('number', $day);

        if (!$target) {
            return redirect()->back()->with('error', 'That conference day has no scheduled sessions.');
        }

        $downloads->queue('day', ['day' => $day], Auth::id());

        return $this->downloadQueuedResponse($request, "Day {$day}");
    }

    /**
     * Queue a background ZIP of all presentations (optionally filtered by mode/session).
     */
    public function downloadAllPresentations(Request $request, PresentationDownloadService $downloads)
    {
        $params = [
            'mode'       => $request->get('mode', 'all'),
            'session_id' => $request->get('session_id'),
        ];

        $downloads->queue('all', array_filter($params, fn ($v) => $v !== null), Auth::id());

        return $this->downloadQueuedResponse($request, 'All presentations');
    }

    /**
     * JSON list of recent download jobs (polled by the Downloads panel).
     */
    public function presentationDownloadJobs(PresentationDownloadService $downloads)
    {
        return response()->json(['jobs' => $downloads->all()]);
    }

    /**
     * Serve a finished ZIP for download.
     */
    public function presentationDownloadFile(PresentationDownloadService $downloads, string $jobId)
    {
        $file = $downloads->readyFilePath($jobId);

        if (!$file) {
            return redirect()->back()->with('error', 'That download is not ready or has expired.');
        }

        return response()->download($file['path'], $file['name']);
    }

    /**
     * Cancel a queued/processing job or dismiss a finished one.
     */
    public function cancelPresentationDownload(Request $request, PresentationDownloadService $downloads, string $jobId)
    {
        $cancelled = $downloads->cancel($jobId);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => $cancelled]);
        }

        return redirect()
            ->route('admin.abstracts.presentations')
            ->with($cancelled ? 'success' : 'error', $cancelled ? 'Download cancelled.' : 'That download no longer exists.');
    }

    /**
     * Shared response for a queued download trigger: JSON for fetch/XHR,
     * a flash + redirect for plain links.
     */
    private function downloadQueuedResponse(Request $request, string $label)
    {
        $message = "Preparing “{$label}” in the background — it will appear in the Downloads panel on the Presentations page when ready.";

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
        }

        return redirect()
            ->route('admin.abstracts.presentations')
            ->with('success', $message);
    }

    /**
     * Notify chairperson and rapporteur for a specific session
     */
    public function notifySessionLeads(Request $request, $id)
    {
        $session = ConferenceSession::findOrFail($id);

        // Generate token if not exists for confirmation links
        if (!$session->confirmation_token) {
            $session->confirmation_token = \Illuminate\Support\Str::random(32);
            $session->save();
        }

        $notifiedCount = 0;

        $pdfData = $this->generateProgramPdfContent();
        $pdfName = config('conference.file_prefix') . '-Conference-Programme.pdf';

        // Notify Chair
        if ($session->session_chair_id) {
            $chair = \App\Models\User::find($session->session_chair_id);
            if ($chair) {
                $this->emailService->sendChairAssignment($chair, $session, [
                    'pdf_data' => $pdfData,
                    'pdf_name' => $pdfName
                ]);
                $session->update(['chair_notified_at' => now()]);
                $notifiedCount++;
            }
        }

        // Notify Rapporteur
        if ($session->session_rapporteur_id) {
            $rapporteur = \App\Models\User::find($session->session_rapporteur_id);
            if ($rapporteur) {
                $this->emailService->sendRapporteurAssignment($rapporteur, $session, [
                    'pdf_data' => $pdfData,
                    'pdf_name' => $pdfName
                ]);
                $session->update(['rapporteur_notified_at' => now()]);
                $notifiedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully sent {$notifiedCount} notifications for session: {$session->name}",
        ]);
    }

    /**
     * Notify all un-notified chairs and rapporteurs
     */
    public function notifyAllLeads(Request $request)
    {
        $sessions = ConferenceSession::where('is_active', true)->get();
        $notifiedCount = 0;

        $pdfData = $this->generateProgramPdfContent();
        $pdfName = config('conference.file_prefix') . '-Conference-Programme.pdf';

        foreach ($sessions as $session) {
            // Notify Chair if not notified
            if ($session->session_chair_id && !$session->chair_notified_at) {
                $chair = \App\Models\User::find($session->session_chair_id);
                if ($chair) {
                    $this->emailService->sendChairAssignment($chair, $session, [
                        'pdf_data' => $pdfData,
                        'pdf_name' => $pdfName
                    ]);
                    $session->update(['chair_notified_at' => now()]);
                    $notifiedCount++;
                }
            }

            // Notify Rapporteur if not notified
            if ($session->session_rapporteur_id && !$session->rapporteur_notified_at) {
                $rapporteur = \App\Models\User::find($session->session_rapporteur_id);
                if ($rapporteur) {
                    $this->emailService->sendRapporteurAssignment($rapporteur, $session, [
                        'pdf_data' => $pdfData,
                        'pdf_name' => $pdfName
                    ]);
                    $session->update(['rapporteur_notified_at' => now()]);
                    $notifiedCount++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully sent {$notifiedCount} notifications to all assigned chairs and rapporteurs.",
        ]);
    }

    /**
     * Helper to generate program PDF content
     */
    private function generateProgramPdfContent()
    {
        $programByDay = $this->buildProgramByDay($this->getProgramExportSessions());

        return Pdf::loadView('admin.conference-program.programme', compact('programByDay'))
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->output();
    }


}
