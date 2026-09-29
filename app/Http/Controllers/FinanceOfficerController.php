<?php

namespace App\Http\Controllers;

use App\Models\GroupRegistration;
use App\Models\PaymentTransaction;
use App\Models\SponsorPayment;
use App\Models\User;
use App\Payments\PaymentException;
use App\Payments\PaymentOptions;
use App\Payments\PaymentService;
use App\Services\EmailNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FinanceOfficerController extends Controller
{
    public function __construct(
        protected EmailNotificationService $emailService,
        protected PaymentService $payments,
    ) {}

    /**
     * Display the Finance Officer dashboard.
     */
    public function dashboard()
    {
        $supportsSponsorPayments = Schema::hasTable('sponsor_payments');
        $sponsorStats = [
            'pending_count' => $supportsSponsorPayments ? SponsorPayment::where('payment_status', 'submitted')->count() : 0,
            'verified_count' => $supportsSponsorPayments ? SponsorPayment::whereIn('payment_status', ['verified', 'paid'])->count() : 0,
            'rejected_count' => $supportsSponsorPayments ? SponsorPayment::where('payment_status', 'rejected')->count() : 0,
        ];

        // Payment status counts
        $stats = [
            'pending_count' => User::where('payment_status', 'submitted')->count() + GroupRegistration::where('payment_status', 'submitted')->count(),
            'verified_count' => User::where('payment_status', 'verified')->count(),
            'waived_count' => User::where('payment_status', 'waived')->count(),
            'rejected_count' => User::where('payment_status', 'rejected')->count() + GroupRegistration::where('payment_status', 'rejected')->count(),
            'not_started_count' => User::whereNotNull('registration_category')->where(function($q) {
                $q->where('payment_status', 'pending')->orWhereNull('payment_status');
            })->count(),
            'all_pending_count' => User::where('payment_status', 'submitted')->count()
                + GroupRegistration::where('payment_status', 'submitted')->count()
                + $sponsorStats['pending_count'],
        ];

        // Group counts for dashboard
        $groupStats = [
            'pending_count' => GroupRegistration::where('payment_status', 'submitted')->count(),
            'all_count' => GroupRegistration::count(),
        ];

        // Revenue Calculations
        $revenue = [
            'realized' => ['TZS' => 0, 'USD' => 0],
            'pending' => ['TZS' => 0, 'USD' => 0], // Now represents Outstanding/Unpaid
            'waived' => ['TZS' => 0, 'USD' => 0],
            'projected' => ['TZS' => 0, 'USD' => 0],
        ];

        $users = User::whereNotNull('registration_category')->with('groupMember.groupRegistration')->get();
        foreach ($users as $user) {
            // Avoid double counting
            if ($user->groupMember && $user->groupMember->groupRegistration && $user->groupMember->groupRegistration->payment_status !== 'rejected') {
                continue;
            }

            $fee = $user->getRegistrationFee();
            $amt = $fee['amount'];
            $cur = $fee['currency'];

            if ($user->payment_status === 'verified') {
                $revenue['realized'][$cur] += $amt;
            } elseif ($user->payment_status === 'waived') {
                $revenue['waived'][$cur] += $amt;
            } else {
                // All other statuses (pending, submitted, null) count as Outstanding
                if ($user->payment_status !== 'rejected') {
                    $revenue['pending'][$cur] += $amt;
                }
            }

            // Projected includes everything except rejected and waived
            if (!in_array($user->payment_status, ['rejected', 'waived'])) {
                $revenue['projected'][$cur] += $amt;
            }
        }

        // Add Group Revenue
        $groups = GroupRegistration::with('members')->get();
        foreach($groups as $group) {
            foreach($group->members as $member) {
                $amt = $member->fee_amount;
                $cur = $member->fee_currency;

                if ($group->payment_status === 'verified') {
                    $revenue['realized'][$cur] += $amt;
                } elseif ($group->payment_status === 'waived') {
                    $revenue['waived'][$cur] += $amt;
                } else {
                     if ($group->payment_status !== 'rejected') {
                        $revenue['pending'][$cur] += $amt;
                    }
                }

                if (!in_array($group->payment_status, ['rejected', 'waived'])) {
                    $revenue['projected'][$cur] += $amt;
                }
            }
        }

        $sponsorRevenue = ['TZS' => 0, 'USD' => 0];
        if ($supportsSponsorPayments) {
            foreach (SponsorPayment::whereIn('payment_status', ['verified', 'paid'])->get() as $payment) {
                $currency = $payment->paid_currency ?: $payment->currency ?: 'TZS';
                if (!isset($sponsorRevenue[$currency])) {
                    $sponsorRevenue[$currency] = 0;
                }
                $sponsorRevenue[$currency] += (float) ($payment->paid_amount ?: $payment->amount);
            }
        }

        $pendingSponsorPayments = $supportsSponsorPayments
            ? SponsorPayment::query()
                ->where('payment_status', 'submitted')
                ->latest('updated_at')
                ->limit(5)
                ->get()
            : collect();


        // Today's Performance
        $todayVerifications = User::where('payment_status', 'verified')
            ->whereDate('payment_verified_at', today())
            ->count();

        $todayRevenue = ['TZS' => 0, 'USD' => 0];
        $todayUsers = User::where('payment_status', 'verified')->whereDate('payment_verified_at', today())->get();
        foreach($todayUsers as $u) {
            $f = $u->getRegistrationFee();
            $todayRevenue[$f['currency']] += $f['amount'];
        }

        // Recent Audit Requests (High Priority)
        $pendingPayments = User::where('payment_status', 'submitted')
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        // Categorical Breakdown
        $categoryStats = User::whereNotNull('registration_category')
            ->select('registration_category', DB::raw('count(*) as total'))
            ->groupBy('registration_category')
            ->get()
            ->pluck('total', 'registration_category')
            ->toArray();

        return view('finance.dashboard', compact(
            'stats',
            'revenue',
            'todayRevenue',
            'pendingPayments',
            'categoryStats',
            'todayVerifications',
            'groupStats',
            'sponsorStats',
            'pendingSponsorPayments',
            'sponsorRevenue'
        ));
    }

    /**
     * List all payments with filters for status.
     */
    public function payments(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');
        $category = $request->query('category', 'all');
        $method = $request->query('method', '');

        $query = User::query();

        if ($status !== 'all') {
            $query->where('payment_status', $status);
        }

        if ($method !== '') {
            $query->where('payment_method', $method);
        }

        if ($category !== 'all') {
            $query->where('registration_category', $category);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('updated_at', 'desc')->paginate(20);

        $stats = [
            'pending_count' => User::where('payment_status', 'submitted')->count(),
            'verified_count' => User::where('payment_status', 'verified')->count(),
            'waived_count' => User::where('payment_status', 'waived')->count(),
            'rejected_count' => User::where('payment_status', 'rejected')->count(),
            'not_started_count' => User::where('payment_status', 'pending')->orWhereNull('payment_status')->count(),
        ];

        return view('finance.payments', compact('users', 'stats', 'status', 'search', 'category', 'method'));
    }

    /**
     * View payment details for a user.
     */
    public function show(User $user)
    {
        $user->load('abstractSubmissions');
        $transactions = $user->paymentTransactions()->with(['reviewer', 'submitter'])->get();

        return view('finance.payment-details', compact('user', 'transactions'));
    }

    /**
     * Verify a user's payment.
     */
    public function verify(Request $request, User $user)
    {
        $request->validate(['notes' => 'nullable|string|max:2000']);

        $transaction = $user->transactionAwaitingReview();
        if (! $transaction) {
            return redirect()->back()->with('error', 'There is no submitted payment to verify for this registrant.');
        }

        $this->payments->verify($transaction, Auth::user(), $request->notes);

        return redirect()->back()->with('success', "Payment for {$user->full_name} has been verified and the participant has been notified.");
    }

    /**
     * Reject a user's payment.
     */
    public function reject(Request $request, User $user)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $transaction = $user->transactionAwaitingReview();
        if (! $transaction) {
            return redirect()->back()->with('error', 'There is no submitted payment to reject for this registrant.');
        }

        $this->payments->reject($transaction, Auth::user(), $request->notes);

        return redirect()->back()->with('success', "The payment submitted by {$user->full_name} has been rejected. They have been notified and can submit corrected details.");
    }

    /**
     * Waive a user's registration fee.
     */
    public function waive(Request $request, User $user)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $user->update([
            'payment_status' => 'waived',
            'payment_verified_at' => now(),
            'payment_verified_by' => Auth::id(),
            'payment_notes' => $request->notes,
        ]);

        // Generate QR code token for conference check-in
        $user->generateQrToken();

        $this->emailService->sendFeeWaived($user, $request->notes);

        return redirect()->back()->with('success', "Registration fee for {$user->full_name} has been waived and the user has been notified.");
    }

    /**
     * Remove a mistaken waiver and return the user to the normal payment flow.
     */
    public function returnWaiverToPayment(User $user)
    {
        if ($user->payment_status !== 'waived') {
            return redirect()->back()->with('error', "Only waived payments can be returned to payment.");
        }

        $hasPaymentProgress = (bool) $user->transactionAwaitingReview();
        $user->update([
            'payment_status' => $hasPaymentProgress ? 'submitted' : 'pending',
            'payment_verified_at' => null,
            'payment_verified_by' => null,
            'payment_notes' => 'Waiver removed by finance officer. Participant returned to payment flow.',
        ]);

        Log::info("Waiver removed for User {$user->id} by Finance Officer " . Auth::id());

        return redirect()->back()->with('success', "{$user->full_name} has been returned to the payment flow.");
    }

    /**
     * List all group payments.
     */
    public function groups(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = \App\Models\GroupRegistration::with('leader');

        if ($status !== 'all') {
            $query->where('payment_status', $status);
        }

        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('group_name', 'like', "%{$search}%")
                  ->orWhere('organization', 'like', "%{$search}%")
                  ->orWhereHas('leader', function($sq) use ($search) {
                      $sq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $groups = $query->orderBy('updated_at', 'desc')->paginate(20);

        $stats = [
            'pending_count' => \App\Models\GroupRegistration::where('payment_status', 'submitted')->count(),
            'verified_count' => \App\Models\GroupRegistration::where('payment_status', 'verified')->count(),
            'rejected_count' => \App\Models\GroupRegistration::where('payment_status', 'rejected')->count(),
        ];

        return view('finance.groups', compact('groups', 'stats', 'status', 'search'));
    }

    /**
     * View group registration details.
     */
    public function showGroup(\App\Models\GroupRegistration $groupRegistration)
    {
        $groupRegistration->load(['members', 'leader']);
        $transactions = $groupRegistration->paymentTransactions()->with(['reviewer', 'submitter'])->get();

        return view('finance.group-details', compact('groupRegistration', 'transactions'));
    }

    /**
     * Verify a group registration payment.
     */
    public function verifyGroup(Request $request, \App\Models\GroupRegistration $groupRegistration)
    {
        $request->validate(['notes' => 'nullable|string|max:2000']);

        $transaction = $groupRegistration->transactionAwaitingReview();
        if (! $transaction) {
            return redirect()->back()->with('error', 'There is no submitted payment to verify for this group.');
        }

        $this->payments->verify($transaction, Auth::user(), $request->notes);

        return redirect()->back()->with('success', "Payment for group '{$groupRegistration->group_name}' has been verified. Members are now available at the registration desk.");
    }

    /**
     * Reject a group registration payment.
     */
    public function rejectGroup(Request $request, \App\Models\GroupRegistration $groupRegistration)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $transaction = $groupRegistration->transactionAwaitingReview();
        if (! $transaction) {
            return redirect()->back()->with('error', 'There is no submitted payment to reject for this group.');
        }

        $this->payments->reject($transaction, Auth::user(), $request->notes);

        return redirect()->back()->with('success', "The payment submitted for group '{$groupRegistration->group_name}' has been rejected and the leader notified.");
    }

    /**
     * Waive a group registration fee.
     */
    public function waiveGroup(Request $request, \App\Models\GroupRegistration $groupRegistration)
    {
        $request->validate([
            'notes' => 'required|string',
        ]);

        $groupRegistration->update([
            'payment_status' => 'waived',
            'payment_verified_at' => now(),
            'verified_by' => Auth::id(),
            'admin_notes' => $request->notes,
        ]);

        // Generate QR tokens for all group members
        foreach ($groupRegistration->members as $member) {
            $member->generateQrToken();
        }

        // Notify group leader
        $this->emailService->sendGroupFeeWaived($groupRegistration, $request->notes);

        return redirect()->back()->with('success', "Registration fee for group '{$groupRegistration->group_name}' has been waived and the group leader has been notified.");
    }

    /**
     * Remove a mistaken group waiver and return the group to payment.
     */
    public function returnGroupWaiverToPayment(\App\Models\GroupRegistration $groupRegistration)
    {
        if ($groupRegistration->payment_status !== 'waived') {
            return redirect()->back()->with('error', "Only waived group payments can be returned to payment.");
        }

        $hasPaymentProgress = (bool) $groupRegistration->transactionAwaitingReview();
        $groupRegistration->update([
            'payment_status' => $hasPaymentProgress ? 'submitted' : 'pending',
            'payment_verified_at' => null,
            'verified_by' => null,
            'admin_notes' => 'Waiver removed by finance officer. Group returned to payment flow.',
        ]);

        Log::info("Group waiver removed for Group {$groupRegistration->id} by Finance Officer " . Auth::id());

        return redirect()->back()->with('success', "Group '{$groupRegistration->group_name}' has been returned to the payment flow.");
    }

    /**
     * List finance-created sponsor payment requests.
     */
    public function sponsors(Request $request)
    {
        $status = $request->query('status', 'all');
        $search = $request->query('search', '');

        $query = SponsorPayment::with(['createdBy', 'verifiedBy']);

        if ($status !== 'all') {
            $query->where('payment_status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('sponsor_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%");
            });
        }

        $payments = $query->latest('updated_at')->paginate(20)->withQueryString();

        $stats = [
            'pending_count' => SponsorPayment::where('payment_status', 'pending')->count(),
            'submitted_count' => SponsorPayment::where('payment_status', 'submitted')->count(),
            'verified_count' => SponsorPayment::whereIn('payment_status', ['verified', 'paid'])->count(),
            'rejected_count' => SponsorPayment::where('payment_status', 'rejected')->count(),
        ];

        return view('finance.sponsors', compact('payments', 'stats', 'status', 'search'));
    }

    public function createSponsorPayment()
    {
        return view('finance.sponsor-create');
    }

    public function storeSponsorPayment(Request $request)
    {
        $request->merge([
            'amount' => str_replace(',', '', (string) $request->input('amount')),
        ]);

        $validated = $request->validate([
            'sponsor_name' => 'required|string|max:255',
            'package_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'contact_email' => 'required|email|max:255',
            'contact_phone' => 'required|string|max:50',
            'description' => 'required|string|max:1000',
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|in:TZS,USD',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['payment_status'] = 'pending';

        $payment = SponsorPayment::create($validated);
        $payment->ensurePaymentReference();

        return redirect()
            ->route('finance.sponsors')
            ->with('success', "Sponsor invoice created with payment reference {$payment->payment_reference}. Share it with the sponsor so it appears on their transfer.");
    }

    /**
     * Record a sponsor payment seen on the bank statement / operator report
     * and mark the invoice paid.
     */
    public function verifySponsor(Request $request, SponsorPayment $sponsorPayment, PaymentOptions $options)
    {
        $currency = $sponsorPayment->paymentCurrency();

        $validated = $request->validate([
            'method' => ['required', \Illuminate\Validation\Rule::in(array_keys($options->methodsFor($currency)))],
            'provider' => ['nullable', 'required_if:method,'.PaymentTransaction::METHOD_MOBILE_MONEY],
            'external_reference' => 'required|string|max:100',
            'amount' => 'nullable|numeric|min:0',
            'paid_on' => 'nullable|date|before_or_equal:today',
            'proof' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'notes' => 'nullable|string|max:2000',
        ]);

        try {
            $this->payments->recordVerified($sponsorPayment, $validated, $request->file('proof'), Auth::user(), $validated['notes'] ?? null);
        } catch (PaymentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->back()->with('success', 'Sponsor payment has been recorded and verified.');
    }

    /**
     * Download a proof-of-payment file attached to any transaction.
     */
    public function transactionProof(PaymentTransaction $transaction)
    {
        return $this->payments->downloadProof($transaction);
    }

    public function rejectSponsor(Request $request, SponsorPayment $sponsorPayment)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $sponsorPayment->update([
            'payment_status' => 'rejected',
            'admin_notes' => $validated['notes'],
        ]);

        return redirect()->back()->with('success', 'Sponsor payment has been rejected.');
    }

    /**
     * Export payment report
     */
    public function exportReport(Request $request)
    {
        $status = $request->query('status', 'all');
        $type = $request->query('type', 'individuals');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="finance_report_' . $type . '_' . date('Y-m-d') . '.csv"',
        ];

        if ($type === 'groups') {
            $query = \App\Models\GroupRegistration::with(['leader', 'members']);

            if ($status !== 'all') {
                $query->where('payment_status', $status);
            }

            $groups = $query->orderBy('updated_at', 'desc')->get();

            $callback = function() use ($groups) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Group Name', 'Leader', 'Organization', 'Members', 'Currency', 'Total Amount', 'Status', 'Verified At', 'Notes']);

                foreach ($groups as $group) {
                    fputcsv($file, [
                        $group->group_name ?: 'Group #' . $group->id,
                        $group->leader?->full_name ?? $group->leader?->email ?? 'Unknown',
                        $group->organization ?? '-',
                        $group->members->count(),
                        $group->currency ?? '-',
                        $group->total_amount ?? 0,
                        $group->payment_status ?? 'pending',
                        $group->payment_verified_at ? $group->payment_verified_at->format('Y-m-d H:i') : '-',
                        $group->admin_notes ?? '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        if ($type === 'sponsors') {
            if (! Schema::hasTable('sponsor_payments')) {
                return redirect()->back()->with('error', 'Sponsor payments are not available yet.');
            }

            $status = $request->query('status', 'all');
            $search = $request->query('search', '');

            $query = SponsorPayment::with(['createdBy', 'verifiedBy']);

            if ($status !== 'all') {
                $query->where('payment_status', $status);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('sponsor_name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('contact_email', 'like', "%{$search}%")
                        ->orWhere('payment_reference', 'like', "%{$search}%");
                });
            }

            $payments = $query->latest('updated_at')->get();

            $callback = function () use ($payments) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Sponsor', 'Description', 'Contact Email', 'Contact Phone', 'Currency', 'Amount', 'Paid Amount', 'Payment Reference', 'Payment Status', 'Created By', 'Verified By', 'Verified At', 'Notes']);

                foreach ($payments as $payment) {
                    fputcsv($file, [
                        $payment->sponsor_name ?? '-',
                        $payment->description ?? '-',
                        $payment->contact_email ?? '-',
                        $payment->contact_phone ?? '-',
                        $payment->paid_currency ?: $payment->currency ?: 'TZS',
                        $payment->amount ?? 0,
                        $payment->paid_amount ?? '-',
                        $payment->payment_reference ?? '-',
                        $payment->payment_status ?? 'pending',
                        $payment->createdBy?->full_name ?? $payment->createdBy?->email ?? '-',
                        $payment->verifiedBy?->full_name ?? $payment->verifiedBy?->email ?? '-',
                        $payment->payment_verified_at ? $payment->payment_verified_at->format('Y-m-d H:i') : '-',
                        $payment->payment_notes ?? $payment->admin_notes ?? '',
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        if ($type === 'summary') {
            $stats = [
                'verified_count' => User::where('payment_status', 'verified')->count(),
                'submitted_count' => User::where('payment_status', 'submitted')->count() + \App\Models\GroupRegistration::where('payment_status', 'submitted')->count(),
                'pending_count' => User::whereNotNull('registration_category')->where(function($q) {
                    $q->where('payment_status', 'pending')->orWhereNull('payment_status');
                })->count(),
                'waived_count' => User::where('payment_status', 'waived')->count(),
                'rejected_count' => User::where('payment_status', 'rejected')->count() + \App\Models\GroupRegistration::where('payment_status', 'rejected')->count(),
            ];

            $revenue = [
                'realized_tzs' => 0,
                'realized_usd' => 0,
                'pending_tzs' => 0,
                'pending_usd' => 0,
                'waived_tzs' => 0,
                'waived_usd' => 0,
                'projected_tzs' => 0,
                'projected_usd' => 0,
            ];

            $users = User::whereNotNull('registration_category')->with('groupMember.groupRegistration')->get();
            foreach ($users as $user) {
                if ($user->groupMember && $user->groupMember->groupRegistration && $user->groupMember->groupRegistration->payment_status !== 'rejected') {
                    continue;
                }

                $fee = $user->getRegistrationFee();
                $amount = $fee['amount'] ?? 0;
                $currency = $fee['currency'] ?? 'TZS';
                $prefix = strtolower($currency);

                if ($user->payment_status === 'verified') {
                    $revenue["realized_{$prefix}"] += $amount;
                } elseif ($user->payment_status === 'waived') {
                    $revenue["waived_{$prefix}"] += $amount;
                } elseif ($user->payment_status !== 'rejected') {
                    $revenue["pending_{$prefix}"] += $amount;
                }

                if (!in_array($user->payment_status, ['rejected', 'waived'])) {
                    $revenue["projected_{$prefix}"] += $amount;
                }
            }

            foreach (\App\Models\GroupRegistration::with('members')->get() as $group) {
                foreach ($group->members as $member) {
                    $prefix = strtolower($member->fee_currency ?? 'TZS');
                    $amount = (float) ($member->fee_amount ?? 0);

                    if ($group->payment_status === 'verified') {
                        $revenue["realized_{$prefix}"] += $amount;
                    } elseif ($group->payment_status === 'waived') {
                        $revenue["waived_{$prefix}"] += $amount;
                    } elseif ($group->payment_status !== 'rejected') {
                        $revenue["pending_{$prefix}"] += $amount;
                    }

                    if (!in_array($group->payment_status, ['rejected', 'waived'])) {
                        $revenue["projected_{$prefix}"] += $amount;
                    }
                }
            }

            $categoryBreakdown = User::whereNotNull('registration_category')
                ->select('registration_category', DB::raw('count(*) as total'))
                ->groupBy('registration_category')
                ->orderBy('registration_category')
                ->get();

            $callback = function() use ($stats, $revenue, $categoryBreakdown) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Metric', 'Value']);
                fputcsv($file, ['Verified Count', $stats['verified_count']]);
                fputcsv($file, ['Submitted Count', $stats['submitted_count']]);
                fputcsv($file, ['Pending Count', $stats['pending_count']]);
                fputcsv($file, ['Waived Count', $stats['waived_count']]);
                fputcsv($file, ['Rejected Count', $stats['rejected_count']]);
                fputcsv($file, ['Realized TZS', $revenue['realized_tzs']]);
                fputcsv($file, ['Realized USD', $revenue['realized_usd']]);
                fputcsv($file, ['Pending TZS', $revenue['pending_tzs']]);
                fputcsv($file, ['Pending USD', $revenue['pending_usd']]);
                fputcsv($file, ['Waived TZS', $revenue['waived_tzs']]);
                fputcsv($file, ['Waived USD', $revenue['waived_usd']]);
                fputcsv($file, ['Projected TZS', $revenue['projected_tzs']]);
                fputcsv($file, ['Projected USD', $revenue['projected_usd']]);
                fputcsv($file, []);
                fputcsv($file, ['Registration Category', 'Count']);

                foreach ($categoryBreakdown as $row) {
                    fputcsv($file, [$row->registration_category, $row->total]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

        $query = User::whereNotNull('registration_category');

        if ($status !== 'all') {
            $query->where('payment_status', $status);
        }

        $users = $query->orderBy('updated_at', 'desc')->get();

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Name', 'Email', 'Category', 'Currency', 'Amount', 'Payment Reference', 'Method', 'Status', 'Verified At', 'Notes']);

            foreach ($users as $user) {
                $fee = $user->getRegistrationFee();
                fputcsv($file, [
                    $user->full_name,
                    $user->email,
                    $user->registration_category ?? 'Not set',
                    $fee['currency'],
                    $fee['amount'],
                    $user->payment_reference ?? '-',
                    $user->payment_method ?? '-',
                    $user->payment_status ?? 'pending',
                    $user->payment_verified_at ? $user->payment_verified_at->format('Y-m-d H:i') : '-',
                    $user->payment_notes ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
