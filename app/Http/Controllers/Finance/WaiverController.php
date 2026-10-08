<?php

namespace App\Http\Controllers\Finance;

use App\Enums\RegistrationStatus;
use App\Enums\WaiverReason;
use App\Http\Controllers\Controller;
use App\Models\FeeWaiver;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Finance waives registration fees, in whole or in part, and can withdraw a waiver. */
class WaiverController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $edition = $summit->edition();
        $search = trim((string) $request->query('q'));
        $show = $request->query('show') === 'withdrawn' ? 'withdrawn' : 'active';

        // Registrations that can be waived: still awaiting payment, without a waiver.
        $candidates = $search === '' ? collect() : Registration::query()
            ->where('edition_id', $edition?->id)
            ->where('status', RegistrationStatus::PendingPayment)
            ->where('waived_amount', 0)
            ->where(fn ($q) => $q->where('reference', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($q) => $q
                    ->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->with('user', 'category')
            ->orderBy('reference')
            ->limit(10)
            ->get();

        $waivers = FeeWaiver::query()
            ->whereHas('registration', fn ($q) => $q->where('edition_id', $edition?->id))
            ->with('registration.user', 'registration.category', 'grantedBy', 'revokedBy')
            ->when($show === 'active', fn ($q) => $q->active(), fn ($q) => $q->whereNotNull('revoked_at'))
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $active = FeeWaiver::active()->whereHas('registration', fn ($q) => $q->where('edition_id', $edition?->id))->with('registration')->get();

        return view('finance.waivers.index', [
            'search' => $search,
            'show' => $show,
            'candidates' => $candidates,
            'waivers' => $waivers,
            'stats' => [
                'full' => $active->filter->isFull()->count(),
                'partial' => $active->reject->isFull()->count(),
                'totals' => $active->groupBy('currency')->map->sum('amount'),
            ],
        ]);
    }

    public function create(Registration $registration): View|RedirectResponse
    {
        if ($registration->status !== RegistrationStatus::PendingPayment || $registration->isWaived()) {
            return redirect()->route('finance.waivers.index')
                ->withErrors(['waiver' => $registration->user->name.'’s fee cannot be waived now: only a registration awaiting payment, without a waiver, can be.']);
        }

        return view('finance.waivers.create', [
            'registration' => $registration->load('user', 'category', 'payments'),
            'reasons' => WaiverReason::cases(),
        ]);
    }

    public function store(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'gt:0', 'max:'.(float) $registration->amount],
            'reason' => ['required', Rule::enum(WaiverReason::class)],
            'note' => ['nullable', 'required_if:reason,other', 'string', 'max:500'],
        ], [
            'amount.max' => 'The waiver cannot be more than the fee of '.$registration->formattedAmount().'.',
            'amount.gt' => 'Enter the amount to waive.',
            'note.required_if' => 'Explain the waiver in the note.',
        ]);

        try {
            $waiver = $service->waive($registration, $request->user(), (float) $data['amount'], WaiverReason::from($data['reason']), $data['note'] ?? null);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['amount' => $e->getMessage()]);
        }

        $registration->refresh();

        return redirect()->route('finance.waivers.index')->with('status', $registration->isFullyWaived()
            ? $registration->user->name.'’s fee is waived and their registration is confirmed.'
            : $waiver->formattedAmount().' waived for '.$registration->user->name.'. They now pay '.$registration->formattedDue().'.');
    }

    public function withdraw(Request $request, FeeWaiver $waiver, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate(['revoke_reason' => ['required', 'string', 'min:5', 'max:255']], [
            'revoke_reason.required' => 'Say why the waiver is withdrawn. The participant is told.',
        ]);

        try {
            $service->withdrawWaiver($waiver, $request->user(), $data['revoke_reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['waiver' => $e->getMessage()]);
        }

        return back()->with('status', 'Waiver withdrawn. '.$waiver->registration->user->name.' has been asked to pay '.$waiver->registration->fresh()->formattedDue().'.');
    }

    /** Every waiver, for the summit accounts. */
    public function export(Summit $summit): StreamedResponse
    {
        $waivers = FeeWaiver::query()
            ->whereHas('registration', fn ($q) => $q->where('edition_id', $summit->edition()?->id))
            ->with('registration.user', 'registration.category', 'grantedBy', 'revokedBy')
            ->oldest('id')
            ->get();

        // A cell starting with = + - or @ would run as a formula in a spreadsheet.
        $cell = fn ($value) => is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;

        return response()->streamDownload(function () use ($waivers, $cell) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Registration', 'Participant', 'Email', 'Category', 'Currency', 'Fee', 'Waived', 'Still due', 'Reason', 'Note', 'Granted by', 'Granted at', 'Status', 'Withdrawn by', 'Withdrawn at', 'Why withdrawn']);
            foreach ($waivers as $w) {
                $r = $w->registration;
                fputcsv($out, array_map($cell, [
                    $r->reference, $r->user->name, $r->user->email, $r->category->name, $w->currency, $r->amount, $w->amount,
                    max(0, (float) $r->amount - (float) $w->amount), $w->reason->label(), $w->note, $w->grantedBy?->name, $w->created_at->toDateTimeString(),
                    $w->isActive() ? 'Active' : 'Withdrawn', $w->revokedBy?->name, $w->revoked_at?->toDateTimeString(), $w->revoke_reason,
                ]));
            }
            fclose($out);
        }, 'fee-waivers-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }
}
