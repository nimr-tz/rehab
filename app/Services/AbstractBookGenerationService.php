<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Models\Speaker;
use App\Support\AbstractGrouping;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Throwable;

class AbstractBookGenerationService
{
    public const STATUS_PATH = 'conference-program/abstract-book/status.json';

    public const DIRECTORY = 'conference-program/abstract-book';

    public function status(): array
    {
        return $this->withExistingPdfMetadata($this->readStatus());
    }

    public function resetStuckStatus(): void
    {
        $existing = $this->readStatus();
        $this->writeStatus(array_merge($this->existingPdfFields($existing), [
            'status' => 'idle',
            'message' => 'Ready to generate.',
        ]));
    }

    public function markQueued(?int $userId): array
    {
        return $this->writeStatus(array_merge($this->existingPdfFields($this->readStatus()), [
            'status' => 'queued',
            'message' => 'Starting background process…',
            'progress' => 0,
            'queued_at' => now()->toIso8601String(),
            'queued_by' => $userId,
        ]));
    }

    public function generate(?int $userId = null): array
    {
        @ini_set('memory_limit', env('ABSTRACT_BOOK_MEMORY_LIMIT', '1024M'));
        @set_time_limit(0);

        // Prevent two concurrent generations
        $existing = $this->readStatus();
        if (($existing['status'] ?? '') === 'processing') {
            return $existing;
        }

        $base = array_merge($this->existingPdfFields($existing), [
            'status' => 'processing',
            'message' => 'Starting…',
            'progress' => 0,
            'started_at' => now()->toIso8601String(),
            'queued_by' => $userId,
        ]);
        $this->writeStatus($base);

        // Catch fatals (e.g. memory exhaustion) that try/catch cannot — otherwise
        // the status freezes forever and the UI shows an eternal 80%.
        $completed = false;
        register_shutdown_function(function () use (&$completed, &$base) {
            if ($completed) {
                return;
            }
            $error = error_get_last();
            $detail = $error['message'] ?? 'Process terminated unexpectedly (possible out-of-memory or timeout).';
            $this->writeStatus(array_merge($base, [
                'status' => 'failed',
                'message' => 'Generation stopped: '.$detail,
                'error' => $detail,
                'failed_at' => now()->toIso8601String(),
            ]));
        });

        $progress = function (int $pct, string $msg) use (&$base): void {
            $base['progress'] = $pct;
            $base['message'] = $msg;
            $this->writeStatus($base);
        };

        try {
            $progress(5, 'Loading abstracts…');
            $abstracts = $this->abstracts();

            $progress(15, 'Grouping abstracts by sub theme…');
            $abstractsByTheme = AbstractGrouping::bySubtheme($abstracts);

            $progress(30, 'Loading speakers…');
            $speakers = $this->speakers();

            $progress(35, 'Preparing PDF content — this takes a few minutes…');
            $pdf = Pdf::loadView('admin.conference-program.abstract-book', compact('abstractsByTheme', 'speakers'))
                ->setPaper('a4', 'portrait')
                ->setOption('enable-local-file-access', true)
                ->setOption('isHtml5ParserEnabled', true)
                ->setOption('isRemoteEnabled', false)
                ->setOption('isPhpEnabled', true);

            $progress(70, 'Rendering PDF…');
            $short = config('conference.short_name');
            $year = config('conference.year', date('Y'));
            $filename = "{$short}-{$year}-Book-of-Abstracts-".now()->format('Y-m-d-His').'.pdf';
            $path = self::DIRECTORY.'/'.$filename;

            $contentPdf = $pdf->output();

            $progress(85, 'Saving PDF…');

            // Save the content PDF and mark ready immediately — download works from
            // this point even if the cover prepend below is slow or killed.
            Storage::disk('local')->put($path, $contentPdf);

            $ready = [
                'status' => 'ready',
                'message' => 'Abstract book is ready for download.',
                'progress' => 100,
                'path' => $path,
                'filename' => $filename,
                'abstract_count' => $abstracts->count(),
                'completed_at' => now()->toIso8601String(),
                'queued_by' => $userId,
                'download_url' => route('admin.conference-program.abstract-book.download'),
            ];
            $completed = true; // tell the shutdown handler this run succeeded
            $this->writeStatus($ready);

            // Silently attempt cover prepend — no further status writes so "ready" survives
            // even if the process is killed here.
            try {
                $withCover = $this->prependCoverPdf($contentPdf);
                Storage::disk('local')->put($path, $withCover);
            } catch (\Throwable) {
                // Cover prepend failed — content-only PDF is already saved and status is ready
            }

            return $ready;
        } catch (Throwable $exception) {
            $completed = true; // handled here — don't let the shutdown handler overwrite
            report($exception);

            return $this->markFailed('Abstract book generation failed: '.$exception->getMessage(), $userId);
        }
    }

    public function markFailed(string $message, ?int $userId = null): array
    {
        return $this->writeStatus(array_merge($this->existingPdfFields($this->readStatus()), [
            'status' => 'failed',
            'message' => $message,
            'error' => $message,
            'failed_at' => now()->toIso8601String(),
            'queued_by' => $userId,
        ]));
    }

    public function downloadResponse()
    {
        $status = $this->readStatus();
        $path = $status['path'] ?? null;

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path, $status['filename'] ?? basename($path), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function abstracts(): Collection
    {
        return AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->with('session')
            ->orderBy('subtheme')
            ->orderBy('conference_code')
            ->get();
    }

    private function speakers(): Collection
    {
        return Speaker::where('is_active', true)
            ->orderByRaw("CASE type WHEN 'keynote' THEN 1 WHEN 'plenary' THEN 2 WHEN 'panelist' THEN 3 WHEN 'invited' THEN 4 ELSE 5 END")
            ->orderBy('display_order')
            ->orderBy('name')
            ->get()
            ->each(function (Speaker $speaker) {
                $speaker->pdf_photo_path = $this->resolvePhotoPath($speaker->photo_path);
            });
    }

    private function resolvePhotoPath(?string $photoPath): ?string
    {
        if (! $photoPath) {
            return null;
        }

        // Uploaded via admin panel → stored in storage/app/public/
        $storagePath = storage_path('app/public/'.$photoPath);
        if (file_exists($storagePath)) {
            return $storagePath;
        }

        return null;
    }

    private function readStatus(): array
    {
        if (! Storage::disk('local')->exists(self::STATUS_PATH)) {
            return [
                'status' => 'idle',
                'message' => 'No abstract book has been generated yet.',
            ];
        }

        $status = json_decode(Storage::disk('local')->get(self::STATUS_PATH), true);

        return is_array($status) ? $status : [
            'status' => 'idle',
            'message' => 'No abstract book has been generated yet.',
        ];
    }

    private function withExistingPdfMetadata(array $status): array
    {
        $status['has_existing_pdf'] = false;

        if (! empty($status['path']) && Storage::disk('local')->exists($status['path'])) {
            $status['has_existing_pdf'] = true;
            $status['download_url'] = route('admin.conference-program.abstract-book.download');
        }

        return $status;
    }

    private function existingPdfFields(array $status): array
    {
        if (empty($status['path']) || ! Storage::disk('local')->exists($status['path'])) {
            return [];
        }

        return array_filter([
            'path' => $status['path'],
            'filename' => $status['filename'] ?? null,
            'abstract_count' => $status['abstract_count'] ?? null,
            'completed_at' => $status['completed_at'] ?? null,
            'download_url' => route('admin.conference-program.abstract-book.download'),
        ], fn ($value) => $value !== null);
    }

    private function writeStatus(array $status): array
    {
        Storage::disk('local')->put(self::STATUS_PATH, json_encode($status, JSON_PRETTY_PRINT));

        return $status;
    }

    private function prependCoverPdf(string $contentPdfString): string
    {
        $coverPath = (($p = config('print_design.covers.abstract_book')) ? public_path($p) : '');
        $backCoverPath = (($p = config('print_design.covers.back')) ? public_path($p) : '');

        $tempFile = tempnam(sys_get_temp_dir(), 'abstract_book_');
        file_put_contents($tempFile, $contentPdfString);

        try {
            $fpdi = new Fpdi;
            $fpdi->SetAutoPageBreak(false);

            // Front cover
            if (file_exists($coverPath)) {
                $fpdi->setSourceFile($coverPath);
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage(1);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);
            }

            // Content pages. We draw the whole footer (gold rule + label + page
            // number) here, during assembly, where the exact total is known and
            // positioning is precise. dompdf's position:fixed footer mis-rendered
            // mid-page and its {PAGE_COUNT} placeholder did not survive FPDI
            // re-importing the pages ("###"). A core font (Helvetica) avoids any
            // glyph/embedding issues.
            $label = 'Abstract Book';

            $contentCount = $fpdi->setSourceFile($tempFile);
            for ($i = 1; $i <= $contentCount; $i++) {
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage($i);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);

                // Gold rule above the footer text.
                $fpdi->SetDrawColor(200, 155, 60);
                $fpdi->SetLineWidth(0.6);
                $fpdi->Line(14, 288, 197, 288);

                $fpdi->SetTextColor(102, 102, 102);
                $fpdi->SetFont('Helvetica', 'I', 7.5);
                $fpdi->SetXY(14, 289);
                $fpdi->Cell(120, 4, $label, 0, 0, 'L');

                $fpdi->SetFont('Helvetica', '', 7.5);
                $fpdi->SetXY(77, 289);
                $fpdi->Cell(120, 4, "Page {$i} of {$contentCount}", 0, 0, 'R');
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
}
