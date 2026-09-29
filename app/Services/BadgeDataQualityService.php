<?php

namespace App\Services;

use App\Models\User;
use App\Models\GroupMember;
use Illuminate\Support\Facades\Schema;

/**
 * Scans badge data for quality issues before printing.
 * Flags email-in-name, all-caps, missing institution, institute variants, etc.
 */
class BadgeDataQualityService
{
    // Known institute name variants → canonical form
    // Add more as needed for your conference audience
    private array $instituteVariants = [
        // WHO variants
        'who'                                              => 'World Health Organization',
        'w.h.o'                                            => 'World Health Organization',
        'w.h.o.'                                           => 'World Health Organization',
        'world health organisation'                        => 'World Health Organization',
        // MUHAS variants
        'muhas'                                            => 'Muhimbili University of Health and Allied Sciences',
        'muhimbili university'                             => 'Muhimbili University of Health and Allied Sciences',
        'muhimbili univ.'                                  => 'Muhimbili University of Health and Allied Sciences',
        // Muhimbili National Hospital
        'mnh'                                              => 'Muhimbili National Hospital',
        'muhimbili hospital'                               => 'Muhimbili National Hospital',
        // KCMC
        'kcmc'                                             => 'Kilimanjaro Christian Medical Centre',
        'kilimanjaro christian medical center'             => 'Kilimanjaro Christian Medical Centre',
        // UDSM
        'udsm'                                            => 'University of Dar es Salaam',
        'univ. of dar es salaam'                           => 'University of Dar es Salaam',
        // APOPO
        'apopo'                                            => 'APOPO',
        // MNH
        // CDC
        'cdc'                                              => 'Centers for Disease Control and Prevention',
        'u.s. cdc'                                         => 'Centers for Disease Control and Prevention',
        // USAID
        'usaid'                                            => 'United States Agency for International Development',
        // MOH Tanzania
        'moh'                                              => 'Ministry of Health',
        'ministry of health tz'                            => 'Ministry of Health',
        // TFDA
        'tfda'                                             => 'Tanzania Food and Drugs Authority',
        // COSTECH
        'costech'                                          => 'Commission for Science and Technology',
    ];

    /**
     * Analyse a single record and return an array of flags.
     * Each flag: ['type', 'severity', 'field', 'value', 'message', 'suggestion'?]
     */
    public function analyzeRecord(string $name, ?string $institution): array
    {
        $flags = [];

        $name = trim((string) $name);
        $institution = trim((string) $institution);

        // --- Name checks ---
        if ($name !== '') {
            // Email in name
            if (str_contains($name, '@') || preg_match('/\b[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}\b/i', $name)) {
                $flags[] = [
                    'type'     => 'email_in_name',
                    'severity' => 'critical',
                    'field'    => 'name',
                    'value'    => $name,
                    'message'  => 'Name field contains an email address.',
                ];
            }

            // All-caps name (3+ chars)
            if (strlen($name) > 3 && $name === strtoupper($name) && ctype_alpha(str_replace([' ', '.', '-'], '', $name))) {
                $flags[] = [
                    'type'     => 'all_caps_name',
                    'severity' => 'medium',
                    'field'    => 'name',
                    'value'    => $name,
                    'message'  => 'Name is all uppercase — may print poorly.',
                    'suggestion' => ucwords(strtolower($name)),
                ];
            }

            // Single token (no space) that's very short
            if (!str_contains($name, ' ') && strlen($name) < 3) {
                $flags[] = [
                    'type'     => 'name_too_short',
                    'severity' => 'high',
                    'field'    => 'name',
                    'value'    => $name,
                    'message'  => 'Name is too short — likely incomplete.',
                ];
            }

            // Numeric characters dominate the name
            $digits = strlen(preg_replace('/\D/', '', $name));
            if ($digits > 0 && $digits >= strlen(preg_replace('/\s/', '', $name)) / 2) {
                $flags[] = [
                    'type'     => 'numeric_name',
                    'severity' => 'high',
                    'field'    => 'name',
                    'value'    => $name,
                    'message'  => 'Name contains mostly numeric characters.',
                ];
            }

            // URL-like content in name
            if (preg_match('#https?://#i', $name) || preg_match('/www\./i', $name)) {
                $flags[] = [
                    'type'     => 'url_in_name',
                    'severity' => 'critical',
                    'field'    => 'name',
                    'value'    => $name,
                    'message'  => 'Name contains a URL.',
                ];
            }
        }

        // --- Institution checks ---
        if ($institution !== '') {
            // Email in institution
            if (str_contains($institution, '@') || preg_match('/\b[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}\b/i', $institution)) {
                $flags[] = [
                    'type'     => 'email_in_institution',
                    'severity' => 'critical',
                    'field'    => 'institution',
                    'value'    => $institution,
                    'message'  => 'Institution field contains an email address.',
                ];
            }

            // URL in institution
            if (preg_match('#https?://#i', $institution) || preg_match('/www\./i', $institution)) {
                $flags[] = [
                    'type'     => 'url_in_institution',
                    'severity' => 'critical',
                    'field'    => 'institution',
                    'value'    => $institution,
                    'message'  => 'Institution field contains a URL.',
                ];
            }

            // Known variant → suggest canonical form
            $canonical = $this->detectInstituteVariant($institution);
            if ($canonical !== null && strtolower($canonical) !== strtolower($institution)) {
                $flags[] = [
                    'type'       => 'institute_variant',
                    'severity'   => 'low',
                    'field'      => 'institution',
                    'value'      => $institution,
                    'message'    => "Non-standard institution name detected.",
                    'suggestion' => $canonical,
                ];
            }

            // Pure acronym (all caps, ≤6 chars, no spaces) — informational
            if (strlen($institution) <= 6 && $institution === strtoupper($institution) && !str_contains($institution, ' ') && ctype_alpha($institution)) {
                // Only flag if we don't already have a variant suggestion for it
                $alreadyFlagged = collect($flags)->contains('type', 'institute_variant');
                if (!$alreadyFlagged) {
                    $flags[] = [
                        'type'     => 'acronym_only',
                        'severity' => 'low',
                        'field'    => 'institution',
                        'value'    => $institution,
                        'message'  => 'Institution is an acronym — consider using the full name for badges.',
                    ];
                }
            }
        } else {
            // Missing institution
            $flags[] = [
                'type'     => 'missing_institution',
                'severity' => 'low',
                'field'    => 'institution',
                'value'    => '',
                'message'  => 'No institution/affiliation recorded.',
            ];
        }

        return $flags;
    }

    /**
     * Scan a User model and persist flags.
     */
    public function scanUser(User $user): array
    {
        $name = trim(($user->title ? $user->title . ' ' : '') . $user->first_name . ' ' . $user->last_name);
        $institution = $user->institute ?: $user->affiliation;

        $flags = $this->analyzeRecord($name, $institution);

        $user->update([
            'quality_flags'   => $flags ?: null,
            'quality_flagged' => !empty($flags),
        ]);

        return $flags;
    }

    /**
     * Scan a GroupMember and persist flags.
     */
    public function scanGroupMember(GroupMember $member): array
    {
        $flags = $this->analyzeRecord($member->full_name, $member->institution);

        $member->update([
            'quality_flags'   => $flags ?: null,
            'quality_flagged' => !empty($flags),
        ]);

        return $flags;
    }

    /**
     * Perform a quick in-memory check without persisting — used for pre-print gate.
     */
    public function quickCheck(string $name, ?string $institution): array
    {
        return $this->analyzeRecord($name, $institution);
    }

    /**
     * Return the canonical institute name if the given string matches a known variant.
     */
    public function detectInstituteVariant(string $institution): ?string
    {
        $needle = strtolower(trim($institution));
        return $this->instituteVariants[$needle] ?? null;
    }

    /**
     * Highest severity across an array of flags.
     */
    public function worstSeverity(array $flags): ?string
    {
        $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $worst = null;
        foreach ($flags as $flag) {
            $s = $flag['severity'] ?? 'low';
            if ($worst === null || ($order[$s] ?? 99) < ($order[$worst] ?? 99)) {
                $worst = $s;
            }
        }
        return $worst;
    }

    /**
     * Collect all flagged records from all badge-holder tables.
     * Returns a flat array of ['type', 'id', 'name', 'institution', 'flags', 'worst_severity'].
     */
    public function allFlaggedRecords(): array
    {
        $results = [];

        User::where('quality_flagged', true)->get()->each(function (User $u) use (&$results) {
            $flags = is_string($u->quality_flags) ? json_decode($u->quality_flags, true) : ($u->quality_flags ?? []);
            $results[] = [
                'model_type'  => 'user',
                'id'          => $u->id,
                'name'        => trim(($u->title ? $u->title . ' ' : '') . $u->first_name . ' ' . $u->last_name),
                'institution' => $u->institute ?: $u->affiliation,
                'email'       => $u->email,
                'flags'       => $flags,
                'worst'       => $this->worstSeverity($flags),
                'paid'        => $u->isPaid(),
            ];
        });

        GroupMember::where('quality_flagged', true)->get()->each(function (GroupMember $m) use (&$results) {
            $flags = is_string($m->quality_flags) ? json_decode($m->quality_flags, true) : ($m->quality_flags ?? []);
            $results[] = [
                'model_type'  => 'group_member',
                'id'          => $m->id,
                'name'        => $m->full_name,
                'institution' => $m->institution,
                'email'       => $m->email,
                'flags'       => $flags,
                'worst'       => $this->worstSeverity($flags),
                'paid'        => in_array($m->groupRegistration?->payment_status ?? '', ['verified', 'waived']),
            ];
        });

        // Sort: critical first
        $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($results, fn($a, $b) => ($order[$a['worst']] ?? 99) <=> ($order[$b['worst']] ?? 99));

        return $results;
    }

    /**
     * Count all flagged records across tables.
     */
    public function flaggedCount(): int
    {
        $count = User::where('quality_flagged', true)->count();
        $count += GroupMember::where('quality_flagged', true)->count();
        return $count;
    }

    /**
     * Run a full registry scan (can be slow; run asynchronously for large datasets).
     */
    public function scanAll(): array
    {
        $summary = ['users' => 0, 'group_members' => 0, 'total_flagged' => 0];

        User::chunk(200, function ($users) use (&$summary) {
            foreach ($users as $user) {
                $flags = $this->scanUser($user);
                if (!empty($flags)) {
                    $summary['users']++;
                    $summary['total_flagged']++;
                }
            }
        });

        GroupMember::chunk(200, function ($members) use (&$summary) {
            foreach ($members as $member) {
                $flags = $this->scanGroupMember($member);
                if (!empty($flags)) {
                    $summary['group_members']++;
                    $summary['total_flagged']++;
                }
            }
        });

        return $summary;
    }
}
