<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\ConferenceSession;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use setasign\Fpdi\Fpdi;

class AbstractBookController extends Controller
{
    /**
     * Show the Abstract Book viewer page
     */
    public function index()
    {
        $stats = [
            'total' => AbstractSubmission::where('status', 'accepted')->count(),
            'themes' => AbstractSubmission::where('status', 'accepted')
                ->whereNotNull('subtheme')
                ->distinct('subtheme')
                ->count('subtheme'),
            'oral' => AbstractSubmission::where('status', 'accepted')->where('presentation_mode', 'Oral')->count(),
            'poster' => AbstractSubmission::where('status', 'accepted')->where('presentation_mode', 'Poster')->count(),
            'scheduled' => AbstractSubmission::where('status', 'accepted')->whereNotNull('session_id')->count(),
        ];

        return view('admin.abstracts.book-viewer', compact('stats'));
    }

    /**
     * Stream the PDF for inline viewing
     */
    public function stream()
    {
        $abstracts        = $this->getAbstractData();
        $abstractsByTheme = $this->groupAbstractsByTheme($abstracts);
        $authorIndex      = $this->generateAuthorIndex($abstracts);

        $short = config('conference.short_name');
        $year  = config('conference.year', date('Y'));

        $merged = $this->prependCoverPdf($this->buildAbstractBookPdf($abstractsByTheme, $authorIndex)->output());

        return response($merged, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . "{$short}-{$year}-Book-of-Abstracts.pdf" . '"',
        ]);
    }

    /**
     * Download the PDF
     */
    public function generate(Request $request)
    {
        $abstracts        = $this->getAbstractData();
        $abstractsByTheme = $this->groupAbstractsByTheme($abstracts);
        $authorIndex      = $this->generateAuthorIndex($abstracts);

        $short    = config('conference.short_name');
        $year     = config('conference.year', date('Y'));
        $filename = "{$short}-{$year}-Book-of-Abstracts-" . now()->format('Y-m-d') . '.pdf';

        $merged = $this->prependCoverPdf($this->buildAbstractBookPdf($abstractsByTheme, $authorIndex)->output());

        return response($merged, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function buildAbstractBookPdf($abstractsByTheme, $authorIndex)
    {
        return Pdf::loadView('admin.conference-program.abstract-book', compact('abstractsByTheme', 'authorIndex'))
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->setOption('isPhpEnabled', true);
    }

    private function prependCoverPdf(string $contentPdfString): string
    {
        $coverPath = (($p = config('print_design.covers.abstract_book')) ? public_path($p) : '');

        if ($coverPath === '' || !file_exists($coverPath)) {
            return $contentPdfString;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'abstract_book_');
        file_put_contents($tempFile, $contentPdfString);

        try {
            $fpdi = new Fpdi();
            $fpdi->SetAutoPageBreak(false);

            $pageCount = $fpdi->setSourceFile($coverPath);
            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage($i);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);
            }

            $pageCount = $fpdi->setSourceFile($tempFile);
            for ($i = 1; $i <= $pageCount; $i++) {
                $fpdi->addPage('P', 'A4');
                $tpl = $fpdi->importPage($i);
                $fpdi->useTemplate($tpl, 0, 0, 210, 297);
            }

            return $fpdi->Output('', 'S');
        } finally {
            @unlink($tempFile);
        }
    }

    protected function getAbstractData()
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        return AbstractSubmission::where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->with('session')
            ->orderBy('subtheme')
            ->orderBy('conference_code')
            ->get();
    }

    protected function groupAbstractsByTheme($abstracts)
    {
        return $abstracts
            ->groupBy(fn (AbstractSubmission $abstract) => trim((string) $abstract->subtheme) ?: 'Unspecified Subtheme')
            ->sortKeys();
    }

    protected function generateAuthorIndex($abstracts)
    {
        $authorIndex = [];
        foreach ($abstracts as $abstract) {
            $authors = [];
            $authors[] = $abstract->author_name;
            if ($abstract->coauthors && is_array($abstract->coauthors)) {
                foreach ($abstract->coauthors as $coauthor) {
                    if (is_array($coauthor)) {
                        $authors[] = $coauthor['name'] ?? '';
                    } else {
                        $authors[] = $coauthor;
                    }
                }
            }

            foreach ($authors as $author) {
                $author = trim($author);
                if (!$author) continue;

                // Group by first letter for the index
                $firstLetter = strtoupper(substr($author, 0, 1));
                if (!isset($authorIndex[$firstLetter])) {
                    $authorIndex[$firstLetter] = [];
                }

                if (!isset($authorIndex[$firstLetter][$author])) {
                    $authorIndex[$firstLetter][$author] = [];
                }

                if (!in_array($abstract->conference_code, $authorIndex[$firstLetter][$author])) {
                    $authorIndex[$firstLetter][$author][] = $abstract->conference_code;
                }
            }
        }

        ksort($authorIndex);
        foreach ($authorIndex as $letter => &$authors) {
            ksort($authors);
        }

        return $authorIndex;
    }

}
