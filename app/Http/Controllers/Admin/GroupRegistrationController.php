<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupRegistration;
use App\Models\GroupMember;
use App\Services\EmailNotificationService;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GroupRegistrationController extends Controller
{
    protected $emailService;
    protected $notificationService;

    public function __construct(EmailNotificationService $emailService, NotificationService $notificationService)
    {
        $this->emailService = $emailService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display list of all group registrations
     */
    public function index(Request $request)
    {
        $query = GroupRegistration::with(['leader', 'members'])
            ->withCount('members');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('payment_status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('group_name', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhereHas('leader', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('members', function ($q) use ($search) {
                      $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        $groupRegistrations = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => GroupRegistration::count(),
            'pending' => GroupRegistration::where('payment_status', 'pending')->count(),
            'submitted' => GroupRegistration::where('payment_status', 'submitted')->count(),
            'verified' => GroupRegistration::where('payment_status', 'verified')->count(),
            'total_members' => GroupMember::count(),
            'verified_members' => GroupMember::whereHas('groupRegistration', fn($q) => $q->where('payment_status', 'verified'))->count(),
        ];

        return view('admin.group-registrations.index', compact('groupRegistrations', 'stats'));
    }

    /**
     * Show a specific group registration
     */
    public function show(GroupRegistration $groupRegistration)
    {
        $groupRegistration->load(['leader', 'members', 'verifier']);
        
        return view('admin.group-registrations.show', compact('groupRegistration'));
    }


    /**
     * Export group members to CSV
     */
    public function export(Request $request)
    {
        $query = GroupMember::with('groupRegistration.leader');

        // Only verified groups
        if ($request->get('verified_only', true)) {
            $query->whereHas('groupRegistration', fn($q) => $q->where('payment_status', 'verified'));
        }

        $members = $query->get();

        $filename = 'group_members_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($members) {
            $file = fopen('php://output', 'w');
            
            // Header row
            fputcsv($file, [
                'Group ID',
                'Group Name',
                'Organization',
                'Leader Name',
                'Leader Email',
                'Member Name',
                'Member Email',
                'Member Phone',
                'Institution',
                'Country',
                'Category',

                'QR Token',
                'Checked In',
                'Payment Status',
            ]);

            foreach ($members as $member) {
                $group = $member->groupRegistration;
                fputcsv($file, [
                    $group->id,
                    $group->group_name,
                    $group->organization,
                    $group->leader->full_name ?? '',
                    $group->leader->email ?? '',
                    $member->full_name,
                    $member->email,
                    $member->phone,
                    $member->institution,
                    $member->country,
                    $member->category_label,

                    $member->qr_token,
                    $member->checked_in ? 'Yes' : 'No',
                    $group->payment_status,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export group registrations to Excel (.xlsx)
     */
    public function exportExcel(Request $request)
    {
        $groups = GroupRegistration::with(['leader', 'members', 'verifier'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Group Registrations');

        $headers = [
            'ID', 'Group Name', 'Organization', 'Leader Name', 'Leader Email',
            'Members', 'Payment Status', 'Payment Date', 'Payment Type', 'Verified By', 'Created',
        ];
        $sheet->fromArray($headers, null, 'A1');

        // Bold header row
        $sheet->getStyle('A1:K1')->getFont()->setBold(true);

        $row = 2;
        foreach ($groups as $group) {
            $paymentDate = null;
            $paymentType = null;
            if ($group->payment_verified_at && in_array($group->payment_status, ['verified', 'waived'])) {
                $paymentDate = $group->payment_verified_at->format('Y-m-d');
                $paymentType = $group->payment_status === 'waived' ? 'Waived' : 'Paid';
            }

            $sheet->fromArray([
                $group->id,
                $group->group_name ?? 'Group #' . $group->id,
                $group->organization ?? '',
                $group->leader->full_name ?? '',
                $group->leader->email ?? '',
                $group->members_count,
                ucfirst($group->payment_status),
                $paymentDate ?? '—',
                $paymentType ?? '—',
                $group->verifier->full_name ?? '',
                $group->created_at->format('Y-m-d'),
            ], null, "A{$row}");
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'group_registrations_' . now()->format('Y-m-d_His') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export group registrations to PDF
     */
    public function exportPdf(Request $request)
    {
        $groups = GroupRegistration::with(['leader', 'members', 'verifier'])
            ->withCount('members')
            ->orderBy('created_at', 'desc')
            ->get();

        $pdf = Pdf::loadView('admin.group-registrations.export-pdf', compact('groups'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('group_registrations_' . now()->format('Y-m-d') . '.pdf');
    }

    /**
     * Check in a group member
     */
    public function checkInMember(GroupMember $member)
    {
        if (!$member->isPaymentVerified()) {
            return response()->json(['error' => 'Group payment not verified'], 400);
        }

        if ($member->checked_in) {
            return response()->json([
                'success' => false,
                'message' => 'Member already checked in',
                'member' => $member,
            ]);
        }

        $member->checkIn();

        return response()->json([
            'success' => true,
            'message' => 'Member checked in successfully',
            'member' => $member->fresh(),
        ]);
    }

    /**
     * Lookup group member by QR token
     */
    public function lookupByQr(Request $request)
    {
        $token = $request->input('token');
        
        $member = GroupMember::where('qr_token', $token)->with('groupRegistration.leader')->first();

        if (!$member) {
            return response()->json(['error' => 'Member not found'], 404);
        }

        return response()->json([
            'success' => true,
            'member' => $member,
            'group' => $member->groupRegistration,
            'payment_verified' => $member->isPaymentVerified(),
        ]);
    }
}
