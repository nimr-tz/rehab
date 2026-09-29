<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\User;
use App\Models\ReviewHistory;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AbstractManagementController extends Controller
{
    public function index(Request $request)
    {
        // Process automatic status updates first
        $this->processAutoStatusUpdates();

        $query = AbstractSubmission::with(['user', 'reviewer1', 'reviewer2', 'reviews'])
            ->where('status', '!=', 'draft');

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Validate sort columns to prevent SQL injection
        $allowedSorts = ['id', 'title', 'author_name', 'subtheme', 'status', 'created_at', 'presentation_mode', 'presentation_status', 'presentation_uploaded_at', 'average_score'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Apply search filter (enhanced with ID and Email search)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                // Exact numeric ID match
                if (is_numeric($search)) {
                    $q->where('id', $search);
                }

                $q->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('conference_code', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhere('author_institute', 'like', "%{$search}%")
                  // Search author/user by names or email
                  ->orWhereHas('user', function($u) use ($search) {
                      $u->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Apply acceptance status filter
        if ($request->filled('acceptance_status') && $request->acceptance_status !== 'all') {
            if ($request->acceptance_status === 'accepted') {
                $query->where('status', 'accepted');
            } elseif ($request->acceptance_status === 'rejected') {
                $query->where('status', 'rejected');
            } elseif ($request->acceptance_status === 'decided') {
                $query->whereIn('status', ['accepted', 'rejected']);
            } elseif ($request->acceptance_status === 'pending_decision') {
                $query->whereNotIn('status', ['accepted', 'rejected', 'draft']);
            }
        }

        // Apply status filter with enhanced logic (for other statuses)
        if ($request->filled('status') && $request->status !== 'all') {
            $this->applyStatusFilter($query, $request->status);
        }

        // Apply date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Apply reviewer filter
        if ($request->filled('reviewer')) {
            $reviewerId = $request->reviewer;
            $query->where(function ($q) use ($reviewerId) {
                $q->where('reviewer_id', $reviewerId)
                  ->orWhere('reviewer_2_id', $reviewerId);
            });
        }

        // Apply subtheme filter
        if ($request->filled('subtheme')) {
            $query->where('subtheme', $request->subtheme);
        }

        // Apply presentation status filter (for accepted abstracts only, exclude rejected)
        if ($request->filled('presentation_status') && $request->presentation_status !== 'all') {
            if ($request->presentation_status === 'uploaded') {
                // Check for actual presentation files - only accepted abstracts (exclude rejected)
                $query->where('status', 'accepted')
                      ->where(function($q) {
                          $q->whereNotNull('oral_presentation_file')
                            ->orWhereNotNull('poster_presentation_file')
                            ->orWhereNotNull('audio_poster_file');
                      });
            } elseif ($request->presentation_status === 'pending') {
                // Accepted abstracts without presentation files (exclude rejected)
                $query->where('status', 'accepted')
                      ->where(function($q) {
                          $q->whereNull('oral_presentation_file')
                            ->whereNull('poster_presentation_file')
                            ->whereNull('audio_poster_file');
                      });
            }
        }

        // Handle pagination
        if ($request->has('export')) {
            $abstracts = $query->get();
            $perPage = 20;
            $page = $request->get('page', 1);
            $paged = $abstracts->slice(($page - 1) * $perPage, $perPage)->values();
            $abstracts = new \Illuminate\Pagination\LengthAwarePaginator($paged, $abstracts->count(), $perPage, $page, [
                'path' => request()->url(),
                'query' => request()->query(),
            ]);
        } else {
            $abstracts = $query->paginate(20);
        }

        // Enhanced statistics for the page
        $stats = $this->getEnhancedAbstractStats();

        // Get available reviewers for bulk assignment
        $availableReviewers = User::whereHas('roles', function($query) {
            $query->where('name', 'reviewer');
        })->select('id', 'first_name', 'last_name')->get();

        return view('admin.abstracts.index', compact('abstracts', 'stats', 'availableReviewers'));
    }

    public function exportAllExcel(Request $request)
    {
        $query = AbstractSubmission::with(['user', 'reviewer1', 'reviewer2', 'reviews'])
            ->where('status', '!=', 'draft')
            ->orderBy('created_at', 'desc');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('conference_code', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhere('author_institute', 'like', "%{$search}%");
            });
        }

        // Apply status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $this->applyStatusFilter($query, $request->status);
        }

        // Apply subtheme filter
        if ($request->filled('subtheme')) {
            $query->where('subtheme', $request->subtheme);
        }

        $abstracts = $query->get();

        return $this->bulkExportAsExcel($abstracts);
    }

    /**
     * Handle bulk actions for abstracts
     */
    public function bulkAction(Request $request)
    {
        $action = $request->get('action');
        $ids = $request->get('ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No abstracts selected');
        }

        $abstracts = AbstractSubmission::whereIn('id', $ids)->get();

        $statusService = app(\App\Services\AbstractStatusService::class);

        switch ($action) {
            case 'accept':
                $results = ['success' => 0, 'failed' => 0, 'errors' => []];

                foreach ($abstracts as $abstract) {
                    $summary = $abstract->getBestAvailableAdminReviewSummary();
                    $isRevisionLane = in_array($abstract->status, [
                        'revision',
                        'revision_requested',
                        'revision_required',
                        'minor_revision_required',
                        'major_revision_required',
                        'revision_submitted',
                        'minor_revision_submitted',
                        'major_revision_submitted',
                        'revision_under_review',
                        'revision_review',
                    ], true);

                    if ($isRevisionLane && (float) ($summary['avg_score'] ?? 0) < 70) {
                        $results['failed']++;
                        $results['errors'][] = "Abstract {$abstract->id} is below the 70-point override threshold.";
                        continue;
                    }

                    $result = $statusService->changeStatus($abstract, 'accepted', 'Bulk accepted by admin override');
                    if ($result['success']) {
                        $results['success']++;
                    } else {
                        $results['failed']++;
                        $results['errors'][] = "Abstract {$abstract->id}: " . $result['message'];
                    }
                }

                $message = "{$results['success']} abstracts marked as Accepted.";
                if ($results['failed'] > 0) {
                    $message .= " {$results['failed']} failed.";
                }
                break;
            case 'reject':
                $results = $statusService->bulkUpdateStatus($ids, 'rejected', 'Bulk rejected by admin');
                $message = "{$results['success']} abstracts marked as Rejected.";
                if ($results['failed'] > 0) $message .= " {$results['failed']} failed.";
                break;
            case 'delete':
                foreach ($abstracts as $abstract) {
                    $abstract->delete();
                }
                $message = count($ids) . ' abstracts deleted permanently';
                break;
            case 'assign_reviewer':
                $reviewerId = $request->get('reviewer_id');
                $position = $request->get('position', 1);
                if (!$reviewerId) {
                    return redirect()->back()->with('error', 'No reviewer selected');
                }
                $field = ($position == 1) ? 'reviewer_id' : 'reviewer_2_id';
                foreach ($abstracts as $abstract) {
                    $abstract->update([$field => $reviewerId]);
                    // Transition to under_review via service
                    $statusService->changeStatus($abstract, 'under_review', "Assigned to reviewer {$position}");
                }
                $message = count($ids) . ' abstracts assigned to reviewer ' . $position;
                break;
            default:
                return redirect()->back()->with('error', 'Invalid bulk action');
        }

        return redirect()->back()->with('success', $message);
    }

    public function bulkExport(Request $request)
    {
        $validated = $request->validate([
            'abstract_ids' => 'required|array',
            'abstract_ids.*' => 'exists:abstract_submissions,id',
            'format' => 'sometimes|in:pdf,csv,json,excel'
        ]);

        $abstractIds = $validated['abstract_ids'];
        $format = $validated['format'] ?? 'csv';

        // Load abstracts with necessary relationships
        $abstracts = AbstractSubmission::whereIn('id', $abstractIds)
            ->with(['user', 'reviewer1', 'reviewer2', 'reviews.reviewer'])
            ->get();

        switch ($format) {
            case 'json':
                return $this->bulkExportAsJson($abstracts);
            case 'excel':
                return $this->bulkExportAsExcel($abstracts);
            default:
                return $this->bulkExportAsCsv($abstracts);
        }
    }

    private function bulkExportAsCsv($abstracts)
    {
        $filename = 'abstracts-bulk-export-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($abstracts) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID', 'Title', 'Author', 'Institute', 'Subtheme', 'Status',
                'Reviewer 1', 'Reviewer 2', 'Submitted Date', 'Last Updated',
                'Description'
            ]);

            foreach ($abstracts as $abstract) {
                fputcsv($file, [
                    $abstract->id,
                    $abstract->title,
                    $abstract->author_name,
                    $abstract->author_institute,
                    $abstract->subtheme,
                    $abstract->status,
                    $abstract->reviewer1 ? $abstract->reviewer1->name : 'Not Assigned',
                    $abstract->reviewer2 ? $abstract->reviewer2->name : 'Not Assigned',
                    $abstract->created_at->format('Y-m-d H:i:s'),
                    $abstract->updated_at->format('Y-m-d H:i:s'),
                    strip_tags($abstract->description)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function bulkExportAsJson($abstracts)
    {
        $data = [
            'abstracts' => $abstracts->toArray(),
            'total_count' => $abstracts->count(),
            'exported_at' => now()->toISOString(),
            'exported_by' => [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email
            ]
        ];

        $filename = 'abstracts-bulk-export-' . now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function bulkExportAsExcel($abstracts)
    {
        // Simple HTML table that can be opened in Excel
        $filename = 'abstracts-bulk-export-' . now()->format('Y-m-d-H-i-s') . '.xls';

        $html = '<table border="1">';
        $html .= '<tr><th>ID</th><th>Title</th><th>Author</th><th>Institute</th><th>Subtheme</th><th>Status</th><th>Reviewer 1</th><th>Reviewer 2</th><th>Submitted</th><th>Description</th></tr>';

        foreach ($abstracts as $abstract) {
            $html .= '<tr>';
            $html .= '<td>' . $abstract->id . '</td>';
            $html .= '<td>' . htmlspecialchars($abstract->title) . '</td>';
            $html .= '<td>' . htmlspecialchars($abstract->author_name) . '</td>';
            $html .= '<td>' . htmlspecialchars($abstract->author_institute) . '</td>';
            $html .= '<td>' . htmlspecialchars($abstract->subtheme) . '</td>';
            $html .= '<td>' . ucfirst($abstract->status) . '</td>';
            $html .= '<td>' . ($abstract->reviewer1 ? htmlspecialchars($abstract->reviewer1->name) : 'Not Assigned') . '</td>';
            $html .= '<td>' . ($abstract->reviewer2 ? htmlspecialchars($abstract->reviewer2->name) : 'Not Assigned') . '</td>';
            $html .= '<td>' . $abstract->created_at->format('Y-m-d H:i:s') . '</td>';
            $html .= '<td>' . htmlspecialchars(strip_tags($abstract->description)) . '</td>';
            $html .= '</tr>';
        }

        $html .= '</table>';

        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function show(AbstractSubmission $abstract)
    {
        // Load relationships for detailed view
        $abstract->load(['user', 'reviewer1', 'reviewer2', 'reviews.reviewer']);

        return view('admin.abstracts.view', compact('abstract'));
    }

    public function edit(AbstractSubmission $abstract)
    {
        // Load relationships for edit view
        $abstract->load(['user']);

        return view('admin.abstracts.edit', compact('abstract'));
    }

    public function history(AbstractSubmission $abstract)
    {
        // Load the abstract with its review history and related data
        $abstract->load([
            'user',
            'reviewer1',
            'reviewer2',
            'reviews.reviewer'
        ]);

        // Get review history ordered by date
        $reviewHistory = ReviewHistory::where('abstract_submission_id', $abstract->id)
            ->with(['reviewer', 'admin'])
            ->orderBy('action_date', 'desc')
            ->get();

        // Get status change history if you have it
        // You might want to add a StatusHistory model for tracking status changes

        return view('admin.abstracts.history', compact('abstract', 'reviewHistory'));
    }

    public function export(AbstractSubmission $abstract)
    {
        // Load all necessary relationships
        $abstract->load([
            'user',
            'reviewer1',
            'reviewer2',
            'reviews.reviewer'
        ]);

        // Get review history
        $reviewHistory = ReviewHistory::where('abstract_submission_id', $abstract->id)
            ->with(['reviewer', 'admin'])
            ->orderBy('action_date', 'desc')
            ->get();

        // Check what export format is requested (default to PDF)
        $format = request('format', 'pdf');

        switch ($format) {
            case 'json':
                return $this->exportAsJson($abstract, $reviewHistory);
            case 'csv':
                return $this->exportAsCsv($abstract, $reviewHistory);
            case 'word':
                return $this->exportAsWord($abstract, $reviewHistory);
            default:
                return $this->exportAsPdf($abstract, $reviewHistory);
        }
    }

    private function exportAsPdf(AbstractSubmission $abstract, $reviewHistory)
    {
        // For now, return a simple response with abstract data
        // You can integrate with a PDF library like DomPDF or wkhtmltopdf

        $data = [
            'abstract' => $abstract,
            'reviewHistory' => $reviewHistory,
            'exportDate' => now(),
            'exportedBy' => Auth::user()
        ];

        // Create a simple HTML response that can be printed as PDF
        $html = view('admin.abstracts.export.pdf', $data)->render();

        $filename = 'abstract-' . $abstract->id . '-' . now()->format('Y-m-d-H-i-s') . '.html';

        return response($html)
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportAsJson(AbstractSubmission $abstract, $reviewHistory)
    {
        $data = [
            'abstract' => $abstract->toArray(),
            'review_history' => $reviewHistory->toArray(),
            'exported_at' => now()->toISOString(),
            'exported_by' => [
                'id' => Auth::user()->id,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email
            ]
        ];

        $filename = 'abstract-' . $abstract->id . '-' . now()->format('Y-m-d-H-i-s') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    private function exportAsCsv(AbstractSubmission $abstract, $reviewHistory)
    {
        $filename = 'abstract-' . $abstract->id . '-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($abstract, $reviewHistory) {
            $file = fopen('php://output', 'w');

            // Abstract information
            fputcsv($file, ['Abstract Information']);
            fputcsv($file, ['ID', $abstract->id]);
            fputcsv($file, ['Title', $abstract->title]);
            fputcsv($file, ['Author', $abstract->author_name]);
            fputcsv($file, ['Institute', $abstract->author_institute]);
            fputcsv($file, ['Subtheme', $abstract->subtheme]);
            fputcsv($file, ['Status', $abstract->status]);
            fputcsv($file, ['Submitted', $abstract->created_at->format('Y-m-d H:i:s')]);
            fputcsv($file, ['']);

            // Review history
            fputcsv($file, ['Review History']);
            fputcsv($file, ['Date', 'Action', 'Reviewer', 'Score', 'Comments']);

            foreach ($reviewHistory as $history) {
                fputcsv($file, [
                    $history->action_date->format('Y-m-d H:i:s'),
                    $history->action,
                    $history->reviewer ? $history->reviewer->name : 'N/A',
                    $history->score ?? 'N/A',
                    $history->comments ?? 'N/A'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function exportAsWord(AbstractSubmission $abstract, $reviewHistory)
    {
        // Simple RTF format that can be opened in Word
        $filename = 'abstract-' . $abstract->id . '-' . now()->format('Y-m-d-H-i-s') . '.rtf';

        $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Times New Roman;}}';
        $rtf .= '\\f0\\fs24 ';
        $rtf .= '{\\b Abstract Export Report}\\par\\par';
        $rtf .= '{\\b Title:} ' . $abstract->title . '\\par';
        $rtf .= '{\\b Author:} ' . $abstract->author_name . '\\par';
        $rtf .= '{\\b Institute:} ' . $abstract->author_institute . '\\par';
        $rtf .= '{\\b Status:} ' . ucfirst($abstract->status) . '\\par';
        $rtf .= '{\\b Submitted:} ' . $abstract->created_at->format('M d, Y H:i') . '\\par\\par';

        $rtf .= '{\\b Description:}\\par';
        $rtf .= $abstract->description . '\\par\\par';

        if ($reviewHistory->count() > 0) {
            $rtf .= '{\\b Review History:}\\par';
            foreach ($reviewHistory as $history) {
                $rtf .= '- ' . $history->action_date->format('M d, Y') . ': ' . $history->action;
                if ($history->reviewer) {
                    $rtf .= ' by ' . $history->reviewer->name;
                }
                if ($history->score) {
                    $rtf .= ' (Score: ' . $history->score . '/10)';
                }
                $rtf .= '\\par';
            }
        }

        $rtf .= '}';

        return response($rtf)
            ->header('Content-Type', 'application/rtf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function update(Request $request, AbstractSubmission $abstract)
    {
        $subthemes = array_keys(config('conference.subtheme_prefixes', []));
        $validated = $request->validate([
            'author_name'      => 'required|string|max:255',
            'author_institute' => 'required|string|max:255',
            'title'            => 'required|string|max:255',
            'description'      => 'required|string|max:10000',
            'subtheme'         => ['required', 'string', Rule::in($subthemes)],
            'conference_code'  => 'nullable|string|max:20|unique:abstract_submissions,conference_code,' . $abstract->id,
            'coauthors'        => 'nullable|array',
            'coauthors.*.name'      => 'nullable|string|max:255',
            'coauthors.*.institute' => 'nullable|string|max:255',
        ]);

        // Normalise coauthors: drop blank entries
        $coauthors = collect($validated['coauthors'] ?? [])
            ->filter(fn($c) => !empty($c['name']))
            ->values()
            ->toArray();

        unset($validated['coauthors']);
        $originalConferenceCode = $abstract->conference_code;
        $hasConferenceCodeInput = array_key_exists('conference_code', $validated);

        if ($hasConferenceCodeInput) {
            $validated['conference_code'] = filled($validated['conference_code'] ?? null)
                ? strtoupper(trim($validated['conference_code']))
                : null;
        }

        if ($hasConferenceCodeInput && $validated['conference_code'] !== $originalConferenceCode) {
            $validated['code_is_final'] = filled($validated['conference_code']);
            $validated['code_assigned_by'] = filled($validated['conference_code']) ? Auth::id() : null;
            $validated['code_assigned_at'] = filled($validated['conference_code']) ? now() : null;
        }

        $abstract->update($validated);
        $abstract->coauthors = $coauthors;
        $abstract->save();

        // Log the admin edit action
        Log::info('Admin edited abstract', [
            'abstract_id' => $abstract->id,
            'admin_user_id' => Auth::id(),
            'admin_email' => Auth::user()->email,
            'changes' => $validated
        ]);

        // You might want to notify the original author about the changes
        // NotifyAuthorOfAdminEdit::dispatch($abstract, auth()->user());

        return redirect()
            ->route('admin.abstracts.index')
            ->with('success', 'Abstract updated successfully.');
    }

    public function destroy(AbstractSubmission $abstract)
    {
        // Log the deletion action
        Log::info('Admin deleting abstract', [
            'abstract_id' => $abstract->id,
            'title' => $abstract->title,
            'admin_id' => Auth::id()
        ]);

        try {
            // Delete related records if necessary (though foreign keys should handle cascade usually)
            // But explicitly deleting reviews is safer practice
            $abstract->reviews()->delete();
            
            // Delete the abstract
            $abstract->delete();

            return redirect()->route('admin.abstracts.index')
                ->with('success', 'Abstract deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to delete abstract: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Failed to delete abstract: ' . $e->getMessage());
        }
    }

    public function changeStatus(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'status' => 'required|string',
            'admin_notes' => 'nullable|string|max:1000'
        ]);

        $newStatus = $validated['status'];
        $statusService = app(\App\Services\AbstractStatusService::class);

        // Prepare metadata/additional data
        $metadata = [];
        if ($request->has('admin_notes') && !empty($request->admin_notes)) {
            $metadata['admin_comment'] = $request->admin_notes;
        }

        // Handle specific logic for 'revision' if it's the target
        if ($newStatus === 'revision') {
            // Map 'revision' to the system's preferred revision status if needed
            // (The service usually handles this via decision mappings)
            $metadata['revision_feedback'] = $request->input('admin_notes') ?: $request->input('admin_comment');
            $metadata['revision_requested_at'] = now();
            $metadata['revision_round'] = ($abstract->revision_round ?? 0) + 1;
        }

        $result = $statusService->changeStatus(
            $abstract,
            $newStatus,
            $request->admin_notes ?? 'Status changed by Admin',
            $metadata
        );

        if (!$result['success']) {
            return redirect()->back()->with('error', $result['message']);
        }

        // Success message
        $message = "Status updated to: " . ucfirst($newStatus);

        // If this is a final decision (accepted, rejected, revision), redirect to dashboard
        if (in_array($newStatus, ['accepted', 'rejected', 'revision', 'minor_revision_required', 'major_revision_required'])) {
            return redirect()->route('admin.dashboard')->with('success', $message);
        }

        return redirect()->back()->with('success', $message);
    }

    public function assignConferenceCode(Request $request, AbstractSubmission $abstract)
    {
        $validated = $request->validate([
            'conference_code' => 'required|string|max:20|unique:abstract_submissions,conference_code,' . $abstract->id,
            'committee_notes' => 'nullable|string|max:1000',
            'committee_selected' => 'boolean',
            'presentation_mode' => 'nullable|string|in:oral,poster,Oral,Poster',
            'presentation_session' => 'nullable|string|max:255'
        ]);

        // Check if code already exists (excluding current abstract)
        $existingCode = AbstractSubmission::where('conference_code', $validated['conference_code'])
                                        ->where('id', '!=', $abstract->id)
                                        ->first();

        if ($existingCode) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => "Conference code '{$validated['conference_code']}' is already assigned to Abstract #{$existingCode->id}: \"{$existingCode->title}\""
                ], 422);
            }
            return redirect()->back()->with('error',
                "Conference code '{$validated['conference_code']}' is already assigned to Abstract #{$existingCode->id}: \"{$existingCode->title}\"");
        }

        // Update the abstract with conference code
        $updateData = [
            'conference_code' => strtoupper($validated['conference_code']), // Always uppercase
            'committee_notes' => $validated['committee_notes'] ?? null,
            'committee_selected' => $request->has('committee_selected'),
            'code_assigned_by' => Auth::id(),
            'code_assigned_at' => now(),
            'code_is_final' => true,
        ];

        if (isset($validated['presentation_mode'])) {
            $updateData['presentation_mode'] = $validated['presentation_mode'];
        }
        if (isset($validated['presentation_session'])) {
            $updateData['presentation_session'] = $validated['presentation_session'];
        }

        $abstract->update($updateData);

        // Send conference code assignment email
        $emailService = new EmailNotificationService();
        $emailSent = $emailService->sendConferenceCodeAssignment($abstract);

        if (!$emailSent) {
            Log::warning("Failed to send conference code assignment email for abstract #{$abstract->id}");
        }

        // Log the assignment
        Log::info('Conference code assigned', [
            'abstract_id' => $abstract->id,
            'conference_code' => $validated['conference_code'],
            'assigned_by' => Auth::user()->email,
            'committee_selected' => $request->has('committee_selected')
        ]);

        $message = "Conference code '{$validated['conference_code']}' has been assigned successfully.";

        if ($request->has('committee_selected')) {
            $message .= " This abstract is marked as specially selected by the Scientific Committee.";
        }

        // Return JSON response for AJAX requests
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function toggleBlindReview(AbstractSubmission $abstract)
    {
        $abstract->update([
            'blind_review_enabled' => !$abstract->blind_review_enabled,
            'blind_review_toggled_at' => now(),
            'blind_review_toggled_by' => Auth::id()
        ]);

        $status = $abstract->blind_review_enabled ? 'enabled' : 'disabled';
        Log::info("Blind review {$status} for abstract #{$abstract->id} by admin " . Auth::id());

        return redirect()->back()->with('success', "Blind review {$status} successfully.");
    }

    public function clearReviewers(AbstractSubmission $abstract)
    {
        // Check if reviews have already been submitted
        if ($abstract->reviewer_1_score !== null || $abstract->reviewer_2_score !== null || $abstract->reviews()->where('status', 'submitted')->count() > 0) {
            return redirect()->back()->with('error', 'Cannot clear reviewers: reviews have already been submitted.');
        }

        // Clear reviewer assignments and scores
        $abstract->update([
            'reviewer_id' => null,
            'reviewer_2_id' => null,
            'reviewer_1_score' => null,
            'reviewer_2_score' => null,
            'reviewer_1_notes' => null,
            'reviewer_2_notes' => null,
            'review_completed_at' => null,
            'reviewers_cleared_at' => now(),
            'reviewers_cleared_by' => Auth::id()
        ]);

        Log::info("Reviewers cleared for abstract #{$abstract->id} by admin " . Auth::id());

        return redirect()->back()->with('success', 'Reviewers cleared successfully.');
    }

    public function getEligibleReviewers(AbstractSubmission $abstract)
    {
        $eligibleReviewers = User::whereHas('roles', function ($query) {
            $query->where('name', 'reviewer');
        })->get();

        return response()->json($eligibleReviewers);
    }

    public function pendingAbstracts()
    {
        $abstracts = AbstractSubmission::with(['user', 'reviewer1', 'reviewer2'])
            ->where('status', 'submitted')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.abstracts.index', compact('abstracts'));
    }

    public function underReviewAbstracts()
    {
        $abstracts = AbstractSubmission::with(['user', 'reviewer1', 'reviewer2'])
            ->where('status', 'under_review')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.abstracts.index', compact('abstracts'));
    }

    public function completedAbstracts()
    {
        $abstracts = AbstractSubmission::with(['user', 'reviewer1', 'reviewer2'])
            ->whereIn('status', ['accepted', 'rejected', 'revision'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.abstracts.index', compact('abstracts'));
    }

    /**
     * Get enhanced statistics for abstract management
     */
    private function getEnhancedAbstractStats()
    {
        $totalAbstracts = AbstractSubmission::where('status', '!=', 'draft')->count();
        $nonAssignableStatuses = [
            'accepted',
            'rejected',
            'withdrawn',
            'ready_for_decision',
            'revision',
            'revision_requested',
            'revision_required',
            'minor_revision_required',
            'major_revision_required',
            'revision_submitted',
            'minor_revision_submitted',
            'major_revision_submitted',
            'revision_under_review',
            'revision_review',
        ];

        // Basic status counts
        $statusCounts = [
            'submitted' => AbstractSubmission::where('status', 'submitted')->count(),
            'reviewer_assigned' => AbstractSubmission::where('status', 'reviewer_assigned')->count(),
            'under_review' => AbstractSubmission::where('status', 'under_review')->count(),
            'accepted' => AbstractSubmission::where('status', 'accepted')->count(),
            'rejected' => AbstractSubmission::where('status', 'rejected')->count(),
            'minor_revision' => AbstractSubmission::where('status', 'minor_revision')->count(),
            'major_revision' => AbstractSubmission::where('status', 'major_revision')->count(),
            'scheduled' => AbstractSubmission::where('status', 'scheduled')->count(),
        ];

        // Reviewer assignment analysis
        $reviewerStats = [
            'unassigned' => AbstractSubmission::where('status', '!=', 'draft')
                ->where(function($query) {
                    $query->whereNull('reviewer_id')->orWhereNull('reviewer_2_id');
                })->count(),
            'partially_assigned' => AbstractSubmission::where('status', '!=', 'draft')
                ->where(function($query) {
                    $query->where(function($q) {
                        $q->whereNotNull('reviewer_id')->whereNull('reviewer_2_id');
                    })->orWhere(function($q) {
                        $q->whereNull('reviewer_id')->whereNotNull('reviewer_2_id');
                    });
                })->count(),
            'fully_assigned' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereNotNull('reviewer_id')
                ->whereNotNull('reviewer_2_id')
                ->count(),
        ];

        // Enhanced review progress analysis - using actual reviews table
        $reviewProgress = [
            'unassigned' => AbstractSubmission::where('status', '!=', 'draft')
                ->where(function($query) {
                    $query->whereNull('reviewer_id')
                          ->orWhereNull('reviewer_2_id');
                })->count(),
            'assigned_no_reviews' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereNotNull('reviewer_id')
                ->whereNotNull('reviewer_2_id')
                ->whereDoesntHave('reviews', function($query) {
                    $query->where('status', 'submitted');
                })->count(),
            'partial_review' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereNotNull('reviewer_id')
                ->whereNotNull('reviewer_2_id')
                ->whereHas('reviews', function($query) {
                    $query->where('status', 'submitted');
                }, '=', 1)
                ->count(),
            'fully_reviewed' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereNotNull('reviewer_id')
                ->whereNotNull('reviewer_2_id')
                ->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_submission_id = abstract_submissions.id AND status = \'submitted\') >= 2')
                ->count(),
            'no_reviews' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereDoesntHave('reviews', function($query) {
                    $query->where('status', 'submitted');
                })->count(),
            'one_review' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_submission_id = abstract_submissions.id AND status = \'submitted\') = 1')
                ->count(),
            'both_reviews' => AbstractSubmission::where('status', '!=', 'draft')
                ->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_submission_id = abstract_submissions.id AND status = \'submitted\') = 2')
                ->count(),
        ];

        // Timeline analysis
        $timelineStats = [
            'overdue_review' => AbstractSubmission::where('status', 'under_review')
                ->where('assigned_at', '<', now()->subDays(14))
                ->count(),
            'overdue_decision' => AbstractSubmission::where('status', 'ready_for_decision')
                ->where('review_completed_at', '<', now()->subDays(7))
                ->count(),
            'recent_submissions' => AbstractSubmission::where('status', '!=', 'draft')
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
        ];

        // Subtheme distribution
        $subthemeStats = AbstractSubmission::where('status', '!=', 'draft')
            ->selectRaw('subtheme, COUNT(*) as count')
            ->groupBy('subtheme')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'subtheme')
            ->toArray();

        // Quality metrics - using actual reviews table
        $avgScore = \App\Models\AbstractReview::where('status', 'submitted')->avg('score');

        $qualityStats = [
            'avg_score' => $avgScore ? round($avgScore, 1) : 0,
            'high_quality' => \App\Models\AbstractReview::where('status', 'submitted')
                ->selectRaw('abstract_submission_id, AVG(score) as avg_score')
                ->groupBy('abstract_submission_id')
                ->havingRaw('AVG(score) >= 85')
                ->count(),
            'medium_quality' => \App\Models\AbstractReview::where('status', 'submitted')
                ->selectRaw('abstract_submission_id, AVG(score) as avg_score')
                ->groupBy('abstract_submission_id')
                ->havingRaw('AVG(score) >= 70 AND AVG(score) < 85')
                ->count(),
            'low_quality' => \App\Models\AbstractReview::where('status', 'submitted')
                ->selectRaw('abstract_submission_id, AVG(score) as avg_score')
                ->groupBy('abstract_submission_id')
                ->havingRaw('AVG(score) < 70')
                ->count(),
        ];

        return [
            'total' => $totalAbstracts,
            'status_counts' => $statusCounts,
            'reviewer_stats' => $reviewerStats,
            'review_progress' => $reviewProgress,
            'repository_filters' => [
                'all' => $totalAbstracts,
                'not_assigned' => AbstractSubmission::where('status', '!=', 'draft')
                    ->whereNotIn('status', $nonAssignableStatuses)
                    ->whereNull('reviewer_id')
                    ->whereNull('reviewer_2_id')
                    ->whereDoesntHave('reviews', function ($query) {
                        $query->where('status', 'submitted');
                    })
                    ->count(),
                'assigned_one_reviewer' => AbstractSubmission::where('status', '!=', 'draft')
                    ->whereNotIn('status', $nonAssignableStatuses)
                    ->where(function ($query) {
                        $query->where(function ($q) {
                            $q->whereNotNull('reviewer_id')
                                ->whereNull('reviewer_2_id');
                        })->orWhere(function ($q) {
                            $q->whereNull('reviewer_id')
                                ->whereNotNull('reviewer_2_id');
                        });
                    })
                    ->count(),
                'unreviewed' => AbstractSubmission::whereNotIn('status', [
                        'draft',
                        'accepted',
                        'rejected',
                        'withdrawn',
                        'ready_for_decision',
                        'revision',
                        'revision_requested',
                        'revision_required',
                        'minor_revision_required',
                        'major_revision_required',
                        'revision_submitted',
                        'minor_revision_submitted',
                        'major_revision_submitted',
                        'revision_under_review',
                        'revision_review',
                    ])
                    ->whereNotNull('reviewer_id')
                    ->whereNotNull('reviewer_2_id')
                    ->whereDoesntHave('reviews', function ($query) {
                        $query->where('status', 'submitted');
                    })
                    ->count(),
                'partial_review' => AbstractSubmission::whereNotIn('status', [
                        'draft',
                        'accepted',
                        'rejected',
                        'withdrawn',
                        'ready_for_decision',
                        'revision',
                        'revision_requested',
                        'revision_required',
                        'minor_revision_required',
                        'major_revision_required',
                        'revision_submitted',
                        'minor_revision_submitted',
                        'major_revision_submitted',
                        'revision_under_review',
                        'revision_review',
                    ])
                    ->whereNotNull('reviewer_id')
                    ->whereNotNull('reviewer_2_id')
                    ->whereHas('reviews', function ($query) {
                        $query->where('status', 'submitted');
                    }, '=', 1)
                    ->count(),
                'accepted' => $statusCounts['accepted'] ?? 0,
                'rejected' => $statusCounts['rejected'] ?? 0,
                'ready_for_decision' => AbstractSubmission::where('status', 'ready_for_decision')
                    ->orWhere(function ($query) {
                        $query->where('status', 'under_review')
                            ->whereHas('reviews', function ($q) {
                                $q->where('status', 'submitted');
                            }, '>=', 2);
                    })
                    ->count(),
                'revision_with_authors' => AbstractSubmission::whereIn('status', [
                    'revision',
                    'revision_requested',
                    'revision_required',
                    'minor_revision_required',
                    'major_revision_required',
                ])->count(),
                'revision_with_reviewers' => AbstractSubmission::whereIn('status', [
                    'revision_submitted',
                    'minor_revision_submitted',
                    'major_revision_submitted',
                    'revision_under_review',
                    'revision_review',
                ])->count(),
            ],
            'timeline_stats' => $timelineStats,
            'subtheme_stats' => $subthemeStats,
            'quality_stats' => $qualityStats,
            'acceptance_rate' => $totalAbstracts > 0 ? round(($statusCounts['accepted'] / $totalAbstracts) * 100, 1) : 0,
            'completion_rate' => $totalAbstracts > 0 ? round((($statusCounts['accepted'] + $statusCounts['rejected']) / $totalAbstracts) * 100, 1) : 0,
        ];
    }

    /**
     * Apply enhanced status filtering based on actual review progress
     */
    private function applyStatusFilter($query, $status)
    {
        $nonAssignableStatuses = [
            'accepted',
            'rejected',
            'withdrawn',
            'ready_for_decision',
            'revision',
            'revision_requested',
            'revision_required',
            'minor_revision_required',
            'major_revision_required',
            'revision_submitted',
            'minor_revision_submitted',
            'major_revision_submitted',
            'revision_under_review',
            'revision_review',
        ];

        switch ($status) {
            case 'unreviewed':
                // Both reviewers assigned, no submitted reviews, and still in the normal peer-review lane
                $query->whereNotNull('reviewer_id')
                    ->whereNotNull('reviewer_2_id')
                    ->whereNotIn('status', [
                        'accepted',
                        'rejected',
                        'withdrawn',
                        'ready_for_decision',
                        'revision',
                        'revision_requested',
                        'revision_required',
                        'minor_revision_required',
                        'major_revision_required',
                        'revision_submitted',
                        'minor_revision_submitted',
                        'major_revision_submitted',
                        'revision_under_review',
                        'revision_review',
                    ])
                    ->whereDoesntHave('reviews', function ($q) {
                        $q->where('status', 'submitted');
                    });
                break;

            case 'partial_review':
                // Exactly one submitted review in the normal peer-review lane
                $query->whereNotIn('status', [
                        'accepted',
                        'rejected',
                        'withdrawn',
                        'ready_for_decision',
                        'revision',
                        'revision_requested',
                        'revision_required',
                        'minor_revision_required',
                        'major_revision_required',
                        'revision_submitted',
                        'minor_revision_submitted',
                        'major_revision_submitted',
                        'revision_under_review',
                        'revision_review',
                    ])
                    ->whereNotNull('reviewer_id')
                    ->whereNotNull('reviewer_2_id')
                    ->whereHas('reviews', function ($q) {
                        $q->where('status', 'submitted');
                    }, '=', 1);
                break;

            case 'fully_reviewed':
                // Both reviews in, but no decision yet
                $query->whereRaw('(SELECT COUNT(DISTINCT reviewer_id) FROM abstract_reviews WHERE abstract_submission_id = abstract_submissions.id AND status = \'submitted\') >= 2')
                      ->whereNotIn('status', ['accepted', 'rejected']);
                break;
            
            case 'ready_for_decision':
                // Match the admin dashboard card logic exactly
                $query->where(function ($q) {
                    $q->where('status', 'ready_for_decision')
                      ->orWhere(function ($sub) {
                          $sub->where('status', 'under_review')
                              ->whereHas('reviews', function ($reviewQuery) {
                                  $reviewQuery->where('status', 'submitted');
                              }, '>=', 2);
                      });
                });
                break;

            case 'unassigned':
                $query->where(function ($q) {
                    $q->whereNull('reviewer_id')
                      ->orWhereNull('reviewer_2_id');
                });
                break;

            case 'not_assigned':
                $query->whereNull('reviewer_id')
                      ->whereNull('reviewer_2_id')
                      ->whereNotIn('status', $nonAssignableStatuses)
                      ->whereDoesntHave('reviews', function ($q) {
                          $q->where('status', 'submitted');
                      });
                break;

            case 'assigned_one_reviewer':
                $query->whereNotIn('status', $nonAssignableStatuses)
                    ->where(function ($q) {
                        $q->where(function ($q2) {
                            $q2->whereNotNull('reviewer_id')
                               ->whereNull('reviewer_2_id');
                        })->orWhere(function ($q2) {
                            $q2->whereNull('reviewer_id')
                               ->whereNotNull('reviewer_2_id');
                        });
                    });
                break;

            case 'assigned_both_reviewers':
                $query->whereNotNull('reviewer_id')
                      ->whereNotNull('reviewer_2_id')
                      ->whereNotIn('status', [
                          'accepted',
                          'rejected',
                          'withdrawn',
                          'revision',
                          'revision_requested',
                          'revision_required',
                          'minor_revision_required',
                          'major_revision_required',
                      ]);
                break;

            case 'revision_with_authors':
                $query->whereIn('status', [
                    'revision',
                    'revision_requested',
                    'revision_required',
                    'minor_revision_required',
                    'major_revision_required',
                ]);
                break;

            case 'revision_with_reviewers':
                $query->whereIn('status', [
                    'revision_submitted',
                    'minor_revision_submitted',
                    'major_revision_submitted',
                    'revision_under_review',
                    'revision_review',
                ]);
                break;

            case 'conflicts':
                $query->whereRaw('EXISTS (
                    SELECT 1 FROM abstract_reviews ar1
                    JOIN abstract_reviews ar2 ON ar1.abstract_submission_id = ar2.abstract_submission_id
                    WHERE ar1.abstract_submission_id = abstract_submissions.id
                    AND ar1.status = \'submitted\'
                    AND ar2.status = \'submitted\'
                    AND ar1.id != ar2.id
                    AND NOT (
                        (ar1.recommendation IN (\'accept_oral\', \'accept_poster\', \'accept\') AND ar2.recommendation IN (\'accept_oral\', \'accept_poster\', \'accept\')) OR
                        (ar1.recommendation = \'reject\' AND ar2.recommendation = \'reject\') OR
                        (ar1.recommendation LIKE \'%revision%\' AND ar2.recommendation LIKE \'%revision%\')
                    )
                )')->whereNotIn('status', ['accepted', 'rejected']);
                break;

            case 'initial_review':
                $query->where('status', 'under_review')->where(function($q) {
                    $q->where('revision_round', 0)->orWhereNull('revision_round');
                });
                break;

            case 'revision_review':
                $query->where('status', 'under_review')->where('revision_round', '>', 0);
                break;

            case 'accepted':
                $query->where('status', 'accepted');
                break;

            case 'rejected':
                $query->where('status', 'rejected');
                break;

            default:
                $query->where('status', $status);
                break;
        }
    }

    /**
     * Process automatic status updates for abstracts
     */
    private function processAutoStatusUpdates()
    {
        Log::info('processAutoStatusUpdates called');

        // Only move newly assigned submissions into 'under_review'.
        // Do NOT override explicit or terminal statuses (accepted/rejected/revision states/etc.).
        $submittedUpdated = AbstractSubmission::whereNotNull('reviewer_id')
            ->whereNotNull('reviewer_2_id')
            ->whereIn('status', ['submitted'])
            ->update([
                'status' => 'under_review',
                'assigned_at' => now(),
            ]);

        // Process resubmitted revisions - move them to revision_review status
        $revisionUpdated = AbstractSubmission::whereIn('status', ['minor_revision_submitted', 'major_revision_submitted', 'revision_submitted'])
            ->whereNotNull('revision_submitted_at')
            ->update([
                'status' => 'revision_review',
            ]);

        Log::info('processAutoStatusUpdates completed', [
            'submitted_updated' => $submittedUpdated,
            'revision_updated' => $revisionUpdated
        ]);
    }

    /**
     * Request revision from author
     */
    public function requestRevision(Request $request, AbstractSubmission $abstract)
    {
        Log::info("requestRevision method called for abstract {$abstract->id}");
        Log::info("Request data: " . json_encode($request->all()));

        try {
            $validated = $request->validate([
                'feedback' => 'required|string|min:10|max:2000',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error("Validation failed for revision request: " . json_encode($e->errors()));
            return redirect()->back()->withErrors($e->errors())->withInput();
        }

        try {
            // Use the RevisionWorkflowService instead of direct updates
            $revisionService = app(\App\Services\RevisionWorkflowService::class);

            $result = $revisionService->requestRevision(
                $abstract,
                $validated['feedback'],
                Auth::user(),
                'standard'
            );

            if ($result['success']) {
                Log::info("Revision requested successfully for abstract {$abstract->id} by admin " . Auth::id());

                return redirect()->route('admin.abstracts.view', $abstract)
                    ->with('success', $result['message']);
            } else {
                return redirect()->back()
                    ->with('error', $result['message'])
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error("Failed to request revision for abstract {$abstract->id}: " . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Failed to request revision. Please try again.')
                ->withInput();
        }
    }

    /**
     * Display a listing of presentations (accepted abstracts)
     */
    public function presentations(Request $request, \App\Services\PresentationDownloadService $downloads)
    {
        $query = AbstractSubmission::with(['user'])
            ->where('status', 'accepted');

        // Apply filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('author_name', 'like', "%{$search}%")
                  ->orWhere('conference_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('presentation_status') && $request->presentation_status !== 'all') {
            if ($request->presentation_status === 'submitted') {
                $query->whereIn('presentation_status', ['uploaded', 'approved']);
            } else {
                $query->where('presentation_status', $request->presentation_status);
            }
        }

        if ($request->filled('mode')) {
            $query->where('presentation_mode', $request->mode);
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'conference_code');
        $sortOrder = $request->get('sort_order', 'asc');
        $allowedSorts = ['conference_code', 'title', 'author_name', 'presentation_mode', 'presentation_status', 'presentation_uploaded_at'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('conference_code', 'asc');
        }

        $abstracts = $query->paginate(50);

        $stats = [
            'total' => AbstractSubmission::where('status', 'accepted')->count(),
            'uploaded' => AbstractSubmission::where('status', 'accepted')->whereIn('presentation_status', ['uploaded', 'approved'])->count(),
            'approved' => AbstractSubmission::where('status', 'accepted')->where('presentation_status', 'approved')->count(),
            'pending' => AbstractSubmission::where('status', 'accepted')->where('presentation_status', 'pending')->count(),
        ];

        $conferenceDays = $downloads->conferenceDays();

        return view('admin.abstracts.presentations', compact('abstracts', 'stats', 'conferenceDays'));
    }

    /**
     * Download presentation file
     */
    public function downloadPresentation(AbstractSubmission $abstract, $type)
    {
        $file = $this->resolvePresentationFile($abstract, $type);

        if (!$file) {
            return redirect()->back()->with('error', 'File not found or not yet uploaded.');
        }

        return Storage::disk('public')->download($file['storage_path'], $file['download_name']);
    }

    /**
     * Preview presentation file in browser when supported.
     */
    public function previewPresentation(AbstractSubmission $abstract, $type)
    {
        $file = $this->resolvePresentationFile($abstract, $type);

        if (!$file) {
            return redirect()->back()->with('error', 'File not found or not yet uploaded.');
        }

        $headers = [
            'Content-Type' => Storage::disk('public')->mimeType($file['storage_path']) ?: 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="' . str_replace('"', '', $file['download_name']) . '"',
        ];

        return response()->file(Storage::disk('public')->path($file['storage_path']), $headers);
    }

    /**
     * Resolve an admin presentation file request to a stored file.
     */
    private function resolvePresentationFile(AbstractSubmission $abstract, string $type): ?array
    {
        $filePath = match ($type) {
            'oral' => $abstract->oral_presentation_file,
            'poster' => $abstract->poster_presentation_file,
            'audio', 'audio_poster_audio' => $abstract->audio_poster_file,
            'audio_poster_poster' => $abstract->audio_poster_poster_file,
            default => null,
        };

        if (!$filePath) {
            return null;
        }

        $storagePath = 'presentations/' . $filePath;

        if (!Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        $labels = [
            'oral' => 'Oral',
            'poster' => 'Poster',
            'audio' => 'Audio',
            'audio_poster_audio' => 'Audio',
            'audio_poster_poster' => 'Poster_Visual',
        ];

        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $code = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($abstract->conference_code ?? $abstract->id));
        $label = $labels[$type] ?? 'Presentation';

        return [
            'storage_path' => $storagePath,
            'download_name' => "{$label}_{$code}.{$extension}",
        ];
    }

}
