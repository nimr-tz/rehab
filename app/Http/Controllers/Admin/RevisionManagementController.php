<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Services\RevisionWorkflowService;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RevisionManagementController extends Controller
{
    protected RevisionWorkflowService $revisionService;
    protected EmailNotificationService $emailService;

    public function __construct(
        RevisionWorkflowService $revisionService,
        EmailNotificationService $emailService
    ) {
        $this->revisionService = $revisionService;
        $this->emailService = $emailService;
    }

    /**
     * Export revisions data to CSV
     */
    public function exportRevisions(Request $request)
    {
        $filter = $request->get('filter', 'all');

        // Get all abstracts in revision workflow
        $allAbstracts = $this->revisionService->getAbstractsInRevisionWorkflow();

        // Apply same filters as index
        switch($filter) {
            case 'pending_admin':
                $abstracts = $allAbstracts->whereIn('status', [
                    RevisionWorkflowService::STATUS_REVISION_SUBMITTED
                ]);
                break;
            case 'pending_reviewers':
                $abstracts = $allAbstracts->whereIn('status', [
                    RevisionWorkflowService::STATUS_REVISION_FINAL_REVIEW
                ]);
                break;
            case 'requested':
                $abstracts = $allAbstracts->whereIn('status', [
                    RevisionWorkflowService::STATUS_REVISION_REQUESTED
                ]);
                break;
            case 'in_progress':
                $abstracts = $allAbstracts->whereIn('status', [
                    RevisionWorkflowService::STATUS_REVISION_IN_PROGRESS
                ]);
                break;
            default:
                $abstracts = $allAbstracts;
        }

        $filename = 'revisions_' . $filter . '_' . date('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($abstracts) {
            $file = fopen('php://output', 'w');

            // CSV Headers
            fputcsv($file, [
                'ID',
                'Title',
                'Author',
                'Email',
                'Status',
                'Revision Round',
                'Workflow Step',
                'Requested At',
                'Submitted At',
                'Approved At',
                'Deadline',
                'Reviewer 1',
                'Reviewer 2',
                'Created At'
            ]);

            foreach ($abstracts as $abstract) {
                fputcsv($file, [
                    $abstract->id,
                    $abstract->title,
                    $abstract->user->name ?? '',
                    $abstract->user->email ?? '',
                    ucfirst(str_replace('_', ' ', $abstract->status)),
                    $abstract->revision_round ?? 0,
                    ucfirst(str_replace('_', ' ', $abstract->revision_workflow_step ?? '')),
                    $this->formatDate($abstract->revision_requested_at),
                    $this->formatDate($abstract->revision_submitted_at),
                    $this->formatDate($abstract->revision_approved_at),
                    $this->formatDate($abstract->revision_deadline),
                    $abstract->reviewer1->name ?? '',
                    $abstract->reviewer2->name ?? '',
                    $this->formatDate($abstract->created_at)
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Safely format a date field that might be a string or datetime object
     */
    private function formatDate($date): string
    {
        if (!$date) {
            return '';
        }

        // If it's already a Carbon/DateTime object
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H:i:s');
        }

        // If it's a string, try to parse it
        if (is_string($date)) {
            try {
                return \Carbon\Carbon::parse($date)->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                return $date; // Return as-is if parsing fails
            }
        }

        return '';
    }
}
