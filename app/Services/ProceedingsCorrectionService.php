<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\ProceedingsCorrectionSetting;
use App\Models\RevisionHistory;
use App\Models\User;
use App\Support\AbstractBodyFormatter;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Author-facing camera-ready corrections for accepted abstracts.
 *
 * Authors get a narrow window to fix the details that end up in print — title,
 * abstract text, keywords, their own name/affiliation and their co-authors —
 * without going anywhere near the fields that drive conference codes and
 * scheduling.
 *
 * Deliberately NOT routed through AbstractStatusService: an accepted abstract
 * may only transition to rejected/withdrawn, and re-running the accept path
 * would fire ConferenceCodeAssignmentService::onAbstractAccepted() and resync
 * codes. A correction is a plain field update on a row that stays 'accepted'.
 */
class ProceedingsCorrectionService
{
    /**
     * Fields an author may change once their abstract is accepted.
     *
     * Withheld on purpose:
     *   subtheme          — moves the abstract between book sections; admin-only
     *                       (there is already a subtheme-changed notification path)
     *   presentation_mode — oral/poster drives session scheduling and book ordering
     *   conference_code / status / session_id — never author-editable
     */
    public const EDITABLE_FIELDS = [
        'title',
        'description',
        'keywords',
        'author_name',
        'author_institute',
        'coauthors',
        // The author's own consent to having their full abstract published in
        // the Conference Proceedings volume. This is the only field here that
        // changes whether the abstract is published at all, rather than how.
        'include_in_proceedings',
    ];

    public function validationRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
            'keywords' => 'required|string|max:500',
            'author_name' => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'coauthors' => 'nullable|array|max:50',
            'coauthors.*.name' => 'nullable|string|max:255',
            'coauthors.*.institute' => 'nullable|string|max:255',
            'include_in_proceedings' => 'required|boolean',
        ];
    }

    // ─── Window ──────────────────────────────────────────────────────────────

    public function supportsSettings(): bool
    {
        return Schema::hasTable('proceedings_correction_settings');
    }

    public function getSetting(): ?ProceedingsCorrectionSetting
    {
        if (! $this->supportsSettings()) {
            return null;
        }

        return ProceedingsCorrectionSetting::query()->firstOrCreate(['id' => 1], [
            'is_open' => false,
            'closes_at' => null,
            'updated_by' => null,
        ]);
    }

    public function closesAt(): ?Carbon
    {
        return $this->getSetting()?->closes_at;
    }

    /**
     * Corrections are open when an admin has switched them on and any auto-close
     * moment has not yet passed.
     */
    public function isOpen(): bool
    {
        $setting = $this->getSetting();

        if (! $setting || ! $setting->is_open) {
            return false;
        }

        return $setting->closes_at === null || now()->lte($setting->closes_at);
    }

    public function open(?Carbon $closesAt = null, ?int $userId = null): ?ProceedingsCorrectionSetting
    {
        if (! $this->supportsSettings()) {
            return null;
        }

        $setting = $this->getSetting();
        $setting->update([
            'is_open' => true,
            'closes_at' => $closesAt,
            'updated_by' => $userId,
        ]);

        return $setting->fresh();
    }

    public function close(?int $userId = null): ?ProceedingsCorrectionSetting
    {
        if (! $this->supportsSettings()) {
            return null;
        }

        $setting = $this->getSetting();
        $setting->update([
            'is_open' => false,
            'updated_by' => $userId,
        ]);

        return $setting->fresh();
    }

    public function closedMessage(): string
    {
        $closesAt = $this->closesAt();

        if ($closesAt && now()->gt($closesAt)) {
            $when = $closesAt->timezone(config('app.timezone'))->format('F d, Y \a\t H:i');

            return "The window for proceedings corrections closed on {$when} EAT. "
                .'Contact the secretariat if your entry still needs a change.';
        }

        return 'Proceedings corrections are not open at the moment. '
            .'You will be notified when you can review your entry for the abstract book.';
    }

    // ─── Eligibility ─────────────────────────────────────────────────────────

    /**
     * Only the submitting account may correct its own accepted abstract, and
     * only while the window is open.
     */
    public function canCorrect(AbstractSubmission $abstract, ?User $user): bool
    {
        if ($user === null || ! $this->isEligible($abstract)) {
            return false;
        }

        if ($this->canAdminister($user)) {
            return true;
        }

        return $abstract->user_id === $user->id && $this->isOpen();
    }

    /**
     * Admins can correct any entry on an author's behalf, at any time —
     * including after the author window has closed, which is exactly when
     * they are needed for authors who never got round to it.
     */
    public function canAdminister(?User $user): bool
    {
        return $user !== null && $user->hasAnyRole(['admin', 'scientific_admin']);
    }

    /**
     * Whether this abstract is one that appears in the book at all. Mirrors the
     * abstract book's own selection query (accepted + has a conference code).
     */
    public function isEligible(AbstractSubmission $abstract): bool
    {
        return $abstract->status === 'accepted' && ! empty($abstract->conference_code);
    }

    /**
     * Accepted abstracts belonging to a user that are correctable right now.
     */
    public function correctableFor(User $user)
    {
        return AbstractSubmission::query()
            ->where('user_id', $user->id)
            ->where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->orderBy('conference_code')
            ->get();
    }

    // ─── Write ───────────────────────────────────────────────────────────────

    /**
     * Apply an author's correction and record the before/after diff.
     *
     * Returns the list of changed field names (empty when nothing differed).
     */
    public function apply(AbstractSubmission $abstract, array $input, ?User $user = null): array
    {
        $payload = $this->normalizePayload($input);
        $before = $this->snapshot($abstract);

        $changed = [];
        foreach ($payload as $field => $value) {
            if (! $this->valuesMatch($before[$field] ?? null, $value)) {
                $changed[] = $field;
            }
        }

        if (empty($changed)) {
            return [];
        }

        DB::transaction(function () use ($abstract, $payload, $before, $changed, $user) {
            // forceFill + save, never AbstractStatusService: status stays 'accepted'
            // and no code reassignment is triggered.
            $abstract->forceFill($payload + [
                'proceedings_corrected_at' => now(),
                'proceedings_corrected_by' => $user?->id,
            ])->save();

            $after = $this->snapshot($abstract->fresh());

            $onBehalf = $user !== null && $user->id !== $abstract->user_id;

            RevisionHistory::createEntry([
                'abstract_id' => $abstract->id,
                'action' => 'proceedings_correction',
                'user_id' => $user?->id,
                'round' => $abstract->revision_round ?: 1,
                'author_response' => $onBehalf
                    ? "Proceedings entry corrected by an administrator on the author's behalf."
                    : 'Author corrected their entry for the conference proceedings.',
                'metadata' => [
                    'changed_fields' => $changed,
                    'before' => array_intersect_key($before, array_flip($changed)),
                    'after' => array_intersect_key($after, array_flip($changed)),
                    'conference_code' => $abstract->conference_code,
                    'on_behalf_of_author' => $onBehalf,
                ],
            ]);
        });

        return $changed;
    }

    /**
     * Coerce request input into storable values for the whitelisted fields only.
     */
    public function normalizePayload(array $input): array
    {
        $payload = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            if (! array_key_exists($field, $input)) {
                continue;
            }

            $payload[$field] = match ($field) {
                'coauthors' => $this->normalizeCoauthors($input[$field]),
                'include_in_proceedings' => filter_var($input[$field], FILTER_VALIDATE_BOOLEAN),
                default => trim((string) $input[$field]),
            };
        }

        return $payload;
    }

    /**
     * Drop blank rows, trim, and settle on the 'institute' key.
     *
     * Legacy rows store the affiliation under 'affiliation', which the abstract
     * book still has to read defensively; normalising on save retires that.
     * The value is returned as a PHP array — the model casts 'coauthors' to
     * array, so json_encode()ing here would double-encode it.
     */
    public function normalizeCoauthors($coauthors): array
    {
        if (is_string($coauthors)) {
            $coauthors = json_decode($coauthors, true) ?: [];
        }

        if (! is_array($coauthors)) {
            return [];
        }

        $normalized = [];

        foreach ($coauthors as $coauthor) {
            if (is_string($coauthor)) {
                $name = trim($coauthor);
                $institute = '';
            } elseif (is_array($coauthor)) {
                $name = trim((string) ($coauthor['name'] ?? ''));
                $institute = trim((string) ($coauthor['institute'] ?? $coauthor['affiliation'] ?? ''));
            } else {
                continue;
            }

            if ($name === '' && $institute === '') {
                continue;
            }

            $normalized[] = ['name' => $name, 'institute' => $institute];
        }

        return array_values($normalized);
    }

    /**
     * The editable fields as currently stored, for diffing and for form display.
     */
    public function snapshot(AbstractSubmission $abstract): array
    {
        $snapshot = [];

        foreach (self::EDITABLE_FIELDS as $field) {
            $snapshot[$field] = match ($field) {
                'coauthors' => $this->normalizeCoauthors($abstract->coauthors),
                'include_in_proceedings' => (bool) $abstract->include_in_proceedings,
                // Submissions store the body as editor HTML. Authors edit and see
                // plain text here, and the proceedings strips markup anyway, so the
                // snapshot is the plain form — which also keeps the change diff
                // honest, comparing like with like.
                'description' => AbstractBodyFormatter::plainText($abstract->description),
                default => (string) ($abstract->{$field} ?? ''),
            };
        }

        return $snapshot;
    }

    private function valuesMatch($a, $b): bool
    {
        return is_array($a) || is_array($b)
            ? json_encode($a) === json_encode($b)
            : (string) $a === (string) $b;
    }

    // ─── Admin reporting ─────────────────────────────────────────────────────

    /**
     * How many book-bound abstracts changed since the given moment (typically
     * the last abstract book generation), so admins know when a regeneration is
     * actually worth the 900s job.
     */
    public function correctedSince(?Carbon $since): int
    {
        $query = AbstractSubmission::query()
            ->where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->whereNotNull('proceedings_corrected_at');

        if ($since) {
            $query->where('proceedings_corrected_at', '>', $since);
        }

        return $query->count();
    }
}
