<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Models\GroupMember;
use App\Models\GroupRegistration;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        // Get data for filters
        $subthemes = AbstractSubmission::where('status', '!=', 'draft')
            ->distinct()
            ->pluck('subtheme')
            ->filter()
            ->sort()
            ->values();

        $unpaidContactsCount = User::whereNotIn('payment_status', ['verified', 'waived'])
            ->whereNotNull('phone')
            ->count();

        return view('admin.reports.builder', compact('subthemes', 'unpaidContactsCount'));
    }

    public function generate(Request $request)
    {
        $validated = $request->validate([
            'columns' => 'nullable|array',
            'format' => 'required|in:pdf,csv',
            'status' => 'nullable|string',
            'subtheme' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $columns = $request->input('columns', []);
        if (empty($columns)) {
            $columns = ['id', 'title', 'subtheme', 'author_name', 'status'];
        }

        // Build Query
        $query = AbstractSubmission::query()
            ->where('status', '!=', 'draft')
            ->with(['user', 'reviewer1', 'reviewer2', 'reviews']);

        // Apply Filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('subtheme') && $request->subtheme !== 'all') {
            $query->where('subtheme', $request->subtheme);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $abstracts = $query->get();
        $format = $request->format;

        if ($format === 'pdf') {
            return $this->generatePdf($abstracts, $columns);
        } else {
            return $this->generateCsv($abstracts, $columns);
        }
    }

    private function generatePdf($abstracts, $columns)
    {
        $pdf = Pdf::loadView('admin.reports.pdf_template', [
            'abstracts' => $abstracts,
            'columns' => $columns,
            'generated_at' => now(),
            'user' => auth()->user()
        ]);

        return $pdf->setPaper('a4', 'landscape')->download('abstracts-report-' . now()->format('Y-m-d-H-i') . '.pdf');
    }

    private function generateCsv($abstracts, $columns)
    {
        $filename = 'abstracts-report-' . now()->format('Y-m-d-H-i') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($abstracts, $columns) {
            $file = fopen('php://output', 'w');
            if (!$file) {
                return;
            }

            // Add BOM for Excel compatibility
            fputs($file, "\xEF\xBB\xBF");

            // Header Row
            $headerRow = array_map(fn ($col) => $this->getColumnLabel($col), $columns);
            fputcsv($file, $headerRow);

            // Data Rows
            if ($abstracts->isEmpty()) {
                $emptyRow = array_fill(0, count($columns), 'No records match your filters.');
                $emptyRow[0] = '(0 abstracts)';
                fputcsv($file, $emptyRow);
            } else {
                foreach ($abstracts as $abstract) {
                    $row = [];
                    foreach ($columns as $col) {
                        try {
                            $row[] = $this->getColumnValue($abstract, $col);
                        } catch (\Throwable $e) {
                            $row[] = '';
                        }
                    }
                    fputcsv($file, $row);
                }
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function getColumnLabel($column)
    {
        $labels = [
            'id' => 'ID',
            'title' => 'Title',
            'conference_code' => 'Conf. Code',
            'subtheme' => 'Subtheme',
            'author_title' => 'Author Title',
            'author_name' => 'Author Name',
            'author_email' => 'Author Email',
            'author_institute' => 'Institute',
            'author_country' => 'Country',
            'status' => 'Status',
            'created_at' => 'Submitted Date',
            'updated_at' => 'Last Updated',
            'reviewer_names' => 'Reviewers',
            'average_score' => 'Avg Score',
            'recommendations' => 'Recommendations',
            'description' => 'Abstract Content'
        ];

        return $labels[$column] ?? ucfirst(str_replace('_', ' ', $column));
    }

    private function getColumnValue($abstract, $column)
    {
        $user = $abstract->user;
        $reviewer1 = $abstract->reviewer1 ?? null;
        $reviewer2 = $abstract->reviewer2 ?? null;

        switch ($column) {
            case 'id': return $abstract->id;
            case 'title': return $abstract->title ?? '';
            case 'conference_code': return $abstract->conference_code ?? 'N/A';
            case 'subtheme': return $abstract->subtheme ?? '';
            case 'author_title': return $user ? ($user->title ?? '') : '';
            case 'author_name':
                if ($user) {
                    return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
                }
                return $abstract->author_name ?? '';
            case 'author_email': return $user ? ($user->email ?? 'N/A') : 'N/A';
            case 'author_institute': return $abstract->author_institute ?? '';
            case 'author_country': return $abstract->author_country ?? ($user ? ($user->country ?? 'N/A') : 'N/A');
            case 'status': return ucfirst(str_replace('_', ' ', $abstract->status ?? ''));
            case 'created_at': return $abstract->created_at ? $abstract->created_at->format('Y-m-d H:i') : '';
            case 'updated_at': return $abstract->updated_at ? $abstract->updated_at->format('Y-m-d H:i') : '';
            case 'reviewer_names':
                $r1 = $reviewer1 ? ($reviewer1->name ?? trim(($reviewer1->first_name ?? '') . ' ' . ($reviewer1->last_name ?? ''))) : '';
                $r2 = $reviewer2 ? ($reviewer2->name ?? trim(($reviewer2->first_name ?? '') . ' ' . ($reviewer2->last_name ?? ''))) : '';
                return trim("$r1, $r2", ", ");
            case 'average_score':
                $reviews = $abstract->reviews ?? collect();
                $scores = $reviews->where('status', 'submitted')->pluck('score')->filter();
                return $scores->count() > 0 ? round($scores->avg(), 1) : 'N/A';
            case 'recommendations':
                $reviews = $abstract->reviews ?? collect();
                return $reviews->where('status', 'submitted')->pluck('recommendation')->filter()->map(fn ($r) => ucfirst(str_replace('_', ' ', $r)))->join(', ');
            case 'description': return strip_tags($abstract->description ?? '');
            default: return '';
        }
    }

    /**
     * Summary statistics: abstracts and registrations by institute and by country
     */
    public function summary()
    {
        $abstractsByInstitute = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(author_institute), ''), 'Not specified') as name, COUNT(*) as count FROM abstract_submissions WHERE status != 'draft' GROUP BY 1 ORDER BY count DESC"
        ))->map(fn ($r) => (object)['name' => $r->name, 'count' => $r->count]);

        $abstractsByCountry = collect(DB::select(
            "SELECT COALESCE(NULLIF(TRIM(COALESCE(u.country, '')), ''), 'Not specified') as name, COUNT(*) as count
             FROM abstract_submissions a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.status != 'draft'
             GROUP BY 1 ORDER BY count DESC"
        ))->map(fn ($r) => (object)['name' => $r->name, 'count' => $r->count]);

        // Individual registrations (users with verified/waived payment)
        $individualByInstitute = User::query()
            ->whereIn('payment_status', ['verified', 'waived'])
            ->selectRaw('COALESCE(NULLIF(TRIM(affiliation), ""), "Not specified") as name, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(NULLIF(TRIM(affiliation), ""), "Not specified")'))
            ->orderByDesc('count')
            ->get();

        $individualByCountry = User::query()
            ->whereIn('payment_status', ['verified', 'waived'])
            ->selectRaw('COALESCE(NULLIF(TRIM(country), ""), "Not specified") as name, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(NULLIF(TRIM(country), ""), "Not specified")'))
            ->orderByDesc('count')
            ->get();

        // Group members (verified groups only)
        $groupByInstitute = GroupMember::query()
            ->whereHas('groupRegistration', fn ($q) => $q->whereIn('payment_status', ['verified', 'waived']))
            ->selectRaw('COALESCE(NULLIF(TRIM(institution), ""), "Not specified") as name, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(NULLIF(TRIM(institution), ""), "Not specified")'))
            ->orderByDesc('count')
            ->get();

        $groupByCountry = GroupMember::query()
            ->whereHas('groupRegistration', fn ($q) => $q->whereIn('payment_status', ['verified', 'waived']))
            ->selectRaw('COALESCE(NULLIF(TRIM(country), ""), "Not specified") as name, COUNT(*) as count')
            ->groupBy(DB::raw('COALESCE(NULLIF(TRIM(country), ""), "Not specified")'))
            ->orderByDesc('count')
            ->get();

        // Merge individual + group for registrations by institute/country
        $regByInstitute = $this->mergeCounts($individualByInstitute->pluck('count', 'name'), $groupByInstitute->pluck('count', 'name'));
        $regByCountry = $this->mergeCounts($individualByCountry->pluck('count', 'name'), $groupByCountry->pluck('count', 'name'));

        return view('admin.reports.summary', [
            'abstractsByInstitute' => $abstractsByInstitute,
            'abstractsByCountry' => $abstractsByCountry,
            'registrationsByInstitute' => $regByInstitute,
            'registrationsByCountry' => $regByCountry,
        ]);
    }

    public function exportUnpaidContacts()
    {
        $users = User::whereNotIn('payment_status', ['verified', 'waived'])
            ->whereNotNull('phone')
            ->orderBy('last_name')
            ->get(['first_name', 'last_name', 'email', 'phone', 'registration_category', 'payment_status', 'created_at']);

        $filename = 'unpaid-contacts-' . now()->format('Y-m-d-H-i') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=$filename",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, ['Name', 'Email', 'Phone', 'Category', 'Payment Status', 'Registered At']);

            foreach ($users as $user) {
                fputcsv($file, [
                    trim($user->first_name . ' ' . $user->last_name),
                    $user->email,
                    $user->phone,
                    $user->registration_category ?? 'Not set',
                    ucfirst($user->payment_status),
                    $user->created_at->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return Response::stream($callback, 200, $headers);
    }

    private function mergeCounts($a, $b)
    {
        $keys = $a->keys()->merge($b->keys())->unique();
        return $keys->map(fn ($name) => (object)[
            'name' => $name,
            'count' => (int) ($a[$name] ?? 0) + (int) ($b[$name] ?? 0),
        ])->sortByDesc('count')->values();
    }
}
