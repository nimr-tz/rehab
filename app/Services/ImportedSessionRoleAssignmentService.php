<?php

namespace App\Services;

use App\Models\ConferenceSession;
use App\Models\ImportedSessionRolePerson;
use App\Models\Role;
use App\Models\SessionRoleApplication;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ImportedSessionRoleAssignmentService
{
    public function applyFromVerifiedPool(?Collection $sessions = null): array
    {
        $people = $this->verifiedPeople();
        $sessions = $sessions ?? $this->assignableSessions();
        $generated = $this->generateAssignments($people, $sessions);

        if (empty($generated['assignments'])) {
            return [
                'saved' => 0,
                'verified_people' => $people->count(),
                'assignments' => [],
                'warnings' => $generated['warnings'],
            ];
        }

        $this->clearSessionLeadAssignmentsForSubthemes($sessions, $this->verifiedSubthemeKeys($people));
        $this->clearPosterSessionLeadAssignments($sessions);

        $saved = 0;

        foreach ($generated['assignments'] as $assignment) {
            $session = ConferenceSession::find($assignment['session_id'] ?? null);
            $user = User::find($assignment['user_id'] ?? null);

            if (!$session || !$user || empty($assignment['role'])) {
                continue;
            }

            $this->applySessionLeadAssignment($session, $user, $assignment['role']);
            $saved++;
        }

        return [
            'saved' => $saved,
            'verified_people' => $people->count(),
            'assignments' => $generated['assignments'],
            'warnings' => $generated['warnings'],
        ];
    }

    private function verifiedPeople(): Collection
    {
        return ImportedSessionRolePerson::with('matchedUser')
            ->whereNotNull('verified_at')
            ->whereNotNull('matched_user_id')
            ->orderBy('subtheme_key')
            ->orderBy('role')
            ->orderBy('source_name')
            ->get()
            ->filter(fn (ImportedSessionRolePerson $person) => $person->matchedUser)
            ->values();
    }

    private function assignableSessions(): Collection
    {
        return ConferenceSession::query()
            ->where('is_active', true)
            ->whereIn('session_type', ['presentation', 'poster', 'panel', 'plenary', 'discussion'])
            ->orderBy('schedule_days')
            ->orderBy('start_time')
            ->orderBy('sort_order')
            ->get(['id', 'name', 'session_type', 'subtheme', 'schedule_days', 'start_time', 'room_location', 'session_chair_id', 'session_rapporteur_id']);
    }

    private function generateAssignments(Collection $people, Collection $sessions): array
    {
        $warnings = [];
        $assignments = [];
        $usedByTimeSlot = [];
        $pools = [];
        $positions = [];
        $knownSubthemeKeys = [];
        $capacityWarnings = [];

        foreach ($people as $person) {
            $key = $this->subthemeAssignmentKey($person->subtheme);
            $role = $person->role;
            $user = $person->matchedUser;

            if (!$key || !$user || !in_array($role, [SessionRoleApplication::ROLE_CHAIR, SessionRoleApplication::ROLE_RAPPORTEUR], true)) {
                continue;
            }

            $knownSubthemeKeys[$key] = true;

            $pools[$role][$key][] = [
                'user_id' => $user->id,
                'user_name' => $user->full_name,
                'user_email' => $user->email,
                'confidence' => $person->match_confidence ?: 'Verified',
                'source_name' => $person->source_name,
                'institution' => $person->institution,
                'subtheme' => $person->subtheme,
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
            'warnings' => array_values(array_unique($warnings)),
        ];
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

    private function clearSessionLeadAssignmentsForSubthemes(Collection $sessions, array $subthemeKeys): void
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

    private function clearPosterSessionLeadAssignments(Collection $sessions): void
    {
        foreach ($sessions as $session) {
            if (!$this->isPosterSession($session)) {
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

    private function verifiedSubthemeKeys(Collection $people): array
    {
        return $people
            ->map(fn (ImportedSessionRolePerson $person) => $this->subthemeAssignmentKey($person->subtheme))
            ->filter()
            ->unique()
            ->values()
            ->all();
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

    private function normalizeLooseText(?string $value): string
    {
        $value = Str::lower(trim((string) $value));
        $value = preg_replace('/[^a-z0-9\s]/', ' ', $value);
        $value = preg_replace('/\s+/', ' ', $value);

        return trim((string) $value);
    }

    private function subthemeAssignmentKey(?string $value): ?string
    {
        $value = $this->normalizeLooseText($value);

        if ($value === '') {
            return null;
        }

        return match (true) {
            str_contains($value, 'amr') || str_contains($value, 'antimicrobial') => 'AMR',
            str_contains($value, 'ai dig') || str_contains($value, 'artificial intelligence') || str_contains($value, 'digital') => 'AI/DIG',
            str_contains($value, 'hiv') => 'HIV',
            str_contains($value, 'malaria') || $value === 'mal' => 'MAL',
            str_contains($value, 'tuberculosis') || $value === 'tb' => 'TB',
            str_contains($value, 'ntd') || str_contains($value, 'neglected') || str_contains($value, 'one health') || str_contains($value, 'climate') => 'NTD/OH',
            str_contains($value, 'ncd') || str_contains($value, 'non communicable') || str_contains($value, 'cancer') || str_contains($value, 'mental') || str_contains($value, 'injur') => 'NCD/CANC/MENT/INJ',
            str_contains($value, 'hss') || str_contains($value, 'health system') || str_contains($value, 'health security') => 'HSS',
            str_contains($value, 'tcim') || str_contains($value, 'traditional') || str_contains($value, 'complementary') || str_contains($value, 'integrative') => 'TCIM',
            str_contains($value, 'mch') || str_contains($value, 'maternal') || str_contains($value, 'child') || str_contains($value, 'reproductive') => 'MCH',
            default => Str::upper($value),
        };
    }
}
