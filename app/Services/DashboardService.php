<?php

namespace App\Services;

use App\Enums\AbstractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\RegistrationStatus;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\Registration;
use App\Models\ReviewAssignment;
use App\Models\User;
use App\Support\Palette;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** The numbers behind each role's dashboard. Every figure is computed from live data. */
class DashboardService
{
    public function participant(User $user, ?Edition $edition): array
    {
        $registration = $user->registrationFor($edition)?->load('category', 'payments');
        $latest = $registration?->payments->first();
        $abstracts = $edition
            ? $user->abstracts()->where('edition_id', $edition->id)->with('topic', 'reviews', 'sessions')->latest()->get()
            : collect();
        $confirmed = $registration?->isConfirmed() ?? false;
        $summitOver = $edition?->end_date?->isPast() ?? false;

        $steps = [
            ['Account created', $user->created_at->format('j M'), true],
            ['Registered', $registration ? $registration->category->name : 'Choose a category', (bool) $registration],
            ['Payment verified', $confirmed ? $registration->confirmed_at->format('j M') : ($latest?->status === PaymentStatus::Submitted ? 'In progress' : 'Pay the fee'), $confirmed],
            ['Badge ready', $confirmed ? 'Print or show on phone' : 'After payment', $confirmed],
            ['Certificate', 'After the Summit', $summitOver && $confirmed],
        ];
        $current = collect($steps)->search(fn ($s) => ! $s[2]);
        $current = $current === false ? count($steps) - 1 : $current;

        [$headline, $detail] = match (true) {
            ! $registration => ['Register for the '.$edition?->name.' '.$edition?->year, 'Choose your category and pay by bank transfer or mobile money. Your badge unlocks as soon as payment is verified.'],
            $registration->status === RegistrationStatus::PaymentSubmitted => ['Your payment is being verified', 'Submitted by '.$latest->channel().' on '.$latest->created_at->format('j F').'. Finance usually confirms within 2 working days, then your badge unlocks.'],
            $registration->status === RegistrationStatus::PendingPayment && $latest?->status === PaymentStatus::Rejected => ['Please resubmit your payment', 'We could not verify your last payment: '.$latest->rejection_reason],
            $registration->status === RegistrationStatus::PendingPayment => ['Pay your registration fee', $registration->formattedAmount().' with reference '.$registration->reference.'. Pay by bank transfer or mobile money, then upload the proof.'],
            default => ['You are all set for the Summit', 'Your registration is confirmed. Download your badge, plan your sessions and bring your badge to the registration desk.'],
        };

        $sessions = $edition ? $edition->sessions()->where('kind', '!=', 'break')->with('abstracts')->get() : collect();
        $mine = $abstracts->flatMap->sessions->pluck('id');

        return [
            'registration' => $registration,
            'latestPayment' => $latest,
            'abstracts' => $abstracts,
            'steps' => $steps,
            'current' => $current,
            'headline' => $headline,
            'detail' => $detail,
            'countdowns' => $this->countdowns($edition),
            'agenda' => $sessions->groupBy(fn ($s) => $s->starts_at->toDateString()),
            'mySessions' => $mine,
            'cpdSessions' => $sessions->count(),
        ];
    }

    /** "21 days until abstract submissions close", with how much of the run-up has passed. */
    private function countdowns(?Edition $edition): array
    {
        $items = [];
        $runUp = fn (CarbonImmutable $date) => max(4, min(100, 100 - today()->diffInDays($date) / 365 * 100));

        if ($edition?->abstract_deadline && $edition->abstract_deadline->isFuture()) {
            $date = CarbonImmutable::parse($edition->abstract_deadline);
            $items[] = ['days' => (int) today()->diffInDays($date), 'label' => 'until abstract submissions close', 'date' => $date->format('j M'), 'progress' => $runUp($date), 'color' => '#bd520a'];
        }
        if ($edition?->start_date && $edition->start_date->isFuture()) {
            $date = CarbonImmutable::parse($edition->start_date);
            $items[] = ['days' => (int) today()->diffInDays($date), 'label' => 'until the Summit opens'.($edition->venue ? ' at '.Str::of($edition->venue)->explode(' ')->filter(fn ($w) => ctype_upper($w[0] ?? ''))->map(fn ($w) => $w[0])->implode('') : ''), 'date' => $date->format('j M'), 'progress' => $runUp($date), 'color' => '#024f6d'];
        }

        return $items;
    }

    public function scientific(?Edition $edition): array
    {
        $abstracts = AbstractSubmission::where('edition_id', $edition?->id)->where('status', '!=', AbstractStatus::Draft)
            ->with('topic', 'reviews.reviewer', 'submitter')->get();
        $reviews = $abstracts->flatMap->reviews;
        $ready = $abstracts->filter(fn ($a) => $a->status === AbstractStatus::UnderReview && $a->reviews->isNotEmpty() && $a->reviews->every->isComplete());
        $decided = $abstracts->whereIn('status', [AbstractStatus::Accepted, AbstractStatus::Rejected]);

        $daily = collect(range(29, 0))->map(function ($ago) use ($abstracts) {
            $day = today()->subDays($ago);

            return ['label' => $day->format('j M'), 'value' => $abstracts->filter(fn ($a) => $a->submitted_at?->isSameDay($day))->count()];
        })->values()->all();

        $agreement = function (AbstractSubmission $a) {
            $recs = $a->reviews->filter->isComplete()->map(fn ($r) => $r->recommendation->value)->unique();
            if ($recs->count() <= 1) {
                return ['High', 'bg-emerald-500'];
            }

            return $recs->contains('reject') ? ['Low', 'bg-red-500'] : ['Medium', 'bg-amber-500'];
        };

        $topics = ($edition?->topics ?? collect())->map(fn ($topic) => [
            'name' => $topic->name,
            'cells' => [
                'Submitted' => $abstracts->where('topic_id', $topic->id)->where('status', AbstractStatus::Submitted)->count(),
                'In review' => $abstracts->where('topic_id', $topic->id)->where('status', AbstractStatus::UnderReview)->count(),
                'Accepted' => $abstracts->where('topic_id', $topic->id)->where('status', AbstractStatus::Accepted)->count(),
                'Not accepted' => $abstracts->where('topic_id', $topic->id)->where('status', AbstractStatus::Rejected)->count(),
            ],
        ]);

        $activity = collect()
            ->merge($abstracts->filter->submitted_at->map(fn ($a) => ['at' => $a->submitted_at, 'text' => $a->blindId().' submitted under '.$a->topic->name, 'dot' => 'bg-brand-600', 'url' => route('scientific.abstracts.show', $a)]))
            ->merge($reviews->filter->isComplete()->map(fn ($r) => ['at' => $r->completed_at, 'text' => $r->reviewer->name.' reviewed '.$r->abstract->blindId(), 'dot' => 'bg-olive-700', 'url' => route('scientific.abstracts.show', $r->abstract_id)]))
            ->merge($decided->filter->decided_at->map(fn ($a) => ['at' => $a->decided_at, 'text' => $a->blindId().($a->status === AbstractStatus::Accepted ? ' accepted as '.$a->code : ' not accepted'), 'dot' => $a->status === AbstractStatus::Accepted ? 'bg-emerald-500' : 'bg-red-500', 'url' => route('scientific.abstracts.show', $a)]))
            ->sortByDesc('at')->take(7)->values();

        $workload = User::role('reviewer')->withCount([
            'reviewAssignments as assigned',
            'reviewAssignments as done' => fn ($q) => $q->whereNotNull('completed_at'),
            'reviewAssignments as overdue' => fn ($q) => $q->whereNull('completed_at')->whereDate('due_on', '<', today()),
        ])->get()->sortByDesc('assigned')->values();

        return [
            'submitted' => $abstracts->count(),
            'thisWeek' => $abstracts->filter(fn ($a) => $a->submitted_at?->gte(now()->subWeek()))->count(),
            'assigned' => $abstracts->filter(fn ($a) => $a->reviews->isNotEmpty())->count(),
            'reviewsDone' => $reviews->filter->isComplete()->count(),
            'reviewsTotal' => $reviews->count(),
            'decided' => $decided->count(),
            'ready' => $ready->sortByDesc(fn ($a) => $a->averageScore())->values(),
            'daily' => $daily,
            'pipeline' => [
                ['Submitted', $abstracts->count()],
                ['Assigned to reviewers', $abstracts->filter(fn ($a) => $a->reviews->isNotEmpty())->count()],
                ['Fully reviewed', $abstracts->filter(fn ($a) => $a->reviews->isNotEmpty() && $a->reviews->every->isComplete())->count()],
                ['Decided', $decided->count()],
                ['Accepted', $abstracts->where('status', AbstractStatus::Accepted)->count()],
            ],
            'agreement' => $agreement,
            'topics' => $topics,
            'activity' => $activity,
            'workload' => $workload,
        ];
    }

    public function executive(?Edition $edition): array
    {
        $registrations = Registration::where('edition_id', $edition?->id)->with('user', 'category')->get();
        $total = $registrations->count();
        $target = $edition?->registration_target;
        $payments = Payment::whereIn('registration_id', $registrations->pluck('id'))->get();

        // Cumulative registrations by day, from the first one to today.
        $first = $registrations->min('created_at');
        $points = [];
        if ($first) {
            $days = (int) CarbonImmutable::parse($first)->startOfDay()->diffInDays(today()) + 1;
            $step = max(1, intdiv($days, 40));
            for ($d = 0; $d < $days; $d += $step) {
                $day = CarbonImmutable::parse($first)->startOfDay()->addDays($d);
                $points[] = ['label' => $day->format('j M'), 'value' => $registrations->filter(fn ($r) => $r->created_at->lte($day->endOfDay()))->count()];
            }
            $points[] = ['label' => 'Today', 'value' => $total];
        }
        $pace = null;
        if ($target && $first && $edition?->start_date) {
            $span = max(1, CarbonImmutable::parse($first)->diffInDays($edition->start_date));
            $pace = (int) round($target * min(1, CarbonImmutable::parse($first)->diffInDays(today()) / $span));
        }

        $byCurrency = fn (string $currency, $status = null) => $payments->where('currency', $currency)
            ->when($status, fn ($c) => $c->where('status', $status))->sum('amount');
        $expected = fn (string $currency) => $registrations->where('currency', $currency)->sum('amount');
        $money = collect(['TZS', 'USD'])->mapWithKeys(fn ($c) => [$c => [
            'verified' => $byCurrency($c, PaymentStatus::Verified),
            'submitted' => $byCurrency($c, PaymentStatus::Submitted),
            'expected' => $expected($c),
        ]]);

        $countries = $registrations->groupBy(fn ($r) => $r->user->country)->map->count()->sortDesc();
        $international = $total ? round($registrations->filter(fn ($r) => $r->user->country !== 'TZ')->count() / $total * 100) : 0;
        $pending = $payments->where('status', PaymentStatus::Submitted);
        $oldest = $pending->min('created_at');
        $abstracts = AbstractSubmission::where('edition_id', $edition?->id)->where('status', '!=', AbstractStatus::Draft)->get();

        $watch = [];
        if ($target && $pace) {
            $watch[] = $total >= $pace
                ? ['Registrations on pace', number_format($total).' registered against a pace target of '.number_format($pace).'.', 'good']
                : ['Registrations behind pace', number_format($total).' registered against a pace target of '.number_format($pace).'.', 'watch'];
        }
        if ($pending->isNotEmpty()) {
            $days = (int) CarbonImmutable::parse($oldest)->diffInDays(now());
            $watch[] = ['Verification backlog', $pending->count().' payments waiting; the oldest was submitted '.$days.' '.Str::plural('day', $days).' ago.', $days > 3 ? 'act' : 'watch'];
        }
        $watch[] = ['International share', 'Participants from outside Tanzania are '.$international.'% of registrations.', 'watch'];
        $watch[] = $abstracts->count()
            ? ['Abstracts on track', $abstracts->count().' submitted, '.$abstracts->where('status', AbstractStatus::Accepted)->count().' accepted so far.', 'good']
            : ['No abstracts yet', 'Abstract submission has not produced any submissions.', 'watch'];

        $institutions = $registrations->groupBy(fn ($r) => $r->user->institution ?: '—')->map->count()->sortDesc()->take(6);
        $abstractsByInstitution = AbstractSubmission::where('edition_id', $edition?->id)->with('submitter')->get()
            ->groupBy(fn ($a) => $a->submitter->institution)->map->count();

        return [
            'total' => $total,
            'target' => $target,
            'confirmed' => $registrations->where('status', RegistrationStatus::Confirmed)->count(),
            'points' => $points,
            'pace' => $pace,
            'mix' => ($edition?->categories ?? collect())->values()->map(fn ($c, $i) => [
                'label' => $c->name, 'value' => $registrations->where('registration_category_id', $c->id)->count(), 'color' => Palette::categorical($i),
            ])->all(),
            'money' => $money,
            'countries' => $countries,
            'international' => $international,
            'abstracts' => $abstracts->count(),
            'abstractsThisWeek' => $abstracts->filter(fn ($a) => $a->submitted_at?->gte(now()->subWeek()))->count(),
            'watch' => $watch,
            'institutions' => $institutions,
            'abstractsByInstitution' => $abstractsByInstitution,
            'recent' => $registrations->sortByDesc('created_at')->take(6),
        ];
    }

    public function reviewer(User $user, ?Edition $edition): array
    {
        $assignments = $user->reviewAssignments()->with('abstract.topic', 'abstract.edition')->get();
        $done = $assignments->filter->isComplete();
        $pending = $assignments->reject->isComplete()->sortBy('due_on')->values();
        $deadline = $edition?->review_deadline;

        $bands = ['<10' => [0, 9], '10–12' => [10, 12], '13–15' => [13, 15], '16–18' => [16, 18], '19–20' => [19, 20]];
        $panel = ReviewAssignment::whereNotNull('completed_at')->get();
        $share = fn (Collection $set, array $band) => $set->count()
            ? round($set->filter(fn ($r) => $r->totalScore() >= $band[0] && $r->totalScore() <= $band[1])->count() / $set->count() * 100) : 0;

        return [
            'assignments' => $assignments,
            'pending' => $pending,
            'done' => $done->sortByDesc('completed_at')->values(),
            'percent' => $assignments->count() ? $done->count() / $assignments->count() * 100 : 0,
            'deadline' => $deadline,
            'daysLeft' => $deadline && $deadline->isFuture() ? (int) today()->diffInDays($deadline) : null,
            'overdue' => $pending->filter(fn ($a) => $a->due_on?->isPast())->count(),
            'average' => $done->count() ? round($done->avg(fn ($r) => $r->totalScore()), 1) : null,
            'panelAverage' => $panel->count() ? round($panel->avg(fn ($r) => $r->totalScore()), 1) : null,
            'bands' => collect($bands)->map(fn ($band, $label) => ['label' => $label, 'you' => $share($done, $band), 'panel' => $share($panel, $band)])->values(),
        ];
    }

    public function finance(): array
    {
        $payments = Payment::with('registration.user', 'registration.category')->get();
        $verified = $payments->where('status', PaymentStatus::Verified);
        $submitted = $payments->where('status', PaymentStatus::Submitted)->sortBy('created_at')->values();
        $registrations = Registration::all();

        $channels = $payments->groupBy(fn ($p) => $p->channel())->map->count()->sortDesc();

        // Verifications per day for the last five weeks, Monday first.
        $start = today()->startOfWeek()->subWeeks(4);
        $pulse = collect(range(0, 34))->map(function ($d) use ($start, $verified) {
            $day = $start->copy()->addDays($d);

            return ['date' => $day, 'count' => $verified->filter(fn ($p) => $p->reviewed_at?->isSameDay($day))->count(), 'future' => $day->isFuture()];
        });

        return [
            'verifiedCount' => $verified->count(),
            'verifiedTotals' => $verified->groupBy('currency')->map->sum('amount'),
            'submitted' => $submitted,
            'olderThan3' => $submitted->filter(fn ($p) => $p->created_at->lt(now()->subDays(3)))->count(),
            'notPaid' => $registrations->where('status', RegistrationStatus::PendingPayment)->count(),
            'rejected' => $payments->where('status', PaymentStatus::Rejected)->count(),
            'channels' => $channels,
            'mobileCount' => $submitted->where('method', PaymentMethod::MobileMoney)->count(),
            'bankCount' => $submitted->where('method', PaymentMethod::BankTransfer)->count(),
            'collection' => collect(['TZS', 'USD'])->mapWithKeys(fn ($c) => [$c => [
                'collected' => $verified->where('currency', $c)->sum('amount'),
                'expected' => $registrations->where('currency', $c)->sum('amount'),
            ]]),
            'pulse' => $pulse,
            'pulseMax' => max(1, $pulse->max('count')),
        ];
    }
}
