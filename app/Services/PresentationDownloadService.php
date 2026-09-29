<?php

namespace App\Services;

use App\Jobs\BuildPresentationZip;
use App\Models\AbstractSubmission;
use App\Models\ConferenceSession;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;
use ZipArchive;

/**
 * Builds presentation ZIP downloads in the background.
 *
 * Each requested download becomes a "job" tracked by a small JSON status file
 * on the local disk (storage/app/private/presentation-downloads/{id}.json). The
 * actual ZIP is written alongside it as {id}.zip. The admin Presentations page
 * polls all() and offers a link once a job is "ready".
 */
class PresentationDownloadService
{
    public const DIRECTORY = 'presentation-downloads';

    /** Where presenters' uploaded files live (public disk). */
    private function sourceBasePath(): string
    {
        return storage_path('app/public/presentations/');
    }

    /**
     * Queue a new download. Scope is one of: session | day | all.
     *  - session: params['session_id']
     *  - day:     params['day'] (1-based conference day number)
     *  - all:     params['mode'] ('all'|'Oral'|'Poster'|'Audio Poster'), params['session_id'] optional
     */
    public function queue(string $scope, array $params, ?int $userId = null): array
    {
        // Don't spawn a duplicate while an identical request is still in
        // flight — return the existing job so repeated clicks are harmless.
        if ($existing = $this->findActiveDuplicate($scope, $params)) {
            return $existing;
        }

        $id = (string) Str::uuid();

        $status = [
            'id' => $id,
            'scope' => $scope,
            'params' => $params,
            'label' => $this->describe($scope, $params),
            'status' => 'queued',
            'message' => 'Queued — preparing your download…',
            'file_name' => null,
            'file_count' => 0,
            'size' => 0,
            'queued_by' => $userId,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];

        $this->writeStatus($id, $status);

        BuildPresentationZip::dispatch($id);

        return $status;
    }

    /**
     * Build the ZIP for a queued job. Called from the background job.
     */
    public function build(string $jobId): void
    {
        $status = $this->find($jobId);
        if (! $status) {
            return;
        }

        @set_time_limit(0);
        @ini_set('memory_limit', '512M');

        $this->patch($jobId, ['status' => 'processing', 'message' => 'Gathering presentation files…']);

        try {
            $entries = $this->resolveEntries($status['scope'], $status['params'] ?? []);

            if (empty($entries)) {
                $this->patch($jobId, [
                    'status' => 'empty',
                    'message' => 'No presentation files found for this selection.',
                ]);

                return;
            }

            $storedName = $jobId.'.zip';
            $zipPath = $this->absolutePath($storedName);
            $this->ensureDirectory();

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Could not create ZIP archive.');
            }

            $added = 0;
            foreach ($entries as $entry) {
                if (is_file($entry['path'])) {
                    $zip->addFile($entry['path'], $entry['name']);
                    $added++;
                }
            }
            $zip->close();

            if ($added === 0) {
                @unlink($zipPath);
                $this->patch($jobId, [
                    'status' => 'empty',
                    'message' => 'No presentation files found for this selection.',
                ]);

                return;
            }

            // Bail out if the job was cancelled (status file removed) while we
            // were building — don't resurrect it by patching a fresh status.
            if (! $this->find($jobId)) {
                @unlink($zipPath);

                return;
            }

            $this->patch($jobId, [
                'status' => 'ready',
                'message' => 'Ready to download.',
                'file_name' => $this->downloadName($status['scope'], $status['params'] ?? []),
                'stored_name' => $storedName,
                'file_count' => $added,
                'size' => @filesize($zipPath) ?: 0,
                'ready_at' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            $this->markFailed($jobId, $e->getMessage());
        }
    }

    public function markFailed(string $jobId, string $message): void
    {
        $this->patch($jobId, [
            'status' => 'failed',
            'message' => 'Failed: '.$message,
        ]);
    }

    /**
     * All recent jobs, newest first. Prunes anything older than 48h.
     */
    public function all(): array
    {
        $this->ensureDirectory();
        $cutoff = now()->subHours(48);
        $jobs = [];

        foreach (Storage::disk('local')->files(self::DIRECTORY) as $file) {
            if (! Str::endsWith($file, '.json')) {
                continue;
            }

            $decoded = json_decode((string) Storage::disk('local')->get($file), true);
            if (! is_array($decoded) || empty($decoded['id'])) {
                continue;
            }

            $createdAt = $decoded['created_at'] ?? null;
            if ($createdAt && \Carbon\Carbon::parse($createdAt)->lt($cutoff)) {
                $this->forget($decoded['id']);

                continue;
            }

            $jobs[] = $decoded;
        }

        usort($jobs, fn ($a, $b) => strcmp($b['created_at'] ?? '', $a['created_at'] ?? ''));

        return $jobs;
    }

    public function find(string $jobId): ?array
    {
        $path = self::DIRECTORY.'/'.$jobId.'.json';
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }
        $decoded = json_decode((string) Storage::disk('local')->get($path), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * An in-flight (queued or processing) job for the same scope + params, if
     * any. Used to avoid queueing duplicate downloads on repeated clicks.
     */
    public function findActiveDuplicate(string $scope, array $params): ?array
    {
        foreach ($this->all() as $job) {
            if (($job['scope'] ?? null) === $scope
                && in_array($job['status'] ?? '', ['queued', 'processing'], true)
                && ($job['params'] ?? []) == $params) {
                return $job;
            }
        }

        return null;
    }

    /**
     * Cancel/dismiss a job: drop its status file and any built ZIP. A queued
     * job that hasn't started yet finds no status in build() and returns
     * immediately; a job mid-build detects the missing file and bails out.
     */
    public function cancel(string $jobId): bool
    {
        if (! $this->find($jobId)) {
            return false;
        }

        $this->forget($jobId);

        return true;
    }

    /**
     * Absolute path to a ready ZIP, or null if not available.
     */
    public function readyFilePath(string $jobId): ?array
    {
        $status = $this->find($jobId);
        if (! $status || ($status['status'] ?? null) !== 'ready' || empty($status['stored_name'])) {
            return null;
        }

        $path = $this->absolutePath($status['stored_name']);
        if (! is_file($path)) {
            return null;
        }

        return ['path' => $path, 'name' => $status['file_name'] ?? 'presentations.zip'];
    }

    /**
     * The conference days, derived the same way the printed programme is:
     * sessions ordered by date then start time, grouped by primary date,
     * day N = the Nth distinct date.
     *
     * @return array<int, array{number:int, date:string, label:string}>
     */
    public function conferenceDays(): array
    {
        $sessions = ConferenceSession::where('is_active', true)
            ->orderBy('start_time')
            ->get();

        // Group by each session's resolved primary date, then order the days
        // chronologically by their key. Day numbers MUST follow calendar order,
        // not DB row order: on MySQL the JSON `schedule_days` column does not
        // sort reliably, which previously mislabelled the first day (Day 1
        // showed as June 10 instead of June 9).
        $byDate = $sessions
            ->groupBy(fn ($session) => $session->getSafePrimaryDate() ?? ($session->schedule_days[0] ?? 'Unassigned'))
            ->forget('Unassigned')
            ->sortKeys();

        $days = [];
        $n = 0;
        foreach ($byDate as $date => $daySessions) {
            $n++;
            $days[] = [
                'number' => $n,
                'date' => $date,
                'label' => $daySessions->first()->getPrimaryDayLabel(),
            ];
        }

        return $days;
    }

    // ---------------------------------------------------------------------
    // Abstract / file resolution
    // ---------------------------------------------------------------------

    /**
     * @return array<int, array{path:string, name:string}>
     */
    private function resolveEntries(string $scope, array $params): array
    {
        return match ($scope) {
            'session' => $this->sessionEntries((int) ($params['session_id'] ?? 0)),
            'day' => $this->dayEntries((int) ($params['day'] ?? 0)),
            default => $this->allEntries($params),
        };
    }

    private function sessionEntries(int $sessionId): array
    {
        $session = ConferenceSession::with([
            'abstracts' => fn ($q) => $q->orderBy('session_order', 'asc')->orderBy('conference_code', 'asc'),
        ])->find($sessionId);

        if (! $session) {
            return [];
        }

        return $this->sessionFileEntries($session);
    }

    /**
     * Presentation file entries for one session, following the programme.
     *
     * Abstracts are walked in presentation order so the ZIP mirrors the
     * printed programme.
     *
     * @return array<int, array{path:string, name:string}>
     */
    private function sessionFileEntries(ConferenceSession $session, ?string $folder = null): array
    {
        $sources = $session->abstracts;

        $entries = [];
        $order = 1;
        foreach ($sources as $abstract) {
            $files = $this->abstractFiles($abstract);
            foreach ($files as $file) {
                $name = $this->friendlyName($abstract, $file['type'], $file['extension'], $order);
                $entries[] = [
                    'path' => $file['path'],
                    'name' => $folder ? $folder.'/'.$name : $name,
                ];
            }
            if (! empty($files)) {
                $order++;
            }
        }

        return $entries;
    }

    private function dayEntries(int $dayNumber): array
    {
        $days = $this->conferenceDays();
        $target = collect($days)->firstWhere('number', $dayNumber);
        if (! $target) {
            return [];
        }

        $sessions = ConferenceSession::with([
                'abstracts' => fn ($q) => $q->where('status', 'accepted')
                    ->whereNotNull('conference_code')
                    ->orderBy('session_order', 'asc')
                    ->orderBy('conference_code', 'asc'),
            ])
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get()
            ->filter(function ($session) use ($target) {
                $date = $session->getSafePrimaryDate() ?? ($session->schedule_days[0] ?? null);

                return $date === $target['date'];
            });

        $entries = [];
        foreach ($sessions as $session) {
            $folder = $this->sanitize($session->name);
            foreach ($this->sessionFileEntries($session, $folder) as $entry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function allEntries(array $params): array
    {
        $mode = $params['mode'] ?? 'all';
        $sessionId = $params['session_id'] ?? null;

        $query = AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->with(['session'])
            ->orderBy('session_id')
            ->orderBy('session_order')
            ->orderBy('conference_code');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        if ($mode !== 'all') {
            $query->where('presentation_mode', $mode);
        }

        $query->where(function ($q) {
            $q->whereNotNull('oral_presentation_file')
                ->orWhereNotNull('poster_presentation_file')
                ->orWhereNotNull('audio_poster_file');
        });

        $abstracts = $query->get();

        $entries = [];
        $currentSessionId = null;
        $order = 0;
        foreach ($abstracts as $abstract) {
            if ($abstract->session_id !== $currentSessionId) {
                $currentSessionId = $abstract->session_id;
                $order = 0;
            }
            $order++;

            $files = $this->abstractFiles($abstract);
            foreach ($files as $file) {
                $name = $this->friendlyName($abstract, $file['type'], $file['extension'], $order);
                if ($abstract->session && ! $sessionId) {
                    $name = $this->sanitize($abstract->session->name).'/'.$name;
                }
                $entries[] = ['path' => $file['path'], 'name' => $name];
            }
        }

        return $entries;
    }

    /**
     * @return array<int, array{type:string, path:string, extension:string}>
     */
    private function abstractFiles(AbstractSubmission $abstract): array
    {
        $files = [];
        $base = $this->sourceBasePath();

        $candidates = [
            'Oral' => $abstract->oral_presentation_file,
            'Poster' => $abstract->poster_presentation_file,
            'Audio' => $abstract->audio_poster_file,
        ];

        foreach ($candidates as $type => $filename) {
            if (! $filename) {
                continue;
            }
            $path = $base.$filename;
            if (is_file($path)) {
                $files[] = [
                    'type' => $type,
                    'path' => $path,
                    'extension' => pathinfo($filename, PATHINFO_EXTENSION),
                ];
            }
        }

        return $files;
    }

    // ---------------------------------------------------------------------
    // Naming / labelling
    // ---------------------------------------------------------------------

    private function friendlyName(AbstractSubmission $abstract, string $type, string $extension, int $order): string
    {
        $authorParts = explode(' ', trim((string) $abstract->author_name));
        $lastName = $this->sanitize((string) end($authorParts));
        $code = $abstract->conference_code ?? ('ABS-'.str_pad((string) $abstract->id, 4, '0', STR_PAD_LEFT));
        $orderStr = str_pad((string) $order, 2, '0', STR_PAD_LEFT);

        return "{$orderStr}_{$code}_{$lastName}_{$type}.{$extension}";
    }

    private function describe(string $scope, array $params): string
    {
        return match ($scope) {
            'session' => 'Session: '.(ConferenceSession::find($params['session_id'] ?? 0)?->name ?? 'Unknown'),
            'day' => $this->dayLabel((int) ($params['day'] ?? 0)),
            default => 'All presentations'.(($params['mode'] ?? 'all') !== 'all' ? ' — '.$params['mode'] : ''),
        };
    }

    private function dayLabel(int $dayNumber): string
    {
        $target = collect($this->conferenceDays())->firstWhere('number', $dayNumber);

        return $target ? "Day {$dayNumber}: {$target['label']}" : "Day {$dayNumber}";
    }

    private function downloadName(string $scope, array $params): string
    {
        $stamp = now()->format('Ymd_His');

        if ($scope === 'session') {
            $session = ConferenceSession::find($params['session_id'] ?? 0);
            $name = $session ? $this->sanitize($session->name) : 'Session';

            return "Session_{$name}_{$stamp}.zip";
        }

        if ($scope === 'day') {
            $target = collect($this->conferenceDays())->firstWhere('number', (int) ($params['day'] ?? 0));
            $date = $target ? str_replace('-', '', $target['date']) : 'day';

            return config('conference.file_prefix') . "_Presentations_Day{$params['day']}_{$date}.zip";
        }

        $mode = $params['mode'] ?? 'all';
        $modeLabel = $mode === 'all' ? 'All' : str_replace(' ', '', $mode);

        return config('conference.file_prefix') . "_Presentations_{$modeLabel}_{$stamp}.zip";
    }

    private function sanitize(string $name): string
    {
        $sanitized = preg_replace('/[<>:"\/\\\\|?*]/', '', $name);
        $sanitized = preg_replace('/\s+/', '_', (string) $sanitized);
        $sanitized = preg_replace('/_+/', '_', (string) $sanitized);
        $sanitized = substr((string) $sanitized, 0, 50);

        return trim($sanitized, '_');
    }

    // ---------------------------------------------------------------------
    // Status persistence
    // ---------------------------------------------------------------------

    private function ensureDirectory(): void
    {
        if (! Storage::disk('local')->exists(self::DIRECTORY)) {
            Storage::disk('local')->makeDirectory(self::DIRECTORY);
        }
    }

    private function writeStatus(string $jobId, array $status): void
    {
        $this->ensureDirectory();
        Storage::disk('local')->put(
            self::DIRECTORY.'/'.$jobId.'.json',
            json_encode($status, JSON_PRETTY_PRINT)
        );
    }

    private function patch(string $jobId, array $changes): void
    {
        $status = $this->find($jobId) ?? [];
        $status = array_merge($status, $changes, ['updated_at' => now()->toIso8601String()]);
        $this->writeStatus($jobId, $status);
    }

    private function forget(string $jobId): void
    {
        foreach ([$jobId.'.json', $jobId.'.zip'] as $file) {
            $path = self::DIRECTORY.'/'.$file;
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        }
    }

    private function absolutePath(string $filename): string
    {
        return Storage::disk('local')->path(self::DIRECTORY.'/'.$filename);
    }
}
