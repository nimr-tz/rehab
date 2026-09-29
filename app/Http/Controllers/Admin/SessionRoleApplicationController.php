<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConferenceSession;
use App\Models\ImportedSessionRolePerson;
use App\Models\Role;
use App\Models\SessionRoleApplication;
use App\Services\SessionTopicDetectionService;
use App\Models\User;
use App\Services\ImportedSessionRoleAssignmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class SessionRoleApplicationController extends Controller
{
    public function index(Request $request)
    {
        $baseQuery = SessionRoleApplication::query();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
            'shortlisted' => (clone $baseQuery)->where('status', 'shortlisted')->count(),
            'not_selected' => (clone $baseQuery)->where('status', 'not_selected')->count(),
            'chairs' => (clone $baseQuery)->where('role_requested', SessionRoleApplication::ROLE_CHAIR)->count(),
            'rapporteurs' => (clone $baseQuery)->where('role_requested', SessionRoleApplication::ROLE_RAPPORTEUR)->count(),
            'imported_pool' => ImportedSessionRolePerson::count(),
            'verified_imported_pool' => ImportedSessionRolePerson::whereNotNull('verified_at')->count(),
            'unmatched_imported_pool' => ImportedSessionRolePerson::whereNull('matched_user_id')->count(),
        ];

        $applications = SessionRoleApplication::with(['user', 'reviewer'])
            ->when($request->filled('role'), fn ($query) => $query->where('role_requested', $request->role))
            ->when($request->filled('theme'), fn ($query) => $query->where('preferred_theme', $request->theme))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($inner) use ($search) {
                    $inner->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('affiliation', 'like', "%{$search}%")
                                ->orWhere('institute', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.session-role-applications.index', [
            'applications' => $applications,
            'latestImport' => ImportedSessionRolePerson::latest('updated_at')->first(),
            'roles' => [
                SessionRoleApplication::ROLE_CHAIR => 'Session Chair',
                SessionRoleApplication::ROLE_RAPPORTEUR => 'Rapporteur',
            ],
            'themes' => SessionRoleApplication::themes(),
            'statuses' => ['pending', 'shortlisted', 'not_selected'],
            'stats' => $stats,
        ]);
    }

    public function exportCsv(Request $request)
    {
        $applications = SessionRoleApplication::with(['user', 'reviewer'])
            ->when($request->filled('role'), fn ($query) => $query->where('role_requested', $request->role))
            ->when($request->filled('theme'), fn ($query) => $query->where('preferred_theme', $request->theme))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim((string) $request->search);
                $query->where(function ($inner) use ($search) {
                    $inner->where('notes', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('affiliation', 'like', "%{$search}%")
                                ->orWhere('institute', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->get();

        $filename = 'session-role-applications_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($applications) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'Name',
                'Email',
                'Phone',
                'Affiliation',
                'Role',
                'Preferred Theme',
                'Attendance Confirmed',
                'Status',
                'Notes',
                'Submitted At',
                'Reviewed At',
                'Reviewed By',
            ]);

            foreach ($applications as $app) {
                fputcsv($file, [
                    $app->user?->full_name ?? '',
                    $app->user?->email ?? '',
                    $app->user?->phone ?? '',
                    $app->user?->affiliation ?: ($app->user?->institute ?? ''),
                    $app->role_label,
                    $app->preferred_theme,
                    $app->attendance_confirmed ? 'Yes' : 'No',
                    ucfirst(str_replace('_', ' ', $app->status)),
                    $app->notes ?? '',
                    $app->created_at->format('Y-m-d H:i'),
                    $app->reviewed_at ? $app->reviewed_at->format('Y-m-d H:i') : '',
                    $app->reviewer?->full_name ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function updateStatus(Request $request, SessionRoleApplication $application)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,shortlisted,not_selected',
        ]);

        $application->update([
            'status' => $validated['status'],
            'reviewed_at' => now(),
            'reviewed_by' => Auth::id(),
        ]);

        return back()->with('success', 'Application status updated.');
    }

    public function importPool()
    {
        $people = $this->persistedImportPeople();
        $sessions = $this->assignableSessions();
        $generated = $this->generateAssignmentPreview($people, $sessions);

        return view('admin.session-role-applications.import-pool', [
            'people' => $people,
            'sessions' => $sessions,
            'assignments' => $generated['assignments'],
            'assignmentOptions' => $generated['options'],
            'assignmentWarnings' => $generated['warnings'],
            'selectedRole' => SessionRoleApplication::ROLE_RAPPORTEUR,
        ]);
    }

    public function previewPool(Request $request)
    {
        $validated = $request->validate([
            'pool_file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'default_role' => 'required|in:chair,rapporteur',
        ]);

        $people = collect($this->extractPeopleFromWorkbook($validated['pool_file']->getRealPath()))
            ->map(function (array $person) use ($validated) {
                $person['default_role'] = $person['default_role'] ?? $validated['default_role'];
                $person['candidates'] = $this->candidateUsersForName(
                    $person['name'],
                    $person['institution'] ?? null
                );

                return $person;
            });

        $saved = $this->persistImportedPeople($people);

        return redirect()
            ->route('admin.session-role-applications.import-pool')
            ->with('success', "Imported and saved {$saved} Excel pool record(s). You can now verify and assign without uploading again.");
    }

    public function approveGeneratedAssignments(Request $request)
    {
        $validated = $request->validate([
            'assignments' => 'required',
        ]);

        $assignments = is_string($validated['assignments'])
            ? json_decode($validated['assignments'], true)
            : $validated['assignments'];

        if (!is_array($assignments)) {
            return back()->withErrors(['assignments' => 'The generated assignments could not be read. Please preview again.']);
        }

        $saved = 0;
        $usedByTimeSlot = [];

        foreach ($assignments as $assignment) {
            if (empty($assignment['session_id']) || empty($assignment['user_id']) || empty($assignment['role'])) {
                continue;
            }

            if (!in_array($assignment['role'], [SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR], true)) {
                continue;
            }

            $session = ConferenceSession::find($assignment['session_id']);
            $user = User::find($assignment['user_id']);

            if (!$session || !$user) {
                continue;
            }

            $currentDay = $this->sessionDay($session);
            $previewDay = $assignment['date'] ?? null;

            if ($previewDay && $currentDay && $previewDay !== $currentDay) {
                return back()->withErrors([
                    'assignments' => 'The programme changed after this preview was generated. Please upload the file and preview again before approving.',
                ]);
            }

            $timeSlot = $this->sessionTimeSlotKey($session);

            if ($timeSlot) {
                if (isset($usedByTimeSlot[$timeSlot][$user->id])) {
                    return back()->withErrors([
                        'assignments' => 'One person would receive more than one session at the same time. Please preview again before approving.',
                    ]);
                }

                $usedByTimeSlot[$timeSlot][$user->id] = true;
            }

            $this->applySessionLeadAssignment($session, $user, $assignment['role']);
            $saved++;
        }

        return redirect()
            ->route('admin.session-role-applications.import-pool')
            ->with('success', "Approved and saved {$saved} generated assignments.");
    }

    public function autoAssignImportedPool(ImportedSessionRoleAssignmentService $assignmentService)
    {
        $result = $assignmentService->applyFromVerifiedPool();

        if (empty($result['assignments'])) {
            return back()->withErrors([
                'assignments' => 'No assignments could be generated. Verify imported account links first, then try again.',
            ]);
        }

        $warningText = count($result['warnings'])
            ? ' ' . count($result['warnings']) . ' capacity warning(s) remain.'
            : '';

        return redirect()
            ->route('admin.session-role-applications.import-pool')
            ->with('success', "Regenerated and applied {$result['saved']} chair/rapporteur assignment(s) from the saved verified pool.{$warningText}");
    }

    public function assignImportedLead(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|exists:conference_sessions,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:chair,rapporteur',
        ]);

        $session = ConferenceSession::findOrFail($validated['session_id']);
        $user = User::findOrFail($validated['user_id']);

        $this->applySessionLeadAssignment($session, $user, $validated['role']);

        return back()->with(
            'success',
            "{$user->full_name} was assigned as {$validated['role']} for {$session->name}."
        );
    }

    public function verifyImportedLead(Request $request)
    {
        $validated = $request->validate([
            'imported_person_id' => 'nullable|exists:imported_session_role_people,id',
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:chair,rapporteur',
            'subtheme' => 'nullable|string|max:255',
            'source_name' => 'required|string|max:255',
            'institution' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $this->ensureSessionLeadRole($validated['role']);
        $user->assignRole($validated['role'], false, Auth::id());

        SessionRoleApplication::updateOrCreate(
            ['user_id' => $user->id],
            [
                'role_requested' => $validated['role'],
                'preferred_theme' => $this->themeFromSubtheme($validated['subtheme'] ?? null),
                'attendance_confirmed' => true,
                'status' => 'shortlisted',
                'reviewed_at' => now(),
                'reviewed_by' => Auth::id(),
                'notes' => trim(sprintf(
                    'Verified from chair/rapporteur Excel. Excel name: %s%s',
                    $validated['source_name'],
                    !empty($validated['institution']) ? '; Institution: ' . $validated['institution'] : ''
                )),
            ]
        );

        if (!empty($validated['imported_person_id'])) {
            ImportedSessionRolePerson::whereKey($validated['imported_person_id'])->update([
                'matched_user_id' => $user->id,
                'match_confidence' => 'Verified',
                'role' => $validated['role'],
                'verified_at' => now(),
                'verified_by' => Auth::id(),
            ]);
        }

        $message = "{$user->full_name} was verified and linked from the Excel pool.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()
            ->route('admin.session-role-applications.import-pool')
            ->with('success', $message);
    }

    private function persistedImportPeople()
    {
        return ImportedSessionRolePerson::with('matchedUser')
            ->orderBy('subtheme_key')
            ->orderBy('role')
            ->orderBy('source_name')
            ->get()
            ->map(function (ImportedSessionRolePerson $person) {
                $candidates = $this->candidateUsersForName($person->source_name, $person->institution);

                if ($person->matchedUser) {
                    $user = $person->matchedUser;
                    $candidates = $candidates
                        ->reject(fn ($candidate) => (int) $candidate['id'] === (int) $person->matched_user_id)
                        ->values();
                    $candidates->prepend([
                        'id' => $user->id,
                        'name' => $user->full_name,
                        'email' => $user->email,
                        'affiliation' => $user->affiliation ?: ($user->institute ?? ''),
                        'score' => 100,
                        'confidence' => $person->verified_at ? 'Verified' : ($person->match_confidence ?: 'Selected'),
                    ]);
                }

                return [
                    'imported_person_id' => $person->id,
                    'source_sheet' => $person->source_sheet ?: 'Excel',
                    'group' => $person->subtheme,
                    'name' => $person->source_name,
                    'institution' => $person->institution,
                    'region' => null,
                    'status' => $person->verified_at ? 'Verified' : null,
                    'default_role' => $person->role,
                    'normalized_name' => $person->normalized_name,
                    'verified_at' => $person->verified_at,
                    'matched_user_id' => $person->matched_user_id,
                    'candidates' => $candidates,
                ];
            });
    }

    private function persistImportedPeople($people): int
    {
        $saved = 0;

        foreach ($people as $person) {
            $role = $person['default_role'] ?? null;
            $subthemeKey = $this->subthemeAssignmentKey($person['group'] ?? null);

            if (!$role || !$subthemeKey || empty($person['normalized_name'])) {
                continue;
            }

            $candidate = !empty($person['candidates']) && $person['candidates']->isNotEmpty()
                ? $person['candidates']->first()
                : null;

            ImportedSessionRolePerson::updateOrCreate(
                [
                    'subtheme_key' => $subthemeKey,
                    'role' => $role,
                    'normalized_name' => $person['normalized_name'],
                ],
                [
                    'source_sheet' => $person['source_sheet'] ?? 'Excel',
                    'subtheme' => $person['group'] ?? null,
                    'source_name' => $person['name'],
                    'institution' => $person['institution'] ?? null,
                    'matched_user_id' => $candidate['id'] ?? null,
                    'match_confidence' => $candidate['confidence'] ?? null,
                ]
            );

            $saved++;
        }

        return $saved;
    }

    private function assignableSessions()
    {
        return ConferenceSession::query()
            ->where('is_active', true)
            ->whereIn('session_type', ['presentation', 'poster', 'panel', 'plenary', 'discussion'])
            ->orderBy('schedule_days')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'session_type', 'subtheme', 'schedule_days', 'start_time', 'room_location', 'session_chair_id', 'session_rapporteur_id']);
    }

    private function applySessionLeadAssignment(ConferenceSession $session, User $user, string $role): void
    {
        $this->ensureSessionLeadRole($role);
        $user->assignRole($role, false, Auth::id());

        $payload = [
            'updated_by' => Auth::id(),
        ];

        if ($role === SessionRoleApplication::ROLE_CHAIR) {
            $payload += [
                'session_chair' => $user->full_name,
                'session_chair_email' => $user->email,
                'session_chair_id' => $user->id,
                'chair_notified_at' => null,
                'chair_confirmed_at' => null,
            ];

            if ($this->isPosterSession($session)) {
                $payload += [
                    'session_rapporteur' => null,
                    'session_rapporteur_email' => null,
                    'session_rapporteur_id' => null,
                    'rapporteur_notified_at' => null,
                    'rapporteur_confirmed_at' => null,
                ];
            }
        } else {
            $payload += [
                'session_rapporteur' => $user->full_name,
                'session_rapporteur_email' => $user->email,
                'session_rapporteur_id' => $user->id,
                'rapporteur_notified_at' => null,
                'rapporteur_confirmed_at' => null,
            ];
        }

        $session->update($payload);
    }

    private function clearSessionLeadAssignmentsForSubthemes($sessions, array $subthemeKeys): void
    {
        $subthemeKeys = array_flip($subthemeKeys);

        foreach ($sessions as $session) {
            $key = $this->subthemeAssignmentKey($session->subtheme);

            if (!$key || !isset($subthemeKeys[$key])) {
                continue;
            }

            $session->update([
                'session_chair' => null,
                'session_chair_email' => null,
                'session_chair_id' => null,
                'chair_notified_at' => null,
                'chair_confirmed_at' => null,
                'session_rapporteur' => null,
                'session_rapporteur_email' => null,
                'session_rapporteur_id' => null,
                'rapporteur_notified_at' => null,
                'rapporteur_confirmed_at' => null,
                'updated_by' => Auth::id(),
            ]);
        }
    }

    private function verifiedImportedSubthemeKeys($people): array
    {
        return collect($people)
            ->filter(fn ($person) => !empty($person['verified_at']))
            ->map(fn ($person) => $this->subthemeAssignmentKey($person['group'] ?? null))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function generateAssignmentPreview($people, $sessions): array
    {
        $warnings = [];
        $assignments = [];
        $usedByTimeSlot = [];
        $pools = [];
        $positions = [];
        $knownSubthemeKeys = [];
        $capacityWarnings = [];

        foreach ($people as $person) {
            if (empty($person['default_role']) || empty($person['group'])) {
                continue;
            }

            if (array_key_exists('verified_at', $person) && empty($person['verified_at'])) {
                continue;
            }

            $key = $this->subthemeAssignmentKey($person['group']);
            $role = $person['default_role'];

            if (!$key || !in_array($role, [SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR], true)) {
                continue;
            }

            $knownSubthemeKeys[$key] = true;

            if (empty($person['candidates']) || $person['candidates']->isEmpty()) {
                continue;
            }

            $candidate = $person['candidates']->first();

            $pools[$role][$key][] = [
                'user_id' => $candidate['id'],
                'user_name' => $candidate['name'],
                'user_email' => $candidate['email'],
                'confidence' => $candidate['confidence'],
                'source_name' => $person['name'],
                'institution' => $person['institution'] ?? null,
                'subtheme' => $person['group'],
            ];
        }

        foreach ([SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR] as $role) {
            foreach (($pools[$role] ?? []) as $key => $pool) {
                $pools[$role][$key] = collect($pool)
                    ->unique('user_id')
                    ->shuffle()
                    ->values()
                    ->all();
                $positions[$role][$key] = 0;
            }
        }

        $posterChairPool = collect($pools[SessionRoleApplication::ROLE_CHAIR] ?? [])
            ->flatten(1)
            ->unique('user_id')
            ->shuffle()
            ->values()
            ->all();
        $posterChairPosition = 0;

        foreach ($sessions as $session) {
            $day = $this->sessionDay($session);
            $timeSlot = $this->sessionTimeSlotKey($session);
            $key = $this->subthemeAssignmentKey($session->subtheme);

            if (!$day || !$timeSlot) {
                continue;
            }

            if ($this->isPosterSession($session)) {
                if (empty($posterChairPool)) {
                    $warnings[] = "No chair pool found for poster session: {$session->name}.";
                    continue;
                }

                $choice = $this->nextAvailablePoolPerson($posterChairPool, $posterChairPosition, $usedByTimeSlot[$timeSlot] ?? []);

                if (!$choice) {
                    $capacityWarnings[$day]['POSTER'][SessionRoleApplication::ROLE_CHAIR] = ($capacityWarnings[$day]['POSTER'][SessionRoleApplication::ROLE_CHAIR] ?? 0) + 1;
                    continue;
                }

                $posterChairPosition = $choice['next_position'];
                $usedByTimeSlot[$timeSlot][$choice['person']['user_id']] = true;

                $assignments[] = [
                    'session_id' => $session->id,
                    'session_name' => $session->name,
                    'session_type' => $session->session_type,
                    'date' => $day,
                    'time' => optional($session->start_time)->format('H:i'),
                    'subtheme_key' => 'POSTER',
                    'role' => SessionRoleApplication::ROLE_CHAIR,
                    'user_id' => $choice['person']['user_id'],
                    'user_name' => $choice['person']['user_name'],
                    'user_email' => $choice['person']['user_email'],
                    'source_name' => $choice['person']['source_name'],
                    'confidence' => $choice['person']['confidence'],
                ];

                continue;
            }

            if (!$key || !isset($knownSubthemeKeys[$key])) {
                continue;
            }

            foreach ([SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR] as $role) {
                $pool = $pools[$role][$key] ?? [];

                if (empty($pool)) {
                    $warnings[] = "No {$role} pool found for {$key} session: {$session->name}.";
                    continue;
                }

                $choice = $this->nextAvailablePoolPerson($pool, $positions[$role][$key], $usedByTimeSlot[$timeSlot] ?? []);

                if (!$choice) {
                    $capacityWarnings[$day][$key][$role] = ($capacityWarnings[$day][$key][$role] ?? 0) + 1;
                    continue;
                }

                $positions[$role][$key] = $choice['next_position'];
                $usedByTimeSlot[$timeSlot][$choice['person']['user_id']] = true;

                $assignments[] = [
                    'session_id' => $session->id,
                    'session_name' => $session->name,
                    'session_type' => $session->session_type,
                    'date' => $day,
                    'time' => optional($session->start_time)->format('H:i'),
                    'subtheme_key' => $key,
                    'role' => $role,
                    'user_id' => $choice['person']['user_id'],
                    'user_name' => $choice['person']['user_name'],
                    'user_email' => $choice['person']['user_email'],
                    'source_name' => $choice['person']['source_name'],
                    'confidence' => $choice['person']['confidence'],
                ];
            }
        }

        foreach ($capacityWarnings as $day => $subthemes) {
            foreach ($subthemes as $key => $roles) {
                foreach ($roles as $role => $count) {
                    $peopleCount = $key === 'POSTER' && $role === SessionRoleApplication::ROLE_CHAIR
                        ? count($posterChairPool)
                        : count($pools[$role][$key] ?? []);
                    $warnings[] = "{$key} on {$day}: {$count} {$role} assignment(s) could not be generated because {$peopleCount} matched {$role}(s) were already used at the same time.";
                }
            }
        }

        return [
            'assignments' => $assignments,
            'options' => $pools,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function nextAvailablePoolPerson(array $pool, int $position, array $usedAtTime): ?array
    {
        $count = count($pool);

        for ($offset = 0; $offset < $count; $offset++) {
            $index = ($position + $offset) % $count;
            $person = $pool[$index];

            if (!isset($usedAtTime[$person['user_id']])) {
                return [
                    'person' => $person,
                    'next_position' => ($index + 1) % $count,
                ];
            }
        }

        return null;
    }

    private function sessionDay(ConferenceSession $session): ?string
    {
        $days = $session->schedule_days;

        if (is_array($days) && !empty($days[0])) {
            return (string) $days[0];
        }

        return null;
    }

    private function isPosterSession(ConferenceSession $session): bool
    {
        return $session->session_type === 'poster';
    }

    private function sessionTimeSlotKey(ConferenceSession $session): ?string
    {
        $day = $this->sessionDay($session);

        if (!$day || !$session->start_time) {
            return null;
        }

        return implode('|', [
            $day,
            optional($session->start_time)->format('H:i'),
        ]);
    }

    private function extractPeopleFromWorkbook(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $people = [];

        foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
            $sheetName = $worksheet->getTitle();
            $highestRow = $worksheet->getHighestDataRow();
            $matrixSubtheme = null;

            for ($row = 1; $row <= $highestRow; $row++) {
                $first = trim((string) $worksheet->getCell("A{$row}")->getFormattedValue());
                $second = trim((string) $worksheet->getCell("B{$row}")->getFormattedValue());
                $third = trim((string) $worksheet->getCell("C{$row}")->getFormattedValue());
                $fourth = trim((string) $worksheet->getCell("D{$row}")->getFormattedValue());
                $fifth = trim((string) $worksheet->getCell("E{$row}")->getFormattedValue());
                $sixth = trim((string) $worksheet->getCell("F{$row}")->getFormattedValue());

                if ($this->looksLikeChairRapporteurMatrixHeader($first, $second, $third, $fifth, $sixth)) {
                    continue;
                }

                if ($first !== '' && $second === '' && $fifth === '' && !$this->looksLikeHeader($first)) {
                    $matrixSubtheme = $first;
                    continue;
                }

                if ($this->looksLikeChairRapporteurMatrixRow($first, $second, $third, $fifth, $sixth)) {
                    $matrixSubtheme = $first !== '' ? $first : ($matrixSubtheme ?? null);
                    $subtheme = $matrixSubtheme ?: null;

                    if ($second !== '') {
                        $this->appendImportedPerson($people, [
                            'source_sheet' => $sheetName,
                            'group' => $subtheme,
                            'raw_name' => $second,
                            'name' => $this->cleanImportedPersonName($second),
                            'institution' => $third,
                            'region' => null,
                            'status' => null,
                            'default_role' => SessionRoleApplication::ROLE_CHAIR,
                        ]);
                    }

                    if ($fifth !== '') {
                        $this->appendImportedPerson($people, [
                            'source_sheet' => $sheetName,
                            'group' => $subtheme,
                            'raw_name' => $fifth,
                            'name' => $this->cleanImportedPersonName($fifth),
                            'institution' => $sixth,
                            'region' => null,
                            'status' => null,
                            'default_role' => SessionRoleApplication::ROLE_RAPPORTEUR,
                        ]);
                    }

                    continue;
                }

                $name = null;
                $institution = null;
                $region = null;
                $group = null;
                $status = null;

                if ($sheetName === 'Scientific Committee' && ctype_digit($first) && $second !== '') {
                    $name = $second;
                    $institution = $third;
                    $region = $fourth;
                } elseif (in_array($sheetName, ['Key Speakers', 'Panelists'], true) && ctype_digit($second) && $third !== '') {
                    $name = $this->nameBeforeRoleDescription($third);
                    $institution = $this->institutionFromDescription($third);
                    $region = $fourth;
                    $group = $first ?: null;
                    $status = $fifth ?: null;
                }

                if (!$name || $this->looksLikeHeader($name)) {
                    continue;
                }

                $normalized = $this->normalizePersonName($name);
                if ($normalized === '') {
                    continue;
                }

                $this->appendImportedPerson($people, [
                    'source_sheet' => $sheetName,
                    'group' => $group,
                    'raw_name' => $name,
                    'name' => $this->displayName($name),
                    'institution' => $institution,
                    'region' => $region,
                    'status' => $status,
                ]);
            }
        }

        return array_values($people);
    }

    private function appendImportedPerson(array &$people, array $person): void
    {
        if (($person['name'] ?? '') === '' || $this->looksLikeHeader((string) $person['name'])) {
            return;
        }

        $normalized = $this->normalizePersonName($person['name']);
        if ($normalized === '') {
            return;
        }

        $role = $person['default_role'] ?? 'person';
        $key = ($person['source_sheet'] ?? 'Sheet') . '|' . ($person['group'] ?? '') . '|' . $role . '|' . $normalized;

        $people[$key] = array_merge($person, [
            'normalized_name' => $normalized,
        ]);
    }

    private function candidateUsersForName(string $name, ?string $institution)
    {
        $normalizedName = $this->normalizePersonName($name);
        $terms = collect(explode(' ', $normalizedName))
            ->filter(fn ($term) => strlen($term) >= 3)
            ->values();

        if ($terms->isEmpty()) {
            return collect();
        }

        $users = User::query()
            ->where(function ($query) use ($terms) {
                foreach ($terms as $term) {
                    $query->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                }
            })
            ->limit(25)
            ->get(['id', 'title', 'first_name', 'last_name', 'email', 'affiliation', 'institute']);

        return $users
            ->map(function (User $user) use ($normalizedName, $institution, $terms) {
                $candidateName = $this->normalizePersonName($user->full_name);
                $score = 0;

                if ($candidateName === $normalizedName) {
                    $score += 100;
                } elseif (str_contains($candidateName, $normalizedName) || str_contains($normalizedName, $candidateName)) {
                    $score += 80;
                } else {
                    foreach ($terms as $term) {
                        if (str_contains($candidateName, $term)) {
                            $score += 18;
                        }
                    }
                }

                $affiliation = $user->affiliation ?: ($user->institute ?? '');
                if ($institution && $affiliation && str_contains(
                    $this->normalizeLooseText($affiliation),
                    $this->normalizeLooseText($institution)
                )) {
                    $score += 10;
                }

                return [
                    'id' => $user->id,
                    'name' => $user->full_name,
                    'email' => $user->email,
                    'affiliation' => $affiliation,
                    'score' => $score,
                    'confidence' => $score >= 100 ? 'Exact' : ($score >= 70 ? 'Likely' : 'Possible'),
                ];
            })
            ->filter(fn ($candidate) => $candidate['score'] >= 30)
            ->sortByDesc('score')
            ->take(5)
            ->values();
    }

    private function ensureSessionLeadRole(string $roleName): void
    {
        Role::firstOrCreate(
            ['name' => $roleName],
            [
                'display_name' => $roleName === 'chair' ? 'Session Chairperson' : 'Session Rapporteur',
                'description' => $roleName === 'chair'
                    ? 'Moderates and leads conference sessions'
                    : 'Documents and reports on conference sessions',
                'color' => $roleName === 'chair' ? '#8b5cf6' : '#10b981',
                'icon' => $roleName === 'chair' ? 'user-group' : 'clipboard-list',
                'is_active' => true,
            ]
        );
    }

    private function normalizePersonName(?string $name): string
    {
        $name = Str::lower(trim((string) $name));
        $name = preg_replace('/\b(dr|prof|mr|mrs|ms|miss)\.?\b/i', '', $name);
        $name = preg_replace('/[^a-z\s]/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return trim((string) $name);
    }

    private function normalizeLooseText(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    /**
     * Topic code (e.g. 'HBR') for a free-text topic name or code.
     */
    /**
     * Topic code (e.g. 'HBR') for a free-text topic name or code.
     */
    /**
     * Topic code (e.g. 'HBR') for a free-text topic name or code.
     */
    private function subthemeAssignmentKey(?string $value): ?string
    {
        $value = $this->normalizeLooseText($value);

        if ($value === '') {
            return null;
        }

        foreach (config('conference.subtheme_prefixes', []) as $topic => $prefix) {
            if ($value === $this->normalizeLooseText($topic) || $value === Str::lower($prefix)) {
                return $prefix;
            }
        }

        return Str::upper($value);
    }

    private function themeFromSubtheme(?string $value): string
    {
        $key = $this->subthemeAssignmentKey($value);

        return SessionTopicDetectionService::codes()[$key] ?? SessionRoleApplication::themes()[0];
    }

    private function nameBeforeRoleDescription(string $value): string
    {
        $parts = preg_split('/\s(?:—|–|-)\s|,/', $value, 2);

        return trim($parts[0] ?? $value);
    }

    private function institutionFromDescription(string $value): ?string
    {
        if (!preg_match('/(?:—|–|-)\s*(.+)$/u', $value, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    private function displayName(string $name): string
    {
        return trim(preg_replace('/\s+/', ' ', $name));
    }

    private function cleanImportedPersonName(string $name): string
    {
        $name = $this->displayName($name);
        $name = preg_replace('/\s*(?:-|\x{2013}|\x{2014})\s*wanted\s+to\s+chair\b.*$/iu', '', $name);

        return $this->displayName((string) $name);
    }

    private function looksLikeChairRapporteurMatrixHeader(
        string $first,
        string $second,
        string $third,
        string $fifth,
        string $sixth
    ): bool {
        $combined = $this->normalizeLooseText("{$first} {$second} {$third} {$fifth} {$sixth}");

        return str_contains($combined, 'subtheme')
            || (str_contains($combined, 'chairperson') && str_contains($combined, 'rapporteur'));
    }

    private function looksLikeChairRapporteurMatrixRow(
        string $first,
        string $second,
        string $third,
        string $fifth,
        string $sixth
    ): bool {
        if ($second === '' && $fifth === '') {
            return false;
        }

        if ($third !== '' || $sixth !== '') {
            return true;
        }

        return $first !== '' && ($second !== '' || $fifth !== '');
    }

    private function looksLikeHeader(string $name): bool
    {
        return preg_match('/name|address|department|institution|speaker|panelist|participant/i', $name) === 1;
    }
}
