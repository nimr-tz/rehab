<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use App\Support\AbstractGrouping;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;

/**
 * Builds the Conference Proceedings as a print-ready PDF.
 *
 * Distinct from the abstract book in two ways only:
 *   1. It contains just the abstracts — no committees, foreword or
 *      speakers — because the proceedings is the published record of the science.
 *   2. It includes only abstracts whose author opted in via
 *      include_in_proceedings, since publishing full text is the author's call.
 *
 * Everything else is deliberately identical to the book: the same sub theme
 * grouping and ordering (App\Support\AbstractGrouping), the same author and
 * affiliation superscript numbering, and the same IMRaD section parsing
 * (App\Support\AbstractBodyFormatter). An abstract reads the same in both.
 *
 * Rendered with dompdf, the same engine as the abstract book, so the two
 * volumes share one typesetting path and one set of page-break behaviours.
 */
class ConferenceProceedingsService
{
    /**
     * Abstracts destined for the proceedings: accepted, coded, and opted in.
     */
    public function abstracts(): Collection
    {
        return AbstractSubmission::query()
            ->where('status', 'accepted')
            ->whereNotNull('conference_code')
            ->where('include_in_proceedings', true)
            ->orderBy('subtheme')
            ->orderBy('conference_code')
            ->get();
    }

    /**
     * Counts for the admin card: how many accepted abstracts opted in, and how
     * many are excluded because they did not.
     */
    public function stats(): array
    {
        $eligible = AbstractSubmission::query()
            ->where('status', 'accepted')
            ->whereNotNull('conference_code');

        $total = (clone $eligible)->count();
        $included = (clone $eligible)->where('include_in_proceedings', true)->count();

        return [
            'included' => $included,
            'excluded' => $total - $included,
            'total_accepted' => $total,
        ];
    }

    public function filename(): string
    {
        return config('conference.file_prefix').'-Conference-Proceedings-'.now()->format('Y-m-d').'.pdf';
    }

    /**
     * Send the proceedings to the browser as a PDF download.
     */
    public function downloadResponse()
    {
        return response($this->pdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$this->filename().'"',
        ]);
    }

    /**
     * The rendered PDF as a string.
     */
    public function pdf(): string
    {
        // dompdf decodes the whole document in memory; the book uses the same
        // headroom for the same reason.
        @ini_set('memory_limit', env('PROCEEDINGS_MEMORY_LIMIT', '1024M'));
        @set_time_limit(600);

        return Pdf::loadView('admin.conference-program.proceedings', [
            'abstractsByTheme' => AbstractGrouping::bySubtheme($this->abstracts()),
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', false)
            ->output();
    }

    /**
     * The volume's HTML, before dompdf typesets it. Useful for asserting on the
     * content without parsing a PDF.
     */
    public function html(): string
    {
        return view('admin.conference-program.proceedings', [
            'abstractsByTheme' => AbstractGrouping::bySubtheme($this->abstracts()),
        ])->render();
    }
}
