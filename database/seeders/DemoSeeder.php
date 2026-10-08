<?php

namespace Database\Seeders;

use App\Enums\AbstractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PresentationType;
use App\Enums\Recommendation;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Enums\WaiverReason;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\RegistrationCategory;
use App\Models\ReviewAssignment;
use App\Models\Topic;
use App\Models\User;
use App\Support\Rubric;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * SAMPLE DATA FOR THE DEMONSTRATION. Never runs in production.
 *
 * Builds a believable 2027 summit: settings, staff accounts for every role,
 * ~60 participants at every stage of registration and payment, ~36 abstracts
 * through review and decision, and a three-day programme. Fixed random seeds
 * make it the same every time.
 *
 *     php artisan migrate:fresh --seed     (DatabaseSeeder calls this locally)
 *     php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'rehab2027';

    private Edition $edition;

    /** @var Collection<string, Topic> keyed by code */
    private Collection $topics;

    /** @var Collection<int, User> */
    private Collection $reviewers;

    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('The DemoSeeder fills the portal with sample data and must not run in production.');
        }

        // Rendering ~150 sample slips with dompdf needs more than PHP's 128M CLI default.
        ini_set('memory_limit', '512M');
        mt_srand(2027);
        $this->call(RoleSeeder::class);

        $this->edition();
        $staff = $this->staff();
        $participants = $this->participants();
        $this->registrations($participants, $staff['finance']);
        $this->waivers($staff['finance']);
        $this->abstracts($participants, $staff['scientific']);
        $this->programme();
        $this->deskAndNotifications();
        $this->call(AwardsSeeder::class);
        $this->call(GallerySeeder::class);
    }

    /** $time if it is already past; otherwise a random moment between $after and now. */
    private function past(CarbonImmutable $time, CarbonImmutable $after): CarbonImmutable
    {
        if ($time->isPast()) {
            return $time;
        }

        $window = max(60, (int) $after->diffInSeconds(now()));

        return $after->addSeconds(mt_rand(30, $window - 30));
    }

    /** Some badges already printed, and the bell filled for the demo accounts. */
    private function deskAndNotifications(): void
    {
        Registration::where('edition_id', $this->edition->id)->where('status', RegistrationStatus::Confirmed)->get()
            ->filter(fn () => mt_rand(1, 100) <= 45)
            ->each(fn (Registration $r) => $r->update(['badge_printed_at' => $this->past(CarbonImmutable::parse($r->confirmed_at)->addDays(mt_rand(1, 6)), CarbonImmutable::parse($r->confirmed_at))]));

        $notify = function (User $user, string $title, string $body, string $url, string $icon, string $tone, string $ago, bool $read) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'demo',
                'data' => compact('title', 'body', 'url', 'icon', 'tone'),
                'read_at' => $read ? now()->modify($ago)->addHour() : null,
                'created_at' => now()->modify($ago),
                'updated_at' => now()->modify($ago),
            ]);
        };

        $amina = User::where('email', 'participant@rehab.test')->first();
        $accepted = $amina->abstracts()->where('status', AbstractStatus::Accepted)->first();
        $revising = $amina->abstracts()->where('status', AbstractStatus::RevisionRequested)->first();
        $amina->notifications()->delete();
        $notify($amina, 'Registration confirmed', 'Your badge and invitation letter are ready to download.', route('registration.badge.show'), 'check-circle', 'success', '-20 days', true);
        if ($revising) {
            $notify($amina, 'Abstract received', '"'.$revising->title.'" is with the scientific committee.', route('abstracts.show', $revising), 'document', 'info', '-12 days', true);
            $notify($amina, 'Revisions requested', '"'.$revising->title.'" · due '.$revising->revision_due_on->format('j M'), route('abstracts.revision.edit', $revising), 'pencil', 'warning', '-3 days', false);
        }
        if ($accepted) {
            $notify($amina, 'Abstract accepted · '.$accepted->code, '"'.$accepted->title.'"', route('abstracts.show', $accepted), 'clipboard', 'success', '-2 days', false);
        }
        $notify($amina, 'Programme published', 'The '.$this->edition->name.' '.$this->edition->year.' programme is online. Plan your sessions.', route('programme'), 'calendar', 'info', '-5 hours', false);

        $grace = User::where('email', 'reviewer@rehab.test')->first();
        $grace->notifications()->delete();
        $grace->reviewAssignments()->whereNull('completed_at')->with('abstract.topic', 'abstract.edition')->get()
            ->each(fn ($a, $i) => $notify($grace, $a->round === 2 ? 'Revised abstract to review' : 'New abstract to review', $a->abstract->blindId().' · '.$a->abstract->topic->name, route('reviews.edit', $a), 'star', 'warning', '-'.($i * 2 + 1).' days', $i > 1));
    }

    private function edition(): void
    {
        Edition::query()->update(['is_current' => false]);

        $this->edition = Edition::updateOrCreate(['year' => 2027], [
            'name' => 'Rehabilitation Summit',
            'short_name' => 'Rehab Summit',
            'ordinal' => '5th',
            'theme' => 'Rehabilitation for All: Building Resilient Health Systems Across the Life Course',
            'start_date' => '2027-09-15',
            'end_date' => '2027-09-17',
            'venue' => 'Julius Nyerere International Convention Centre',
            'city' => 'Dar es Salaam',
            'country' => 'Tanzania',
            'registration_open' => true,
            'abstracts_open' => true,
            'abstract_deadline' => '2027-05-31',
            'review_deadline' => '2027-06-30',
            'session_role_deadline' => '2027-07-15',
            'presentation_deadline' => '2027-09-10',
            'registration_target' => 400,
            'is_current' => true,
        ]);

        $categories = [
            ['Professional (Tanzania)', 'TZS', 250000, false],
            ['Professional (International)', 'USD', 250, false],
            ['Student (Tanzania)', 'TZS', 100000, true],
            ['Student (International)', 'USD', 120, true],
        ];
        foreach ($categories as $i => [$name, $currency, $amount, $student]) {
            $this->edition->categories()->updateOrCreate(['name' => $name], [
                'currency' => $currency, 'amount' => $amount, 'is_student' => $student, 'sort' => $i,
            ]);
        }

        $topics = [
            'HBR' => 'Home-Based Rehabilitation',
            'TAI' => 'Technology and AI in Rehabilitation',
            'FIN' => 'Financing Rehabilitation Services',
            'OCC' => 'Occupational Health and Workplace Rehabilitation',
            'LEA' => 'Rehabilitation Leadership and Advocacy',
            'LIF' => 'Rehabilitation Across the Life Course',
            'NCD' => 'Rehabilitation and Non-Communicable Diseases (NCDs)',
            'WDI' => 'Women, Disability and Inclusive Development',
            'RIN' => 'Research and Innovation',
        ];
        $i = 0;
        foreach ($topics as $code => $name) {
            $this->edition->topics()->updateOrCreate(['code' => $code], ['name' => $name, 'sort' => $i++]);
        }

        $this->edition->load('categories', 'topics');
        $this->topics = $this->edition->topics->keyBy('code');
    }

    /** @return array<string, User> */
    private function staff(): array
    {
        $make = fn (string $email, ?string $title, string $first, string $last, array $roles, string $institution, string $profession) => tap(
            User::updateOrCreate(['email' => $email], [
                'title' => $title, 'first_name' => $first, 'last_name' => $last,
                'phone' => '+2557'.mt_rand(10000000, 99999999), 'country' => 'TZ',
                'institution' => $institution, 'profession' => $profession,
                'password' => self::PASSWORD,
            ]),
            function (User $user) use ($roles) {
                $user->forceFill(['email_verified_at' => now()->subMonths(3)])->save();
                $user->syncRoles($roles);
            },
        );

        $staff = [
            'admin' => $make('admin@rehab.test', null, 'Portal', 'Admin', [Role::Admin->value], 'Rehab Health', 'Summit secretariat'),
            'executive' => $make('executive@rehab.test', 'Dr', 'Mwajuma', 'Hassan', [Role::Executive->value], 'Rehab Health', 'Executive director'),
            'scientific' => $make('scientific@rehab.test', 'Dr', 'Peter', 'Kimaro', [Role::ScientificAdmin->value], 'Muhimbili University of Health and Allied Sciences', 'Rehabilitation physician'),
            'finance' => $make('finance@rehab.test', null, 'Rehema', 'Said', [Role::FinanceOfficer->value], 'Rehab Health', 'Finance officer'),
            'desk' => $make('desk@rehab.test', null, 'Baraka', 'Lyimo', [Role::RegistrationOfficer->value], 'Rehab Health', 'Events coordinator'),
        ];

        $this->reviewers = collect([
            $make('reviewer@rehab.test', 'Prof', 'Grace', 'Mwakyusa', [Role::Reviewer->value], 'Kilimanjaro Christian Medical University College', 'Professor of physiotherapy'),
            $make('h.otieno@rehab.test', 'Dr', 'Hellen', 'Otieno', [Role::Reviewer->value], 'University of Nairobi', 'Occupational therapist'),
            $make('s.nkurunziza@rehab.test', 'Dr', 'Samuel', 'Nkurunziza', [Role::Reviewer->value], 'University of Rwanda', 'Physiotherapy lecturer'),
            $make('f.mbwambo@rehab.test', 'Dr', 'Fatuma', 'Mbwambo', [Role::Reviewer->value], 'CCBRT', 'Rehabilitation physician'),
            $make('d.kasozi@rehab.test', 'Prof', 'David', 'Kasozi', [Role::Reviewer->value], 'Makerere University', 'Public health specialist'),
            $make('e.mollel@rehab.test', 'Dr', 'Esther', 'Mollel', [Role::Reviewer->value, Role::Participant->value], 'Bugando Medical Centre', 'Speech and language therapist'),
        ]);

        return $staff;
    }

    /** @return Collection<int, User> participants, demo accounts first */
    private function participants(): Collection
    {
        $professions = ['Physiotherapist', 'Physiotherapist', 'Physiotherapist', 'Occupational therapist', 'Occupational therapist', 'Speech and language therapist', 'Prosthetist-orthotist', 'Rehabilitation physician', 'Nurse', 'Public health specialist', 'Researcher', 'Health policy adviser'];

        $make = function (string $email, ?string $title, string $first, string $last, string $institution, string $country, string $profession, ?string $verifiedAgo = '-2 months') {
            [$prefix, $digits] = self::PEOPLE[$country]['phone'];
            $user = User::updateOrCreate(['email' => $email], [
                'title' => $title, 'first_name' => $first, 'last_name' => $last,
                'phone' => $prefix.str_pad((string) mt_rand(0, 10 ** $digits - 1), $digits, '0', STR_PAD_LEFT),
                'country' => $country, 'institution' => $institution, 'profession' => $profession,
                'password' => self::PASSWORD,
            ]);
            $user->forceFill(['email_verified_at' => now()->modify($verifiedAgo), 'created_at' => now()->modify($verifiedAgo)])->save();
            $user->syncRoles(array_unique(array_merge($user->getRoleNames()->all(), [Role::Participant->value])));

            return $user;
        };

        $people = collect([
            $make('participant@rehab.test', 'Dr', 'Amina', 'Mussa', 'Muhimbili National Hospital', 'TZ', 'Rehabilitation physician', '-4 months'),
            $make('newcomer@rehab.test', null, 'Joseph', 'Mrema', 'Dodoma Regional Referral Hospital', 'TZ', 'Physiotherapist', '-2 days'),
        ]);

        // Names, institutions and phone numbers come from the person's own country,
        // and nobody shares a full name with anyone else.
        $abroad = array_values(array_diff(array_keys(self::PEOPLE), ['TZ']));
        $taken = $people->map(fn (User $u) => $u->first_name.' '.$u->last_name)
            ->merge(collect($this->reviewers)->map(fn (User $u) => $u->first_name.' '.$u->last_name))->flip();
        $pick = fn (array $list) => $list[mt_rand(0, count($list) - 1)];

        for ($i = 0; $i < 198; $i++) {
            // About seven in ten participants are from Tanzania.
            $country = mt_rand(1, 100) <= 72 ? 'TZ' : $pick($abroad);
            $pool = self::PEOPLE[$country];
            $woman = $i % 2 === 0;
            do {
                $firstName = $pick($woman ? $pool['women'] : $pool['men']);
                $lastName = $pick($pool['last']);
            } while ($taken->has($firstName.' '.$lastName));
            $taken[$firstName.' '.$lastName] = true;

            $titles = $woman ? ['Dr', 'Ms', 'Mrs', null] : ['Dr', 'Mr', null];
            $email = Str::slug(Str::ascii($firstName.' '.$lastName), '.').'@example.org';
            $people->push($make($email, $pick($titles), $firstName, $lastName, $pick($pool['institutions']), $country,
                $pick($professions), '-'.mt_rand(3, 150).' days'));
        }

        return $people;
    }

    /** Registrations and payments at every stage. */
    /** Fee waivers from finance: some whole fees, some halves, and one withdrawn. */
    private function waivers(User $finance): void
    {
        // [part of the fee waived, reason, note, withdrawn because]
        $plan = [
            [1.0, WaiverReason::Speaker, 'Keynote speaker: assistive technology for all, day 2.', null],
            [1.0, WaiverReason::Committee, 'Member of the scientific committee.', null],
            [1.0, WaiverReason::Organiser, 'Rehab Health secretariat, working at the registration desk.', null],
            [0.5, WaiverReason::Hardship, 'Unpaid volunteer at a community rehabilitation group.', null],
            [0.5, WaiverReason::Sponsored, 'Half the fee sponsored by Humanity & Inclusion.', null],
            [0.5, WaiverReason::Sponsored, 'Half the fee sponsored by the Tanzania Physiotherapy Association.', null],
            [0.5, WaiverReason::Hardship, 'Requested by email; final-year student.', 'Her employer has agreed to pay the full fee.'],
        ];

        $candidates = Registration::where('edition_id', $this->edition->id)
            ->where('status', RegistrationStatus::PendingPayment)
            ->whereDoesntHave('payments')
            ->whereHas('user', fn ($q) => $q->where('email', 'like', '%@example.org'))
            ->orderBy('id')->get()
            ->shuffle(mt_rand())->take(count($plan))->values();

        foreach ($candidates as $i => $registration) {
            [$part, $reason, $note, $withdrawn] = $plan[$i];
            $amount = round((float) $registration->amount * $part, 2);
            $grantedAt = $this->past(CarbonImmutable::parse($registration->created_at)->addDays(mt_rand(1, 6))->setTime(mt_rand(9, 16), mt_rand(0, 59)), CarbonImmutable::parse($registration->created_at));

            $waiver = $registration->waivers()->create([
                'currency' => $registration->currency, 'amount' => $amount, 'reason' => $reason, 'note' => $note, 'granted_by' => $finance->id,
            ]);
            $waiver->forceFill(['created_at' => $grantedAt, 'updated_at' => $grantedAt])->save();

            if ($withdrawn) {
                $waiver->forceFill(['revoked_at' => $this->past($grantedAt->addDays(3), $grantedAt), 'revoked_by' => $finance->id, 'revoke_reason' => $withdrawn])->save();

                continue;
            }

            $registration->update(['waived_amount' => $amount] + ($part >= 1.0
                ? ['status' => RegistrationStatus::Confirmed, 'confirmed_at' => $grantedAt]
                : []));
        }
    }

    private function registrations(Collection $participants, User $finance): void
    {
        $categories = $this->edition->categories;
        $byKey = fn (bool $local, bool $student) => $categories->first(fn (RegistrationCategory $c) => $c->is_student === $student
            && ($c->currency === 'TZS') === $local);

        foreach ($participants as $index => $user) {
            if ($user->email === 'newcomer@rehab.test') {
                continue; // registers live during the demo
            }

            $student = $user->email !== 'participant@rehab.test' && mt_rand(1, 100) <= 18;
            $category = $byKey($user->country === 'TZ', $student);
            $roll = $user->email === 'participant@rehab.test' ? 1 : mt_rand(1, 100);
            $status = match (true) {
                $roll <= 58 => 'confirmed',
                $roll <= 74 => 'submitted',
                $roll <= 80 => 'rejected',
                default => 'pending',
            };
            // Registrations cluster in recent weeks, as they do before a summit.
            $registeredAt = CarbonImmutable::now()->subDays(1 + (int) round(150 * (mt_rand() / mt_getrandmax()) ** 1.7))->setTime(mt_rand(7, 20), mt_rand(0, 59));
            if ($user->created_at->gt($registeredAt)) {
                $user->forceFill(['created_at' => $registeredAt->subDay()])->save();
            }

            $registration = Registration::updateOrCreate(['edition_id' => $this->edition->id, 'user_id' => $user->id], [
                'registration_category_id' => $category->id,
                'reference' => 'pending-'.Str::random(12),
                'currency' => $category->currency,
                'amount' => $category->amount,
                'status' => RegistrationStatus::PendingPayment,
                'needs_invitation_letter' => $user->country !== 'TZ' || $user->email === 'participant@rehab.test',
                'passport_number' => $user->country !== 'TZ' || $user->email === 'participant@rehab.test' ? 'A'.mt_rand(1000000, 9999999) : null,
                'dietary_needs' => mt_rand(1, 6) === 1 ? 'Vegetarian' : null,
                'qr_token' => Str::random(32),
            ]);
            // Same scheme as RegistrationService: derived from the id, so it never collides.
            $registration->forceFill([
                'created_at' => $registeredAt,
                'reference' => sprintf('%s%s-%06d', config('payments.reference_prefix'), $this->edition->shortYear(), $registration->id),
            ])->save();
            $registration->payments()->delete();

            if ($status === 'pending') {
                continue;
            }

            // Payments still waiting for finance were sent in the last week; older ones are settled.
            $paidOn = match ($status) {
                'submitted' => CarbonImmutable::now()->subDays(mt_rand(0, 6))->max($registeredAt),
                'rejected' => CarbonImmutable::now()->subDays(mt_rand(3, 15))->max($registeredAt),
                default => $registeredAt->addDays(mt_rand(1, 5)),
            };

            if ($status === 'rejected') {
                $this->payment($registration, $paidOn, PaymentStatus::Rejected, $finance,
                    'The transaction reference does not appear on our bank statement. Please check it and upload a clearer slip.');

                continue;
            }

            $payment = $this->payment($registration, $paidOn,
                $status === 'confirmed' ? PaymentStatus::Verified : PaymentStatus::Submitted, $finance);

            $registration->update($status === 'confirmed'
                ? ['status' => RegistrationStatus::Confirmed, 'confirmed_at' => $payment->reviewed_at]
                : ['status' => RegistrationStatus::PaymentSubmitted]);
        }
    }

    private function payment(Registration $registration, CarbonImmutable $paidOn, PaymentStatus $status, User $finance, ?string $reason = null): Payment
    {
        $providers = array_keys(config('payments.mobile_money.providers')) ?: ['mpesa'];
        $mobile = $registration->currency === 'TZS' && mt_rand(1, 100) <= 55;
        $paidOn = $paidOn->min(CarbonImmutable::now()->subHours(2));

        $payment = $registration->payments()->create([
            'method' => $mobile ? PaymentMethod::MobileMoney : PaymentMethod::BankTransfer,
            'provider' => $mobile ? $providers[mt_rand(0, count($providers) - 1)] : null,
            'currency' => $registration->currency,
            'amount' => $registration->amount,
            'transaction_reference' => $mobile ? Str::upper(Str::random(10)) : 'FT'.mt_rand(270000000, 279999999),
            'payer_name' => $registration->user->first_name.' '.$registration->user->last_name,
            'payer_phone' => $registration->user->phone,
            'paid_on' => $paidOn->toDateString(),
            'status' => $status,
            'reviewed_by' => $status === PaymentStatus::Submitted ? null : $finance->id,
            'reviewed_at' => $status === PaymentStatus::Submitted ? null : $this->past($paidOn->addHours(mt_rand(3, 40)), $paidOn),
            'rejection_reason' => $reason,
        ]);
        $payment->forceFill(['created_at' => $paidOn->setTime(mt_rand(8, 18), mt_rand(0, 59))->min(CarbonImmutable::now()->subHour())])->save();

        // A sample slip, so the finance screens have something to look at.
        $path = 'payment-proofs/demo-'.$registration->reference.'-'.$payment->id.'.pdf';
        Storage::disk(config('payments.proof_disk'))->put($path, Pdf::loadView('pdf.sample-receipt', [
            'payment' => $payment->setRelation('registration', $registration),
            'bank' => $mobile ? config("payments.mobile_money.providers.{$payment->provider}.label", 'Mobile money') : 'CRDB Bank PLC',
        ])->setPaper('a5', 'landscape')->output());
        $payment->update(['proof_path' => $path]);
        gc_collect_cycles();

        return $payment;
    }

    /**
     * Abstracts at every stage. Each has two reviewers: two acceptances accept
     * it automatically, other combinations wait for or have a committee
     * decision, and some go through a round of revisions. Every date is worked
     * out backwards from today, so the story of each abstract is in order.
     */
    private function abstracts(Collection $participants, User $scientific): void
    {
        AbstractSubmission::where('edition_id', $this->edition->id)->delete();

        $authors = $participants->reject(fn (User $u) => $u->email === 'newcomer@rehab.test')->values();
        $demoReviewer = $this->reviewers->first();

        // The demo participant's three: accepted (0), waiting for her revision (20) and a draft (35).
        $others = collect(array_merge(
            array_fill(0, 9, 'auto'), array_fill(0, 2, 'committee_accepted'), array_fill(0, 4, 'rejected'),
            array_fill(0, 4, 'awaiting'), array_fill(0, 5, 'under_review'), array_fill(0, 2, 'revision_requested'),
            array_fill(0, 2, 'revised'), array_fill(0, 2, 'revised_accepted'), array_fill(0, 2, 'submitted'), ['draft'],
        ))->shuffle(2027)->values()->all();
        $plan = [0 => 'auto', 20 => 'revision_requested', 35 => 'draft'];
        foreach (array_keys(self::ABSTRACTS) as $i) {
            $plan[$i] ??= array_shift($others);
        }

        // Round-1 recommendations for each outcome, cycled for variety. A = accept, V = revise, R = reject.
        $pairs = [
            'auto' => [['A', 'A']],
            'committee_accepted' => [['A', 'V']],
            'rejected' => [['R', 'R'], ['V', 'R']],
            'awaiting' => [['A', 'R'], ['A', 'V'], ['V', 'R'], ['V', 'V']],
            'under_review' => [['A', null], ['V', null], ['R', null], [null, null]],
            'revision_requested' => [['A', 'V'], ['V', 'V']],
            'revised' => [['A', 'V'], ['V', 'R']],
            'revised_accepted' => [['A', 'V'], ['V', 'V']],
        ];
        $seen = [];

        $minutes = fn (int $min, int $max) => mt_rand($min * 1440, $max * 1440);
        $ago = fn (int $min, int $max) => CarbonImmutable::now()->subMinutes($minutes($min, $max));
        $before = fn (CarbonImmutable $time, int $min, int $max) => $time->subMinutes($minutes($min, $max));

        foreach (self::ABSTRACTS as $i => [$code, $title, $focus, $setting, $design, $n, $unit, $background, $finding, $secondary, $conclusion]) {
            $state = $plan[$i];
            $owner = in_array($i, [0, 20, 35], true) ? $authors->first() : $authors[1 + ($i % ($authors->count() - 1))];
            $turn = $seen[$state] = ($seen[$state] ?? -1) + 1;
            [$first, $second] = isset($pairs[$state]) ? $pairs[$state][$turn % count($pairs[$state])] : [null, null];

            // The timeline, from the last event back to the submission.
            $decidedAt = $revisedAt = $requestedAt = null;
            $round1 = [null, null];
            switch ($state) {
                case 'auto':
                    $round1[1] = $decidedAt = $ago(2, 40);
                    $round1[0] = $before($decidedAt, 0, 5);
                    break;
                case 'committee_accepted':
                case 'rejected':
                    $decidedAt = $ago(2, 30);
                    $round1 = [$before($decidedAt, 2, 8), $before($decidedAt, 1, 4)];
                    break;
                case 'awaiting':
                    $round1[1] = $ago(0, 6);
                    $round1[0] = $before($round1[1], 0, 5);
                    break;
                case 'under_review':
                    $round1[0] = $first ? $ago(0, 5) : null;
                    break;
                case 'revision_requested':
                    $requestedAt = $i === 20 ? CarbonImmutable::now()->subDays(3)->setTime(10, 15) : $ago(2, 8);
                    $round1 = [$before($requestedAt, 2, 6), $before($requestedAt, 1, 4)];
                    break;
                case 'revised':
                    $revisedAt = $ago(1, 4);
                    $requestedAt = $before($revisedAt, 5, 9);
                    $round1 = [$before($requestedAt, 2, 6), $before($requestedAt, 1, 4)];
                    break;
                case 'revised_accepted':
                    $decidedAt = $ago(2, 12);
                    $revisedAt = $before($decidedAt, 2, 5);
                    $requestedAt = $before($revisedAt, 5, 9);
                    $round1 = [$before($requestedAt, 2, 6), $before($requestedAt, 1, 4)];
                    break;
            }
            $earliest = collect($round1)->filter()->min() ?? CarbonImmutable::now();
            $submittedAt = match ($state) {
                'draft' => null,
                'submitted' => $ago(0, 6),
                default => $before($earliest, 3, 10),
            };

            $methods = self::methods($design, $n, $unit, $setting);
            $results = ucfirst($finding).'. '.$secondary;
            $abstract = AbstractSubmission::create([
                'edition_id' => $this->edition->id,
                'user_id' => $owner->id,
                'topic_id' => $this->topics[$code]->id,
                'preferred_type' => PresentationType::preferences()[mt_rand(0, count(PresentationType::preferences()) - 1)],
                'title' => $title,
                'background' => $background,
                'methods' => $methods,
                'results' => $results,
                'conclusions' => $conclusion,
                'keywords' => Str::lower($focus).', '.Str::lower(Str::before($setting, ',')).', rehabilitation',
                'status' => $state === 'draft' ? AbstractStatus::Draft : AbstractStatus::Submitted,
                'submitted_at' => $submittedAt,
            ]);
            $abstract->forceFill(['created_at' => ($submittedAt ?? $ago(1, 10))->subDays(2)])->save();

            // Authors: the submitter first, then one to three co-authors from the participant list.
            $coAuthors = $authors->reject(fn (User $u) => $u->is($owner))->shuffle(mt_rand())->take(mt_rand(1, 3));
            foreach ($coAuthors->prepend($owner)->values() as $position => $person) {
                $abstract->authors()->create([
                    'name' => $person->name, 'email' => $person->email, 'affiliation' => $person->institution,
                    'is_presenter' => $position === 0, 'position' => $position + 1,
                ]);
            }

            if (in_array($state, ['draft', 'submitted'], true)) {
                continue;
            }

            // Two reviewers who are not authors. The demo reviewer asked for the first revision, so reviews it again.
            $authorEmails = $abstract->authors()->pluck('email');
            $eligible = $this->reviewers->reject(fn (User $r) => $authorEmails->contains($r->email))->shuffle(mt_rand())->values();
            if ($state === 'revised' && $turn === 0 && $eligible->contains($demoReviewer)) {
                $eligible = $eligible->reject(fn (User $r) => $r->is($demoReviewer))->prepend($demoReviewer)->values();
                [$first, $second] = ['V', 'A'];
            }
            $panel = $eligible->take(2)->values();

            $firstDone = collect($round1)->filter()->min();
            $assignedAt = $submittedAt->addDay()->min($firstDone ? $firstDone->subHours(6) : CarbonImmutable::now()->subHour());
            $reviews = [];
            foreach ([$first, $second] as $r => $letter) {
                $reviews[$r] = $this->seedReview($abstract, $panel[$r], $scientific, 1, $letter, $round1[$r], $assignedAt, $this->edition->review_deadline);
            }

            if ($state === 'under_review' || $state === 'awaiting') {
                $abstract->update(['status' => AbstractStatus::UnderReview]);

                continue;
            }

            if ($state === 'rejected') {
                $abstract->update(['status' => AbstractStatus::Rejected, 'decided_at' => $decidedAt,
                    'decision_note' => 'The committee encourages you to strengthen the methods section and submit again next year.']);

                continue;
            }

            if ($state === 'auto' || $state === 'committee_accepted') {
                $this->seedAcceptance($abstract, $code, $decidedAt, $state === 'auto');

                continue;
            }

            // The committee asked for a revision: keep the text the reviewers saw.
            $abstract->update([
                'status' => AbstractStatus::RevisionRequested,
                'revision_requested_at' => $requestedAt,
                'revision_due_on' => $requestedAt->addDays(config('review.revision_days'))->toDateString(),
                'revision_note' => in_array('A', [$first, $second], true) ? 'One reviewer would accept the abstract as it stands. Please answer the other reviewer\'s comments.' : null,
                'original_version' => $abstract->only(AbstractSubmission::REVISABLE),
            ]);
            if ($state === 'revision_requested') {
                continue;
            }

            $abstract->update([
                'methods' => $methods.' Participants were recruited consecutively, and those lost to follow-up were compared with those who completed the study.',
                'results' => $results.' Main estimates are reported with 95% confidence intervals.',
                'revision_response' => 'We thank the reviewers. We now describe recruitment and loss to follow-up in the methods, report the main estimates with 95% confidence intervals, and have tightened the conclusions to match the results.',
                'revised_at' => $revisedAt,
                'status' => AbstractStatus::Revised,
            ]);

            // The revised version goes only to the reviewers who did not accept.
            $again = collect($reviews)->reject(fn (ReviewAssignment $review) => $review->recommendation->isAcceptance())->values();
            foreach ($again as $k => $review) {
                // Accepted after revision: every second review is in. Still under review: the demo reviewer's is waiting.
                $done = $state === 'revised_accepted' || ($again->count() > 1 && ! $review->reviewer->is($demoReviewer) && $k === 1);
                $completedAt = match (true) {
                    $state === 'revised_accepted' => $decidedAt->subMinutes($k * 180),
                    $done => $revisedAt->addHours(mt_rand(20, 30))->min(CarbonImmutable::now()->subHour()),
                    default => null,
                };
                $this->seedReview($abstract, $review->reviewer, $scientific, 2, $done ? 'A' : null, $completedAt, $revisedAt, $revisedAt->addDays(config('review.second_round_days')));
            }

            if ($state === 'revised_accepted') {
                $this->seedAcceptance($abstract, $code, $decidedAt, true);
            }
        }

        // The demo reviewer always has fresh work: first of the two reviewers on new submissions.
        AbstractSubmission::where('edition_id', $this->edition->id)->where('status', AbstractStatus::Submitted)
            ->whereDoesntHave('authors', fn ($q) => $q->where('email', $demoReviewer->email))
            ->limit(2)->get()
            ->each(function (AbstractSubmission $abstract) use ($demoReviewer, $scientific) {
                $abstract->reviews()->create(['reviewer_id' => $demoReviewer->id, 'round' => 1, 'assigned_by' => $scientific->id, 'due_on' => $this->edition->review_deadline]);
                $abstract->update(['status' => AbstractStatus::UnderReview]);
            });
    }

    /**
     * One review. $letter is A (accept), V (revise) or R (reject); null leaves
     * it unfinished. The scores sit in the rubric band that fits the recommendation.
     */
    private function seedReview(AbstractSubmission $abstract, User $reviewer, User $scientific, int $round, ?string $letter, ?CarbonImmutable $completedAt, CarbonImmutable $assignedAt, $dueOn): ReviewAssignment
    {
        $fields = ['reviewer_id' => $reviewer->id, 'round' => $round, 'assigned_by' => $scientific->id, 'due_on' => $dueOn];

        if ($letter && $completedAt) {
            [$lo, $hi] = ['A' => [74, 90], 'V' => [54, 66], 'R' => [28, 46]][$letter];
            $f = mt_rand($lo, $hi) / 100;
            $comments = $round === 2 ? self::COMMENTS['second'] : self::COMMENTS[['A' => 'good', 'V' => 'revise', 'R' => 'weak'][$letter]];

            $fields += [
                // Rubric points: originality /20, technical /40, significance /30, clarity /10.
                'score_originality' => (int) round(20 * $f),
                'score_technical' => max(0, min(40, (int) round(40 * $f) + mt_rand(-2, 2))),
                'score_significance' => (int) round(30 * $f),
                'score_clarity' => (int) round(10 * $f),
                'technical_checks' => collect(array_keys(Rubric::checks()))->shuffle(mt_rand())->take((int) round(count(Rubric::checks()) * $f))->values()->all(),
                'recommendation' => ['A' => Recommendation::AcceptOral, 'V' => Recommendation::Revise, 'R' => Recommendation::Reject][$letter],
                'comments_for_author' => $comments[mt_rand(0, count($comments) - 1)],
                'comments_for_committee' => $round === 1 && mt_rand(1, 4) === 1 ? 'Strong local relevance; consider for the plenary-adjacent parallel session.' : null,
                'completed_at' => $completedAt,
            ];
        }

        $review = $abstract->reviews()->create($fields);
        $review->forceFill(['created_at' => $assignedAt])->save();

        return $review->setRelation('reviewer', $reviewer);
    }

    private function seedAcceptance(AbstractSubmission $abstract, string $topic, CarbonImmutable $decidedAt, bool $automatically): void
    {
        $prefix = PresentationType::Oral->codePrefix().'-'.$topic.'-';
        $number = AbstractSubmission::where('code', 'like', $prefix.'%')->count() + 1;

        $abstract->update([
            'status' => AbstractStatus::Accepted,
            'decision_type' => PresentationType::Oral,
            'code' => $prefix.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
            'decided_at' => $decidedAt,
            'accepted_automatically' => $automatically,
        ]);
    }

    /** Three days: plenaries, parallel oral sessions by topic, panels, breaks, and posters when they are on. */
    private function programme(): void
    {
        $this->edition->sessions()->delete();

        $accepted = AbstractSubmission::where('edition_id', $this->edition->id)->where('status', AbstractStatus::Accepted)->with('topic')->get();
        $orals = $accepted->where('decision_type', PresentationType::Oral)->groupBy(fn ($a) => $a->topic->code);
        $posters = $accepted->where('decision_type', PresentationType::Poster)->values();
        $chairs = ['Prof Grace Mwakyusa', 'Dr Peter Kimaro', 'Dr Fatuma Mbwambo', 'Prof David Kasozi', 'Dr Hellen Otieno', 'Dr Samuel Nkurunziza'];

        $add = function (string $day, string $from, string $to, string $kind, string $title, ?string $hall = null, ?string $topic = null, ?string $chair = null, ?string $description = null) {
            return ProgrammeSession::create([
                'edition_id' => $this->edition->id, 'title' => $title, 'kind' => $kind,
                'topic_id' => $topic ? $this->topics[$topic]->id : null, 'hall' => $hall,
                'starts_at' => "{$day} {$from}", 'ends_at' => "{$day} {$to}", 'chair' => $chair, 'description' => $description,
            ]);
        };
        $attach = fn (ProgrammeSession $session, Collection $abstracts) => $abstracts->values()->each(
            fn ($abstract, $i) => $session->abstracts()->attach($abstract->id, ['position' => $i + 1]),
        );

        $parallel = $orals->keys()->values();
        $slots = [
            ['2027-09-15', '14:00', '15:30', 'Hall A'], ['2027-09-15', '14:00', '15:30', 'Hall B'],
            ['2027-09-16', '11:00', '12:30', 'Hall A'], ['2027-09-16', '11:00', '12:30', 'Hall B'],
            ['2027-09-17', '09:00', '10:30', 'Hall A'], ['2027-09-17', '09:00', '10:30', 'Hall B'],
        ];
        // Without posters, the afternoon poster slots hold more oral sessions instead.
        if ($posters->isEmpty()) {
            array_push($slots,
                ['2027-09-15', '16:00', '17:30', 'Hall A'], ['2027-09-15', '16:00', '17:30', 'Hall B'],
                ['2027-09-16', '16:00', '17:30', 'Hall A'], ['2027-09-16', '16:00', '17:30', 'Hall B'],
            );
        }

        // Day 1
        $add('2027-09-15', '08:00', '09:00', 'break', 'Registration and welcome coffee');
        $add('2027-09-15', '09:00', '10:30', 'plenary', 'Official opening and keynote address', 'Main Hall', null, 'Prof Grace Mwakyusa',
            'Welcome from Rehab Health and the Ministry of Health, followed by the keynote on rehabilitation in universal health coverage.');
        $add('2027-09-15', '10:30', '11:00', 'break', 'Tea break');
        $add('2027-09-15', '11:00', '12:30', 'panel', 'Rehabilitation in primary health care: from policy to practice', 'Main Hall', 'LEA', 'Dr Peter Kimaro');
        $add('2027-09-15', '12:30', '14:00', 'break', 'Lunch');

        foreach ($slots as $s => [$day, $from, $to, $hall]) {
            if (! isset($parallel[$s])) {
                break;
            }
            $code = $parallel[$s];
            $attach($add($day, $from, $to, 'parallel', 'Oral session: '.$this->topics[$code]->name, $hall, $code, $chairs[$s % count($chairs)]), $orals[$code]);
        }

        if ($posters->isNotEmpty()) {
            $attach($add('2027-09-15', '16:00', '17:30', 'posters', 'Poster session I', 'Exhibition Hall', null, 'Dr Esther Mollel',
                'Presenters stand at their posters for questions.'), $posters->take(3));
        }

        // Day 2
        $add('2027-09-16', '09:00', '10:30', 'plenary', 'Keynote: Assistive technology for all', 'Main Hall', 'TAI', 'Prof David Kasozi');
        $add('2027-09-16', '10:30', '11:00', 'break', 'Tea break');
        $add('2027-09-16', '12:30', '14:00', 'break', 'Lunch');
        $add('2027-09-16', '14:00', '15:30', 'panel', 'Financing rehabilitation: insurance, budgets and partnerships', 'Main Hall', 'FIN', 'Dr Fatuma Mbwambo');
        if ($posters->isNotEmpty()) {
            $attach($add('2027-09-16', '16:00', '17:30', 'posters', 'Poster session II', 'Exhibition Hall', null, 'Dr Hellen Otieno',
                'Presenters stand at their posters for questions.'), $posters->slice(3));
        }

        // Day 3
        $add('2027-09-17', '10:30', '11:00', 'break', 'Tea break');
        $add('2027-09-17', '11:00', '12:00', 'plenary', 'Rapporteur reports', 'Main Hall', null, 'Dr Samuel Nkurunziza', 'Summaries and recommendations from every session.');
        $add('2027-09-17', '12:00', '13:00', 'plenary', 'Closing ceremony and awards', 'Main Hall', null, 'Prof Grace Mwakyusa');
    }

    /** A methods paragraph that fits the study design. */
    private static function methods(string $design, int $n, string $unit, string $setting): string
    {
        $n = number_format($n);
        $a = preg_match('/^[aeiou]/i', $design) ? 'an' : 'a';

        return match (true) {
            str_contains($design, 'randomised') => "We conducted {$a} {$design} in {$setting}. {$n} {$unit} were randomly allocated to the intervention or to usual care. Outcomes were measured at baseline and at follow-up by assessors blinded to allocation, and analysed by intention to treat.",
            str_contains($design, 'retrospective') => "We reviewed the records of {$n} {$unit} at a referral hospital in {$setting} over five years. Outcomes were compared between those who did and did not complete the programme, adjusting for age, sex and severity.",
            str_contains($design, 'cohort') => "We conducted {$a} {$design} in {$setting}, following {$n} {$unit} with standardised outcome measures at baseline, three, six and twelve months. Changes over time were analysed with mixed-effects models.",
            str_contains($design, 'cost survey') => "We surveyed {$n} {$unit} in {$setting}, recording direct medical, transport and lost-income costs over the previous three months. Costs were compared with reported household income.",
            str_contains($design, 'survey'), $design === 'cross-sectional study' => "We conducted {$a} {$design} of {$n} {$unit} in {$setting}, using a structured interviewer-administered questionnaire in Kiswahili or English. Data were analysed with descriptive statistics and logistic regression.",
            $design === 'qualitative study' => "We held in-depth interviews and focus group discussions with {$n} {$unit} in {$setting}. Interviews were recorded, transcribed, translated from Kiswahili and analysed thematically.",
            str_contains($design, 'mixed-methods') => "We combined a structured survey of {$n} {$unit} in {$setting} with in-depth interviews with a purposive sub-sample. Quantitative data were analysed descriptively and interviews thematically.",
            in_array($design, ['quasi-experimental study', 'before-and-after study', 'implementation study', 'programme evaluation'], true) => "We conducted {$a} {$design} in {$setting} involving {$n} {$unit}. Outcomes were measured before the programme started and again after twelve months, and compared using paired tests.",
            str_contains($design, 'psychometric') => "{$n} {$unit} in {$setting} completed the translated instrument, and a sub-sample repeated it seven days later. We assessed internal consistency, test-retest reliability and construct validity.",
            str_contains($design, 'diagnostic') => "{$n} {$unit} in {$setting} were assessed with the smartphone application and with laboratory motion capture on the same day. Agreement was measured with intraclass correlation coefficients, and therapists completed an acceptability questionnaire.",
            str_contains($design, 'key-informant') => "We analysed national policies, training plans and staffing data for {$setting}, and interviewed {$n} {$unit}. Interviews were analysed thematically against a health workforce framework.",
            str_contains($design, 'policy') => "We conducted {$a} {$design} covering {$n} {$unit} in {$setting}. Policy documents, budgets and benefit packages were analysed against a structured framework.",
            $design === 'case study' => "We documented the process with {$n} {$unit} in {$setting} through document review, meeting observation and interviews with officials.",
            $design === 'scoping review' => "We searched PubMed, CINAHL and African Journals Online for rehabilitation studies from {$setting}. Two reviewers screened titles and full texts independently, and {$n} {$unit} were included and charted.",
            $design === 'economic evaluation' => "We compared the costs and outcomes of community and hospital-based services for {$n} {$unit} in {$setting}, from the provider perspective, over one year.",
            $design === 'facility audit' => "We audited {$n} {$unit} in {$setting} against national accessibility standards, using a structured checklist and a walk-through with a disabled people's organisation.",
            str_contains($design, 'Delphi') => "{$n} {$unit} from {$setting} took part in three online Delphi rounds, rating candidate outcomes on a nine-point scale. Consensus was defined as at least 70% rating an outcome 7 to 9.",
            default => "We conducted {$a} {$design} with {$n} {$unit} in {$setting}, measuring feasibility, acceptability and early outcomes.",
        };
    }

    /** Sample participants by country: names, institutions and phone format [prefix, digits]. */
    private const PEOPLE = [
        'TZ' => [
            'women' => ['Neema', 'Halima', 'Rehema', 'Zawadi', 'Agnes', 'Mwanaidi', 'Upendo', 'Lilian', 'Asha', 'Winfrida', 'Mariam', 'Happiness', 'Prisca', 'Nasra', 'Joyce', 'Catherine', 'Josephine', 'Sabina', 'Getrude', 'Anna', 'Rose', 'Veronica', 'Saida', 'Fatma', 'Witness', 'Glory', 'Elizabeth', 'Rukia'],
            'men' => ['Juma', 'Emmanuel', 'Daudi', 'Musa', 'Elia', 'Godfrey', 'Hassan', 'Paulo', 'Isaya', 'Abdallah', 'Salim', 'Yusuf', 'Frank', 'Ibrahim', 'Omari', 'Erasto', 'Saidi', 'Joseph', 'Gerald', 'Elisha', 'Hamisi', 'Rajabu', 'Selemani', 'Nassoro', 'Godlisten', 'Innocent', 'Deogratias'],
            'last' => ['Mushi', 'Mwakalinga', 'Swai', 'Lema', 'Kweka', 'Massawe', 'Ngowi', 'Mbise', 'Shirima', 'Chacha', 'Komba', 'Haule', 'Mtui', 'Urassa', 'Mfinanga', 'Salum', 'Mgaya', 'Minja', 'Mrisho', 'Kitwana', 'Magesa', 'Temba', 'Malisa', 'Mbwana', 'Kileo', 'Sanga', 'Pallangyo', 'Mapunda', 'Kapinga', 'Mwita', 'Marwa', 'Nyagawa', 'Mtenga', 'Kisanga', 'Mwakasege'],
            'institutions' => [
                'Muhimbili National Hospital', 'Kilimanjaro Christian Medical Centre', 'Bugando Medical Centre', 'CCBRT Disability Hospital',
                'Benjamin Mkapa Hospital', 'Mbeya Zonal Referral Hospital', 'Muhimbili University of Health and Allied Sciences',
                'Kilimanjaro Christian Medical University College', 'Aga Khan Hospital, Dar es Salaam', 'Mnazi Mmoja Hospital, Zanzibar',
                'Ministry of Health, Tanzania', 'Arusha Lutheran Medical Centre', 'Dodoma Regional Referral Hospital', 'Tanzania Physiotherapy Association',
                'Muhimbili Orthopaedic Institute', 'Catholic University of Health and Allied Sciences',
            ],
            'phone' => ['+2557', 8],
        ],
        'KE' => [
            'women' => ['Wanjiru', 'Achieng', 'Njeri', 'Akinyi', 'Wambui', 'Chebet', 'Nafula'],
            'men' => ['Kevin', 'Brian', 'Kennedy', 'Dennis', 'Collins', 'Samuel', 'Kiprotich'],
            'last' => ['Kamau', 'Otieno', 'Odhiambo', 'Mwangi', 'Kiprop', 'Wekesa', 'Njoroge', 'Ochieng', 'Mutua'],
            'institutions' => ['Kenyatta National Hospital', 'Moi Teaching and Referral Hospital', 'University of Nairobi', 'Kenya Medical Training College'],
            'phone' => ['+2547', 8],
        ],
        'UG' => [
            'women' => ['Patience', 'Brenda', 'Doreen', 'Sylvia', 'Prossy', 'Harriet'],
            'men' => ['Moses', 'Ronald', 'Isaac', 'Andrew', 'Godfrey', 'Denis'],
            'last' => ['Mugisha', 'Ssempala', 'Okello', 'Namubiru', 'Tumusiime', 'Kizza', 'Byaruhanga', 'Nalwoga'],
            'institutions' => ['Makerere University', 'Mulago National Referral Hospital', 'Mbarara University of Science and Technology'],
            'phone' => ['+2567', 8],
        ],
        'RW' => [
            'women' => ['Aline', 'Claudine', 'Diane', 'Josiane', 'Clarisse'],
            'men' => ['Eric', 'Patrick', 'Innocent', 'Olivier', 'Emmanuel'],
            'last' => ['Uwase', 'Niyonzima', 'Habimana', 'Mukamana', 'Uwimana', 'Ndayisaba'],
            'institutions' => ['University of Rwanda', 'University Teaching Hospital of Kigali'],
            'phone' => ['+25078', 7],
        ],
        'MW' => [
            'women' => ['Chikondi', 'Tamara', 'Thoko', 'Mercy', 'Tiwonge'],
            'men' => ['Chisomo', 'Mphatso', 'Kondwani', 'Blessings', 'Limbani'],
            'last' => ['Banda', 'Phiri', 'Mwale', 'Chirwa', 'Kumwenda', 'Nyirenda'],
            'institutions' => ['Kamuzu University of Health Sciences', 'Queen Elizabeth Central Hospital'],
            'phone' => ['+26599', 7],
        ],
        'ZM' => [
            'women' => ['Mutale', 'Chileshe', 'Bwalya', 'Natasha', 'Mwaka'],
            'men' => ['Mwila', 'Kabwe', 'Chanda', 'Musonda', 'Bupe'],
            'last' => ['Mulenga', 'Mwansa', 'Zulu', 'Tembo', 'Kapambwe', 'Lungu'],
            'institutions' => ['University Teaching Hospital, Lusaka', 'University of Zambia'],
            'phone' => ['+26097', 7],
        ],
        'ZA' => [
            'women' => ['Thandiwe', 'Nomsa', 'Lerato', 'Zanele', 'Naledi'],
            'men' => ['Sipho', 'Thabo', 'Lwazi', 'Kagiso', 'Pieter'],
            'last' => ['Dlamini', 'Nkosi', 'Mokoena', 'Ndlovu', 'Khumalo', 'van der Merwe'],
            'institutions' => ['University of Cape Town', 'University of the Witwatersrand', 'Stellenbosch University'],
            'phone' => ['+278', 8],
        ],
        'ET' => [
            'women' => ['Hiwot', 'Meron', 'Selam', 'Tigist', 'Bethlehem'],
            'men' => ['Abebe', 'Dawit', 'Yonas', 'Mekonnen', 'Henok'],
            'last' => ['Tesfaye', 'Bekele', 'Girma', 'Haile', 'Alemu', 'Getachew'],
            'institutions' => ['Addis Ababa University', 'University of Gondar'],
            'phone' => ['+2519', 8],
        ],
        'NG' => [
            'women' => ['Chidinma', 'Ngozi', 'Funmilayo', 'Adaeze', 'Zainab'],
            'men' => ['Chinedu', 'Oluwaseun', 'Emeka', 'Tunde', 'Abubakar'],
            'last' => ['Okafor', 'Adeyemi', 'Okonkwo', 'Bello', 'Eze', 'Adebayo'],
            'institutions' => ['Lagos University Teaching Hospital', 'University of Ibadan', 'Obafemi Awolowo University'],
            'phone' => ['+23480', 8],
        ],
        'GB' => [
            'women' => ['Rachel', 'Claire', 'Hannah', 'Emma'],
            'men' => ['James', 'Thomas', 'Daniel', 'Oliver'],
            'last' => ['Thompson', 'Hughes', 'Clarke', 'Walker', 'Bennett'],
            'institutions' => ['Humanity & Inclusion', 'London School of Hygiene & Tropical Medicine'],
            'phone' => ['+447', 9],
        ],
        'CH' => [
            'women' => ['Isabelle', 'Anna', 'Sophie'],
            'men' => ['Marc', 'Lukas', 'Pierre'],
            'last' => ['Dubois', 'Keller', 'Meier', 'Rossi'],
            'institutions' => ['World Health Organization'],
            'phone' => ['+4179', 7],
        ],
    ];

    /** [topic code, title, focus, setting, design, n, unit, background, main finding, second finding, conclusion] */
    private const ABSTRACTS = [
        ['HBR', 'Community-based stroke rehabilitation delivered by trained family caregivers in Dodoma', 'stroke rehabilitation', 'Dodoma Region, Tanzania', 'prospective cohort study', 84, 'stroke survivors and their main caregivers',
            'Most stroke survivors in rural Tanzania go home without any rehabilitation, and families provide nearly all of their care. We assessed whether family caregivers trained by physiotherapists could deliver basic rehabilitation at home.',
            'functional independence improved by a mean of 18 points on the Barthel Index at six months', 'Caregiver confidence scores rose, and two thirds of families were still doing the exercises without supervision at twelve months.',
            'Training family caregivers is a feasible way to bring stroke rehabilitation to rural households. The approach should be tested at district scale with community health workers providing follow-up.'],
        ['TAI', 'Low-cost 3D-printed prosthetic sockets: a pilot in northern Tanzania', '3D-printed prosthetics', 'Kilimanjaro Region, Tanzania', 'pilot feasibility study', 32, 'adults with transtibial amputation',
            'Conventional socket fabrication depends on plaster casting and scarce prosthetic technicians, so people often wait weeks for a limb. We tested whether scanning and 3D printing could shorten the process in a regional orthopaedic workshop.',
            'socket fitting time fell from five days to two, with comparable comfort scores', 'Material cost per socket fell by about 60%. Two sockets cracked and were reprinted within three months.',
            '3D-printed sockets are a promising option for regional workshops. A larger study of durability over at least a year is needed before wider use.'],
        ['FIN', 'Out-of-pocket costs of rehabilitation after lower-limb amputation in Dar es Salaam', 'post-amputation rehabilitation', 'Dar es Salaam, Tanzania', 'cross-sectional cost survey', 146, 'households of people with lower-limb amputation',
            'Rehabilitation after amputation, including the prosthesis, is rarely covered by insurance in Tanzania. The cost to households has not been measured.',
            'households spent a median of 38% of monthly income on rehabilitation-related costs', 'Transport accounted for a third of all costs, and 41% of households borrowed money or sold assets to pay.',
            'Rehabilitation after amputation pushes many households into financial hardship. Prostheses and transport support should be included in health financing reforms.'],
        ['OCC', 'Return to work after hand injuries among informal-sector workers in Arusha', 'hand therapy', 'Arusha, Tanzania', 'mixed-methods study', 61, 'informal-sector workers with hand injuries',
            'Hand injuries are common among carpenters, mechanics and market traders, who lose income for every day away from work. Little is known about how they recover.',
            'two thirds returned to work within twelve weeks of structured hand therapy', 'Workers who started therapy within two weeks of injury returned a median of three weeks sooner. Interviews showed that lost income was the main reason for missing sessions.',
            'Early referral to hand therapy should be part of trauma care, and sessions should be scheduled around the working day of self-employed patients.'],
        ['LEA', 'Building a national rehabilitation workforce strategy: lessons from Tanzania', 'rehabilitation workforce planning', 'Tanzania', 'policy analysis with key-informant interviews', 27, 'key informants from ministries, training institutions and professional bodies',
            'Tanzania has no costed plan for training and deploying rehabilitation professionals, and graduates often cannot find posts.',
            'all regions reported fewer than one physiotherapist per 100,000 people', 'Informants identified the lack of approved rehabilitation posts in local government as the main bottleneck, ahead of training capacity.',
            'A national rehabilitation workforce strategy should link the number of graduates to funded posts at district level.'],
        ['LIF', 'Early intervention for children with cerebral palsy in rural Mwanza', 'early childhood rehabilitation', 'Mwanza Region, Tanzania', 'quasi-experimental study', 112, 'children aged one to five years with cerebral palsy',
            'Children with cerebral palsy in rural Tanzania are often first seen by a therapist after the age of three, missing the period of greatest benefit. We evaluated a parent-led early intervention programme run through village health workers.',
            'gross motor scores improved significantly more in the intervention group (mean GMFM-66 gain 7.2 against 3.1)', 'Parents in the programme also reported less stress and a better understanding of their child\'s needs.',
            'Parent-led early intervention is effective for young children with cerebral palsy and can be delivered through existing community health structures.'],
        ['NCD', 'Pulmonary rehabilitation for post-tuberculosis lung disease in Mbeya', 'pulmonary rehabilitation', 'Mbeya, Tanzania', 'randomised controlled trial', 98, 'adults with post-tuberculosis lung disease',
            'Many people who complete tuberculosis treatment are left with breathlessness and reduced exercise capacity. Pulmonary rehabilitation is rarely offered to them.',
            'six-minute walk distance increased by 64 metres compared with usual care', 'Breathlessness and quality-of-life scores also improved, and there were no serious adverse events.',
            'A six-week pulmonary rehabilitation programme is safe and effective after tuberculosis. It can be delivered by physiotherapists in regional hospitals.'],
        ['WDI', 'Barriers to maternal health care for women with disabilities in Zanzibar', 'disability-inclusive maternal care', 'Zanzibar', 'qualitative study', 40, 'women with disabilities who had given birth in the previous two years',
            'Women with disabilities face higher risks during pregnancy and childbirth, yet little is known about their experience of maternal care in Zanzibar.',
            'inaccessible facilities and negative staff attitudes were the most reported barriers', 'Deaf women described the greatest difficulty communicating with staff, and several relied on relatives to interpret during labour.',
            'Maternal health quality standards should include physical accessibility, disability awareness training and access to sign language interpretation.'],
        ['RIN', 'Validation of the Kiswahili version of the WHO Disability Assessment Schedule', 'disability measurement', 'Tanzania', 'psychometric validation study', 320, 'adults attending rehabilitation and general outpatient clinics',
            'No validated Kiswahili instrument exists for measuring disability, which limits research and service planning in East Africa. We translated and validated the WHO Disability Assessment Schedule (WHODAS 2.0).',
            'the Kiswahili version showed excellent internal consistency (Cronbach\'s alpha 0.91)', 'Test-retest reliability was good (ICC 0.86), and scores were higher among rehabilitation patients, as expected.',
            'The Kiswahili WHODAS 2.0 is reliable and valid, and can be used in clinical practice, surveys and research.'],
        ['HBR', 'Tele-supported home exercise after knee replacement in Nairobi', 'home exercise programmes', 'Nairobi, Kenya', 'randomised controlled trial', 76, 'patients after total knee replacement',
            'Outpatient physiotherapy after knee replacement is hard to attend in Nairobi because of traffic and cost, and many patients stop exercising.',
            'adherence was 81% with weekly phone support against 52% without', 'Knee flexion and function scores at twelve weeks were also better in the phone-supported group.',
            'Weekly phone calls from a physiotherapist are a low-cost way to keep patients exercising at home after knee replacement.'],
        ['TAI', 'Smartphone gait analysis for community physiotherapists: accuracy and acceptability', 'mobile gait assessment', 'Kampala, Uganda', 'diagnostic accuracy study', 58, 'adults with gait impairments',
            'Community physiotherapists have no objective way to measure walking, and laboratory gait analysis is available only in a few capital cities.',
            'agreement with laboratory gait analysis was high (ICC 0.88)', 'Physiotherapists rated the application easy to use, and an assessment took under five minutes.',
            'Smartphone gait analysis is accurate and practical enough for routine use in community rehabilitation.'],
        ['FIN', 'Including assistive products in national health insurance benefit packages', 'assistive product financing', 'East Africa', 'comparative policy review', 6, 'national and social health insurance schemes',
            'Assistive products such as wheelchairs, hearing aids and spectacles are rarely paid for by health insurance in East Africa, leaving users to pay in full.',
            'only one of six schemes reimbursed wheelchairs and hearing aids', 'Where products were listed, reimbursement ceilings covered less than half of the market price.',
            'Health insurance benefit packages should include priority products from the WHO Priority Assistive Products List, with realistic reimbursement rates.'],
        ['OCC', 'Ergonomic training for hospital porters to prevent low back pain', 'workplace injury prevention', 'Moshi, Tanzania', 'cluster-randomised trial', 124, 'hospital porters in twelve wards',
            'Hospital porters lift and transfer patients many times a day, often without training or equipment, and back injuries are common.',
            'reported back pain episodes fell by 41% over twelve months', 'Sick leave for back pain fell by a third in the wards that received training.',
            'Ergonomic training is a simple and effective measure that hospitals should provide for all porters, alongside basic transfer equipment.'],
        ['LEA', 'Rehabilitation champions in district councils: an advocacy model', 'district rehabilitation advocacy', 'Tanga Region, Tanzania', 'case study', 11, 'district councils',
            'District councils plan and fund primary health care in Tanzania, but rehabilitation rarely appears in their budgets. We describe a model in which a trained "rehabilitation champion" sits on each council health management team.',
            'eight of eleven councils added rehabilitation lines to their annual budgets', 'Councils where the champion was a senior officer were most likely to fund new services.',
            'Rehabilitation champions are a low-cost advocacy model that fits Tanzania\'s decentralised health system.'],
        ['LIF', 'Falls prevention for older adults through community exercise groups', 'falls prevention', 'Moshi Rural, Tanzania', 'before-and-after study', 140, 'adults aged 60 and over',
            'Falls are a leading cause of injury and loss of independence among older adults, but prevention programmes are rare in rural Tanzania.',
            'the proportion reporting a fall in the previous six months dropped from 34% to 15%', 'Attendance at the weekly groups stayed above 70% throughout the year.',
            'Weekly community exercise groups are acceptable to older adults and reduce falls. They can be led by trained volunteers with physiotherapy supervision.'],
        ['NCD', 'Cardiac rehabilitation in a resource-limited referral hospital', 'cardiac rehabilitation', 'Dar es Salaam, Tanzania', 'retrospective cohort study', 210, 'patients referred for cardiac rehabilitation',
            'Cardiac rehabilitation reduces deaths and readmissions, but few programmes exist in sub-Saharan Africa. We reviewed the first five years of a hospital programme.',
            'completing the programme was associated with fewer readmissions within a year (adjusted odds ratio 0.52)', 'Only 38% of referred patients completed the programme, mostly because of the cost of travelling to sessions.',
            'Cardiac rehabilitation is feasible in a referral hospital, but home-based options are needed so that more patients complete it.'],
        ['WDI', 'Economic empowerment of women with disabilities through savings groups', 'livelihood rehabilitation', 'Morogoro, Tanzania', 'mixed-methods evaluation', 85, 'women with disabilities',
            'Women with disabilities are often excluded from credit and income-generating activities. We evaluated village savings groups made accessible to them.',
            'monthly income rose by a median of 45% after two years', 'Members also described greater confidence and a stronger voice in household decisions.',
            'Inclusive savings groups improve both income and participation, and complement clinical rehabilitation services.'],
        ['RIN', 'Mapping rehabilitation research in East Africa, 2010–2026', 'rehabilitation research', 'East Africa', 'scoping review', 412, 'studies',
            'Rehabilitation research in East Africa has grown quickly, but there is no overview of what has been studied and where the gaps are.',
            'most studies focused on stroke, and few addressed children or mental health', 'Three quarters of studies came from Kenya, Tanzania and Uganda, and fewer than one in five evaluated an intervention.',
            'Research funders should prioritise intervention studies and the conditions and populations that are under-represented.'],
        ['HBR', 'Home-based rehabilitation after spinal cord injury: a five-year follow-up', 'spinal cord injury rehabilitation', 'Moshi, Tanzania', 'longitudinal cohort study', 47, 'people with spinal cord injury',
            'People with spinal cord injury in Tanzania face high rates of pressure ulcers, infections and death after discharge from hospital.',
            'pressure ulcer rates halved among those receiving regular home visits', 'Five-year survival was 81%, higher than reported in earlier Tanzanian studies.',
            'Regular home visits reduce complications after spinal cord injury and should be part of discharge planning.'],
        ['TAI', 'Virtual reality balance training for children with developmental delay', 'virtual reality therapy', 'Kigali, Rwanda', 'pilot randomised trial', 30, 'children aged six to twelve with developmental delay',
            'Children with developmental delay need many hours of repetitive balance practice, which they often find tedious.',
            'balance scores improved more with virtual reality than with standard therapy', 'Children in the virtual reality group attended 94% of their sessions.',
            'Low-cost virtual reality games are an engaging addition to paediatric therapy. A full trial is warranted.'],
        ['NCD', 'Integrating rehabilitation into diabetes clinics: foot care and mobility', 'diabetes-related rehabilitation', 'Mwanza, Tanzania', 'implementation study', 230, 'people with diabetes',
            'Diabetic foot complications are a leading cause of lower-limb amputation in Tanzania, yet foot screening is rarely part of routine diabetes care.',
            'amputation referrals fell by a third after screening was introduced', 'Nurses screened 86% of eligible clinic attendees within the first year.',
            'Foot screening and mobility advice can be built into routine diabetes clinics with modest training for nurses.'],
        ['FIN', 'Cost-effectiveness of community wheelchair services in Malawi', 'wheelchair provision', 'Southern Malawi', 'economic evaluation', 150, 'wheelchair users',
            'Wheelchair services in Malawi are concentrated in a few hospitals, far from most of the people who need them.',
            'community provision cost 40% less per user than hospital-based services', 'Users fitted in the community also had fewer pressure sores at one year.',
            'Community wheelchair services are cost-effective and should be expanded through district health systems.'],
        ['OCC', 'Vocational rehabilitation for road traffic injury survivors', 'vocational rehabilitation', 'Dar es Salaam, Tanzania', 'prospective cohort study', 92, 'adults injured in road traffic crashes',
            'Road traffic injuries mainly affect young working adults, many of whom never return to work.',
            'those who received job-placement support were twice as likely to be employed at one year', 'People with lower-limb fractures benefited most from the support.',
            'Vocational rehabilitation should be linked to trauma care for road traffic injury survivors.'],
        ['LEA', 'Training emergency nurses in early rehabilitation: a national programme', 'early rehabilitation in acute care', 'Tanzania', 'programme evaluation', 360, 'emergency and ward nurses',
            'Early positioning and mobilisation prevent complications in hospital, but nurses receive little rehabilitation training.',
            'nurses\' knowledge scores rose from 48% to 79% after training', 'Ward audits showed more patients were positioned and mobilised within 48 hours of admission.',
            'Training nurses in early rehabilitation is feasible at national scale and changes practice on the wards.'],
        ['LIF', 'School-based screening and rehabilitation for children with hearing loss', 'paediatric hearing rehabilitation', 'Iringa, Tanzania', 'cross-sectional study', 1200, 'primary school children',
            'Undetected hearing loss harms children\'s learning, but hearing screening is not part of routine school health services in Tanzania.',
            'one in twenty-five children screened had previously undetected hearing loss', 'Two thirds of the children identified were seen by an audiology service within three months.',
            'School hearing screening is feasible and should be added to the national school health programme.'],
        ['WDI', 'Gender-based violence services for women with disabilities: an accessibility audit', 'accessible support services', 'Dar es Salaam, Tanzania', 'facility audit', 36, 'facilities offering gender-based violence services',
            'Women with disabilities face higher rates of violence, but may be unable to reach or use the services meant to support them.',
            'only four of thirty-six facilities met basic accessibility standards', 'No facility had information in accessible formats or staff able to communicate in sign language.',
            'Accessibility standards and staff training should be required for every gender-based violence service.'],
        ['RIN', 'A core outcome set for stroke rehabilitation trials in Africa', 'stroke outcome measurement', 'eleven African countries', 'Delphi consensus study', 64, 'clinicians, researchers and stroke survivors',
            'Stroke rehabilitation trials in Africa measure different outcomes, which makes their results hard to compare or combine.',
            'consensus was reached on nine core outcomes', 'Mobility, independence in daily activities and return to community roles were rated most important by stroke survivors.',
            'This core outcome set should be used in future stroke rehabilitation trials in Africa.'],
        ['HBR', 'Caregiver burden in home-based rehabilitation of traumatic brain injury', 'brain injury rehabilitation', 'Kilimanjaro Region, Tanzania', 'cross-sectional study', 70, 'family caregivers of people with traumatic brain injury',
            'Families provide most of the care after traumatic brain injury, usually with little information or support.',
            'high caregiver burden was reported by 57% of caregivers on the Zarit Burden Interview', 'Burden was highest among those caring for someone with changes in behaviour.',
            'Home-based rehabilitation after brain injury should include caregiver training and psychosocial support.'],
        ['TAI', 'SMS reminders to improve attendance at outpatient physiotherapy', 'appointment reminders', 'Arusha, Tanzania', 'randomised controlled trial', 300, 'physiotherapy outpatients',
            'Missed physiotherapy appointments waste clinic time and slow patients\' recovery.',
            'missed appointments fell from 31% to 17%', 'Each reminder cost less than 50 shillings to send.',
            'SMS reminders are a cheap and effective way to improve attendance at outpatient physiotherapy.'],
        ['NCD', 'Group exercise for people living with HIV and chronic pain', 'exercise therapy', 'Mbeya, Tanzania', 'randomised controlled trial', 120, 'adults living with HIV and chronic pain',
            'Chronic pain is common among people living with HIV and is rarely treated with exercise.',
            'pain interference scores improved significantly at twelve weeks', 'The benefit was maintained at six months among those who continued the group sessions.',
            'Group exercise is an effective, low-cost treatment for chronic pain that can be offered in HIV care and treatment centres.'],
        ['LIF', 'Rehabilitation needs of older adults after hip fracture in Tanzania', 'geriatric rehabilitation', 'Dar es Salaam, Tanzania', 'prospective cohort study', 66, 'adults aged 60 and over with hip fracture',
            'Hip fractures in older adults are increasing in Tanzania, but rehabilitation after surgery is limited.',
            'only one in five regained their previous walking ability at six months', 'Waiting more than a week for surgery was associated with poorer recovery.',
            'Faster surgery and structured rehabilitation are needed to improve recovery after hip fracture.'],
        ['OCC', 'Musculoskeletal disorders among smallholder farmers in Mbeya', 'occupational musculoskeletal health', 'Mbeya Rural, Tanzania', 'cross-sectional survey', 410, 'smallholder farmers',
            'Farming involves heavy lifting and long periods of bending, but the musculoskeletal health of Tanzanian farmers has received little attention.',
            'low back pain was reported by 68% of farmers in the past year', 'Pain was most often linked to hand-hoeing and carrying loads on the head.',
            'Rural health services should offer ergonomic advice and rehabilitation for agricultural workers.'],
        ['WDI', 'Inclusive education for girls with disabilities: a community programme', 'inclusive education', 'Dodoma, Tanzania', 'programme evaluation', 150, 'girls with disabilities',
            'Girls with disabilities are among the children most likely to be out of school in Tanzania.',
            'school attendance among enrolled girls rose to 89%', 'Teachers reported more confidence in adapting their lessons after training.',
            'Community programmes that combine teacher training, assistive devices and family support can keep girls with disabilities in school.'],
        ['RIN', 'Patient-reported experience of rehabilitation services in referral hospitals', 'patient experience', 'Tanzania', 'multi-site survey', 540, 'patients in six referral hospitals',
            'Patients\' views of rehabilitation services in Tanzania have rarely been measured.',
            'waiting time and cost were the most frequent complaints', 'Overall, 78% of patients were satisfied with the care they received from therapists.',
            'Reducing waiting times and costs should be priorities for improving rehabilitation services.'],
        ['LEA', 'Engaging parliamentarians on disability and rehabilitation policy', 'policy engagement', 'Tanzania', 'case study', 18, 'members of parliament',
            'Disability and rehabilitation receive little attention in parliamentary debate and budget scrutiny.',
            'a parliamentary caucus on rehabilitation was established within a year', 'Parliamentary questions on rehabilitation rose from two to eleven in the following session.',
            'Structured engagement with parliamentarians can raise the profile of rehabilitation in national policy and budgets.'],
        ['FIN', 'Community health fund coverage of physiotherapy services', 'health insurance coverage', 'Morogoro, Tanzania', 'cross-sectional study', 260, 'members of insured households',
            'Membership of the improved Community Health Fund covers physiotherapy at some facilities, but few members use it.',
            'only 12% of insured members knew physiotherapy was covered', 'Members who had been told about the benefit by a health worker were four times more likely to use it.',
            'Health workers and the fund should tell members that rehabilitation is covered, so that they use the benefit they have paid for.'],
    ];

    private const COMMENTS = [
        'good' => [
            'A clear and relevant study with practical implications for district services. The results section would benefit from confidence intervals, and the conclusion could be more specific about scale-up.',
            'Well written and highly relevant to the summit theme. Please clarify how participants were recruited and whether those lost to follow-up differed from completers.',
            'An important contribution from an under-researched setting. The methods are appropriate; consider reporting the effect size alongside the main outcome.',
            'Strong local evidence with clear policy messages. A short note on cost or feasibility would make the conclusions even more useful for decision makers.',
        ],
        'revise' => [
            'Promising and relevant, but not ready as it stands. Please describe how participants were recruited, report the main result with a confidence interval, and make the conclusion match the data.',
            'The question matters for district services. Before acceptance, the methods need the study design and outcome measures, and the results need numbers rather than a summary.',
            'A useful study that I would accept with changes: shorten the background, add the sample size and follow-up period, and state the main limitation.',
            'Good local relevance. The abstract would be stronger with clearer results: give the effect size and say how missing data were handled.',
        ],
        'weak' => [
            'The topic is relevant, but the methods are not described in enough detail to judge the findings. Please state the study design, sample size calculation and outcome measures.',
            'The conclusions go beyond what the results show. With such a small sample, the findings should be presented as preliminary.',
            'Interesting question, but the abstract reads as a project description rather than a study. Please add results with numbers.',
            'Several statements need supporting data. The link between the intervention and the reported outcomes is not clear.',
        ],
        // Reviews of a revised version.
        'second' => [
            'The revision answers my comments: recruitment is now described and the main result has a confidence interval. I am happy to accept.',
            'Thank you for the careful revision. The methods are now clear, and the conclusions follow from the results.',
        ],
    ];
}
