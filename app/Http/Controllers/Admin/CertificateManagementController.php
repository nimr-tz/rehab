<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin Certificate Management Controller
 *
 * Handles administrative tasks for certificate management including:
 * - Viewing all issued certificates
 * - Revoking certificates
 * - Exporting certificate records
 * - Certificate statistics
 */
class CertificateManagementController extends Controller
{
    /**
     * Display a listing of all certificates
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['user', 'abstract', 'revokedByUser']);

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'valid') {
                $query->whereNull('revoked_at');
            } elseif ($request->status === 'revoked') {
                $query->whereNotNull('revoked_at');
            }
        }

        // Search by user name, email, or certificate number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('certificate_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'issued_at');
        $sortDir = $request->get('dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $certificates = $query->paginate(25)->withQueryString();

        // Statistics
        $stats = [
            'total' => Certificate::count(),
            'attendance_full' => Certificate::where('type', Certificate::TYPE_ATTENDANCE_FULL)->count(),
            'attendance_partial' => Certificate::where('type', Certificate::TYPE_ATTENDANCE_PARTIAL)->count(),
            'oral_presentations' => Certificate::where('type', Certificate::TYPE_ORAL_PRESENTATION)->count(),
            'poster_presentations' => Certificate::where('type', Certificate::TYPE_POSTER_PRESENTATION)->count(),
            'revoked' => Certificate::whereNotNull('revoked_at')->count(),
            'total_downloads' => Certificate::sum('download_count'),
            'total_verifications' => Certificate::sum('verification_count'),
        ];

        return view('admin.certificates.index', compact('certificates', 'stats'));
    }

    /**
     * Revoke a certificate
     */
    public function revoke(Request $request, Certificate $certificate)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if ($certificate->isRevoked()) {
            return back()->with('error', 'This certificate has already been revoked.');
        }

        $certificate->revoke(Auth::id(), $request->reason);

        return back()->with('success', "Certificate {$certificate->certificate_number} has been revoked.");
    }

    /**
     * Reinstate a revoked certificate
     */
    public function reinstate(Certificate $certificate)
    {
        if (! $certificate->isRevoked()) {
            return back()->with('error', 'This certificate is not revoked.');
        }

        $certificate->update([
            'revoked_at' => null,
            'revoked_by' => null,
            'revoked_reason' => null,
        ]);

        return back()->with('success', "Certificate {$certificate->certificate_number} has been reinstated.");
    }

    /**
     * Export certificates to CSV
     */
    public function export(Request $request)
    {
        $query = Certificate::with(['user', 'abstract']);

        // Apply same filters as index
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'valid') {
                $query->whereNull('revoked_at');
            } elseif ($request->status === 'revoked') {
                $query->whereNotNull('revoked_at');
            }
        }

        $certificates = $query->orderBy('issued_at', 'desc')->get();

        $filename = 'certificates_'.now()->format('Y-m-d_His').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($certificates) {
            $file = fopen('php://output', 'w');

            // CSV Header
            fputcsv($file, [
                'Certificate Number',
                'Type',
                'Holder Name',
                'Holder Email',
                'Affiliation',
                'Abstract Title',
                'Issued At',
                'Downloads',
                'Verifications',
                'Status',
                'Revoked At',
                'Revoked Reason',
            ]);

            // CSV Data
            foreach ($certificates as $cert) {
                fputcsv($file, [
                    $cert->certificate_number,
                    $cert->type_label,
                    $cert->user->full_name,
                    $cert->user->email,
                    $cert->user->affiliation ?? '',
                    $cert->abstract?->title ?? '',
                    $cert->issued_at->format('Y-m-d H:i:s'),
                    $cert->download_count,
                    $cert->verification_count,
                    $cert->isRevoked() ? 'Revoked' : 'Valid',
                    $cert->revoked_at?->format('Y-m-d H:i:s') ?? '',
                    $cert->revoked_reason ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Preview any user's certificate (admin only)
     */
    public function previewUser(User $user, Request $request)
    {
        $type = $request->get('type', Certificate::TYPE_ATTENDANCE_FULL);

        // Find existing certificate or create preview data
        $certificate = Certificate::where('user_id', $user->id)
            ->where('type', $type)
            ->first();

        if (! $certificate) {
            $attendanceDays = $this->getUserAttendanceDays($user);
            $certificateType = count($attendanceDays) >= 3 ? Certificate::TYPE_ATTENDANCE_FULL : Certificate::TYPE_ATTENDANCE_PARTIAL;

            // Create temporary certificate for preview based on ACTUAL data
            $certificate = new Certificate([
                'user_id' => $user->id,
                'type' => $type ?? $certificateType,
                'certificate_number' => Certificate::generateCertificateNumber($type ?? $certificateType),
                'attendance_days' => $attendanceDays,
                'issued_at' => Certificate::officialIssueDate(),
            ]);
        }

        $controller = app(\App\Http\Controllers\CertificateController::class);

        return $controller->preview($request, $certificate);
    }

    /**
     * Bulk issue certificates for all eligible users
     */
    public function bulkIssue(Request $request)
    {
        $type = $request->get('type', Certificate::TYPE_ATTENDANCE_FULL);
        $issued = 0;
        $skipped = 0;

        // Get all users with attendance records
        $users = User::whereHas('attendances')->get();

        foreach ($users as $user) {
            // Check if certificate already exists
            $exists = Certificate::where('user_id', $user->id)
                ->where('type', $type)
                ->whereNull('abstract_submission_id')
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            // Get attendance days
            $attendanceDays = $this->getUserAttendanceDays($user);
            $certificateType = count($attendanceDays) >= 3
                ? Certificate::TYPE_ATTENDANCE_FULL
                : Certificate::TYPE_ATTENDANCE_PARTIAL;

            // Only issue requested type
            if ($certificateType !== $type && $type !== 'attendance_any') {
                $skipped++;

                continue;
            }

            // Create certificate
            Certificate::create([
                'user_id' => $user->id,
                'type' => $certificateType,
                'certificate_number' => Certificate::generateCertificateNumber($certificateType),
                'attendance_days' => $attendanceDays,
                'issued_at' => Certificate::officialIssueDate(),
            ]);

            $issued++;
        }

        return back()->with('success', "Bulk issue complete: {$issued} certificates issued, {$skipped} skipped.");
    }

    /**
     * Get user's attendance days
     */
    private function getUserAttendanceDays(User $user): array
    {
        $days = $user->attendances()
            ->whereBetween('day', [1, 3])
            ->pluck('day')
            ->toArray();

        $days = array_unique($days);
        sort($days);

        return $days;
    }
}
