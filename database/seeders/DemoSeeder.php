<?php

namespace Database\Seeders;

use App\Enums\AbstractStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PresentationType;
use App\Enums\Recommendation;
use App\Enums\RegistrationStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Edition;
use App\Models\Payment;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Models\RegistrationCategory;
use App\Models\Topic;
use App\Models\User;
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
        $this->abstracts($participants, $staff['scientific']);
        $this->programme();
        $this->deskAndNotifications();
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
        $pending = $amina->abstracts()->where('status', AbstractStatus::UnderReview)->first();
        $amina->notifications()->delete();
        $notify($amina, 'Registration confirmed', 'Your badge and invitation letter are ready to download.', route('registration.badge.show'), 'check-circle', 'success', '-20 days', true);
        if ($pending) {
            $notify($amina, 'Abstract received', '"'.$pending->title.'" is with the scientific committee.', route('abstracts.show', $pending), 'document', 'info', '-6 days', true);
        }
        if ($accepted) {
            $notify($amina, 'Abstract accepted · '.$accepted->code, '"'.$accepted->title.'"', route('abstracts.show', $accepted), 'clipboard', 'success', '-2 days', false);
        }
        $notify($amina, 'Programme published', 'The '.$this->edition->name.' '.$this->edition->year.' programme is online. Plan your sessions.', route('programme'), 'calendar', 'info', '-5 hours', false);

        $grace = User::where('email', 'reviewer@rehab.test')->first();
        $grace->notifications()->delete();
        $grace->reviewAssignments()->whereNull('completed_at')->with('abstract.topic', 'abstract.edition')->get()
            ->each(fn ($a, $i) => $notify($grace, 'New abstract to review', $a->abstract->blindId().' · '.$a->abstract->topic->name, route('reviews.edit', $a), 'star', 'warning', '-'.($i * 2 + 1).' days', $i > 1));
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
        $women = ['Neema', 'Halima', 'Rehema', 'Zawadi', 'Agnes', 'Mwanaidi', 'Upendo', 'Lilian', 'Asha', 'Winfrida', 'Faith', 'Mariam', 'Esther', 'Catherine', 'Joyce', 'Aisha', 'Josephine', 'Grace', 'Sabina', 'Diana', 'Wanjiru', 'Aline', 'Chikondi', 'Thandiwe', 'Sarah', 'Chidinma', 'Nasra', 'Happiness', 'Prisca'];
        $men = ['Juma', 'Emmanuel', 'Daudi', 'Musa', 'Elia', 'Godfrey', 'Hassan', 'Paulo', 'Isaya', 'Abdallah', 'Kelvin', 'Brian', 'Salim', 'Yusuf', 'Frank', 'Victor', 'Ibrahim', 'Kennedy', 'Omari', 'Kato', 'Abebe', 'Michael', 'Mutale', 'Erasto', 'Saidi'];
        $last = ['Mushi', 'Mwakalinga', 'Swai', 'Lema', 'Kweka', 'Massawe', 'Ngowi', 'Mbise', 'Shirima', 'Chacha', 'Komba', 'Haule', 'Mtui', 'Urassa', 'Mfinanga', 'Nyerere', 'Salum', 'Mgaya', 'Minja', 'Mrisho', 'Kamau', 'Achieng', 'Mugisha', 'Uwase', 'Banda', 'Phiri', 'Dlamini', 'Tesfaye', 'Okafor', 'Mulenga', 'Juma', 'Kitwana', 'Magesa', 'Temba', 'Moshi', 'Malisa', 'Mbwana', 'Kileo', 'Sanga', 'Pallangyo'];
        $local = [
            'Muhimbili National Hospital', 'Kilimanjaro Christian Medical Centre', 'Bugando Medical Centre', 'CCBRT Disability Hospital',
            'Benjamin Mkapa Hospital', 'Mbeya Zonal Referral Hospital', 'Muhimbili University of Health and Allied Sciences',
            'Kilimanjaro Christian Medical University College', 'Aga Khan Hospital, Dar es Salaam', 'Mnazi Mmoja Hospital, Zanzibar',
            'Ministry of Health, Tanzania', 'Arusha Lutheran Medical Centre', 'Dodoma Regional Referral Hospital', 'Tanzania Physiotherapy Association',
        ];
        $abroad = [
            ['Kenyatta National Hospital', 'KE'], ['Moi Teaching and Referral Hospital', 'KE'], ['Makerere University', 'UG'],
            ['Mulago National Referral Hospital', 'UG'], ['University of Rwanda', 'RW'], ['Kamuzu University of Health Sciences', 'MW'],
            ['University Teaching Hospital, Lusaka', 'ZM'], ['University of Cape Town', 'ZA'], ['Addis Ababa University', 'ET'],
            ['Lagos University Teaching Hospital', 'NG'], ['Humanity & Inclusion', 'GB'], ['World Health Organization', 'CH'],
        ];
        $professions = ['Physiotherapist', 'Physiotherapist', 'Physiotherapist', 'Occupational therapist', 'Occupational therapist', 'Speech and language therapist', 'Prosthetist-orthotist', 'Rehabilitation physician', 'Nurse', 'Public health specialist', 'Researcher', 'Health policy adviser'];

        $make = function (string $email, ?string $title, string $first, string $last, string $institution, string $country, string $profession, ?string $verifiedAgo = '-2 months') {
            $user = User::updateOrCreate(['email' => $email], [
                'title' => $title, 'first_name' => $first, 'last_name' => $last,
                'phone' => ($country === 'TZ' ? '+2557' : '+2547').mt_rand(10000000, 99999999),
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

        for ($i = 0; $i < 198; $i++) {
            // About seven in ten participants are from Tanzania.
            [$institution, $country] = mt_rand(1, 100) <= 72
                ? [$local[mt_rand(0, count($local) - 1)], 'TZ']
                : $abroad[mt_rand(0, count($abroad) - 1)];
            $woman = $i % 2 === 0;
            $firstName = $woman ? $women[intdiv($i, 2) % count($women)] : $men[intdiv($i, 2) % count($men)];
            $titles = $woman ? ['Dr', 'Ms', 'Mrs', null] : ['Dr', 'Mr', null];
            $lastName = $last[mt_rand(0, count($last) - 1)];
            $email = Str::lower(Str::ascii($firstName.'.'.$lastName)).($i + 1).'@example.org';
            $people->push($make($email, $titles[mt_rand(0, count($titles) - 1)], $firstName, $lastName, $institution, $country,
                $professions[mt_rand(0, count($professions) - 1)], '-'.mt_rand(3, 150).' days'));
        }

        return $people;
    }

    /** Registrations and payments at every stage. */
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

    /** Abstracts at every stage, reviewed double-blind, some decided. */
    private function abstracts(Collection $participants, User $scientific): void
    {
        AbstractSubmission::where('edition_id', $this->edition->id)->delete();

        $authors = $participants->reject(fn (User $u) => $u->email === 'newcomer@rehab.test')->values();
        $plan = array_merge(
            array_fill(0, 15, 'accepted'), array_fill(0, 4, 'rejected'),
            array_fill(0, 9, 'under_review'), array_fill(0, 6, 'submitted'), array_fill(0, 2, 'draft'),
        );

        foreach (self::ABSTRACTS as $i => [$code, $title, $focus, $setting, $design, $n, $finding]) {
            // The demo participant owns two: one accepted, one under review, plus a draft.
            $owner = match ($i) {
                0, 20 => $authors->first(),
                default => $authors[1 + ($i % ($authors->count() - 1))],
            };
            $state = $i === 20 ? 'under_review' : ($i === 35 ? 'draft' : $plan[$i % count($plan)]);
            if ($i === 35) {
                $owner = $authors->first();
            }

            // Decided abstracts were submitted long enough ago to have been reviewed.
            $minAge = match ($state) {
                'accepted', 'rejected' => 16, 'under_review' => 4, default => 0
            };
            $submittedAt = CarbonImmutable::now()->subDays($minAge + (int) round(40 * (mt_rand() / mt_getrandmax()) ** 1.4))->setTime(mt_rand(7, 21), mt_rand(0, 59));
            $abstract = AbstractSubmission::create([
                'edition_id' => $this->edition->id,
                'user_id' => $owner->id,
                'topic_id' => $this->topics[$code]->id,
                'preferred_type' => [PresentationType::Oral, PresentationType::Poster, PresentationType::Either][mt_rand(0, 2)],
                'title' => $title,
                'background' => "Access to {$focus} remains limited in {$setting}, where most people who need rehabilitation never receive it. Services are concentrated in referral hospitals, and little local evidence exists to guide planning.",
                'methods' => 'We conducted '.(preg_match('/^[aeiou]/i', $design) ? 'an' : 'a')." {$design} in {$setting}. {$n} participants were included. Data were collected with standardised outcome measures and structured interviews, and analysed descriptively and thematically.",
                'results' => ucfirst($finding).'. Participants and families valued care delivered closer to home, while staff shortages, transport costs and irregular supplies were the main barriers reported.',
                'conclusions' => "Our findings support integrating {$focus} into routine primary health care. Training, supervision and sustainable financing are needed to scale up and sustain the approach.",
                'keywords' => Str::lower($focus).', '.Str::lower(Str::before($setting, ',')).', rehabilitation',
                'status' => $state === 'draft' ? AbstractStatus::Draft : AbstractStatus::Submitted,
                'submitted_at' => $state === 'draft' ? null : $submittedAt,
            ]);
            $abstract->forceFill(['created_at' => $submittedAt->subDays(2)])->save();

            // Authors: the submitter first, then one or two co-authors from the participant list.
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

            // Two or three reviewers who are not authors of this abstract.
            $authorEmails = $abstract->authors()->pluck('email');
            $panel = $this->reviewers->reject(fn (User $r) => $authorEmails->contains($r->email))->shuffle(mt_rand())->take(mt_rand(2, 3))->values();
            $positive = $state === 'accepted' ? true : ($state === 'rejected' ? false : null);

            foreach ($panel as $r => $reviewer) {
                $complete = $state !== 'under_review' || $r === 0 || ($reviewer->email !== 'reviewer@rehab.test' && mt_rand(0, 1));
                $good = $positive ?? (mt_rand(1, 100) <= 65);
                $scores = $good ? [mt_rand(4, 5), mt_rand(3, 5), mt_rand(3, 5), mt_rand(4, 5)] : [mt_rand(2, 3), mt_rand(1, 3), mt_rand(1, 3), mt_rand(2, 4)];

                $abstract->reviews()->create([
                    'reviewer_id' => $reviewer->id,
                    'assigned_by' => $scientific->id,
                    'due_on' => $this->edition->review_deadline,
                    'score_relevance' => $complete ? $scores[0] : null,
                    'score_originality' => $complete ? $scores[1] : null,
                    'score_methods' => $complete ? $scores[2] : null,
                    'score_clarity' => $complete ? $scores[3] : null,
                    'recommendation' => $complete ? ($good ? (array_sum($scores) >= 17 ? Recommendation::AcceptOral : Recommendation::AcceptPoster) : Recommendation::Reject) : null,
                    'comments_for_author' => $complete ? self::COMMENTS[$good ? 'good' : 'weak'][mt_rand(0, 3)] : null,
                    'comments_for_committee' => $complete && mt_rand(1, 4) === 1 ? 'Strong local relevance; consider for the plenary-adjacent parallel session.' : null,
                    'completed_at' => $complete ? $submittedAt->addDays(mt_rand(2, max(2, $minAge - 3)))->min(CarbonImmutable::now()->subHours(mt_rand(1, 30))) : null,
                ]);
            }

            if ($state === 'under_review') {
                $abstract->update(['status' => AbstractStatus::UnderReview]);

                continue;
            }

            if ($state === 'rejected') {
                $abstract->update(['status' => AbstractStatus::Rejected, 'decided_at' => $submittedAt->addDays($minAge - 1),
                    'decision_note' => 'The committee encourages you to strengthen the methods section and resubmit next year.']);

                continue;
            }

            $type = ($i % 3 === 2) ? PresentationType::Poster : PresentationType::Oral;
            $prefix = $type->codePrefix().'-'.$code.'-';
            $number = AbstractSubmission::where('code', 'like', $prefix.'%')->count() + 1;
            $abstract->update([
                'status' => AbstractStatus::Accepted,
                'decision_type' => $type,
                'code' => $prefix.str_pad((string) $number, 2, '0', STR_PAD_LEFT),
                'decided_at' => $submittedAt->addDays($minAge - 1),
            ]);
        }

        // The demo reviewer always has work waiting: two fresh submissions.
        $reviewer = $this->reviewers->first();
        AbstractSubmission::where('edition_id', $this->edition->id)->where('status', AbstractStatus::Submitted)
            ->whereDoesntHave('authors', fn ($q) => $q->where('email', $reviewer->email))
            ->limit(2)->get()
            ->each(function (AbstractSubmission $abstract) use ($reviewer, $scientific) {
                $abstract->reviews()->create(['reviewer_id' => $reviewer->id, 'assigned_by' => $scientific->id, 'due_on' => $this->edition->review_deadline]);
                $abstract->update(['status' => AbstractStatus::UnderReview]);
            });
    }

    /** Three days: plenaries, parallel oral sessions by topic, posters, panels and breaks. */
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

        $attach($add('2027-09-15', '16:00', '17:30', 'posters', 'Poster session I', 'Exhibition Hall', null, 'Dr Esther Mollel',
            'Presenters stand at their posters for questions.'), $posters->take(3));

        // Day 2
        $add('2027-09-16', '09:00', '10:30', 'plenary', 'Keynote: Assistive technology for all', 'Main Hall', 'TAI', 'Prof David Kasozi');
        $add('2027-09-16', '10:30', '11:00', 'break', 'Tea break');
        $add('2027-09-16', '12:30', '14:00', 'break', 'Lunch');
        $add('2027-09-16', '14:00', '15:30', 'panel', 'Financing rehabilitation: insurance, budgets and partnerships', 'Main Hall', 'FIN', 'Dr Fatuma Mbwambo');
        $attach($add('2027-09-16', '16:00', '17:30', 'posters', 'Poster session II', 'Exhibition Hall', null, 'Dr Hellen Otieno',
            'Presenters stand at their posters for questions.'), $posters->slice(3));

        // Day 3
        $add('2027-09-17', '10:30', '11:00', 'break', 'Tea break');
        $add('2027-09-17', '11:00', '12:00', 'plenary', 'Rapporteur reports', 'Main Hall', null, 'Dr Samuel Nkurunziza', 'Summaries and recommendations from every session.');
        $add('2027-09-17', '12:00', '13:00', 'plenary', 'Closing ceremony and awards', 'Main Hall', null, 'Prof Grace Mwakyusa');
    }

    /** [topic code, title, focus, setting, design, participants, finding] */
    private const ABSTRACTS = [
        ['HBR', 'Community-based stroke rehabilitation delivered by trained family caregivers in Dodoma', 'stroke rehabilitation', 'Dodoma Region, Tanzania', 'prospective cohort study', 84, 'functional independence improved by a mean of 18 points on the Barthel Index at six months'],
        ['TAI', 'Low-cost 3D-printed prosthetic sockets: a pilot in northern Tanzania', '3D-printed prosthetics', 'Kilimanjaro Region, Tanzania', 'pilot feasibility study', 32, 'socket fitting time fell from five days to two, with comparable comfort scores'],
        ['FIN', 'Out-of-pocket costs of rehabilitation after lower-limb amputation in Dar es Salaam', 'post-amputation rehabilitation', 'Dar es Salaam, Tanzania', 'cross-sectional cost survey', 146, 'households spent a median of 38% of monthly income on rehabilitation-related costs'],
        ['OCC', 'Return to work after hand injuries among informal-sector workers in Arusha', 'hand therapy', 'Arusha, Tanzania', 'mixed-methods study', 61, 'two thirds returned to work within twelve weeks of structured hand therapy'],
        ['LEA', 'Building a national rehabilitation workforce strategy: lessons from Tanzania', 'rehabilitation workforce planning', 'Tanzania', 'policy analysis with key-informant interviews', 27, 'all regions reported fewer than one physiotherapist per 100,000 people'],
        ['LIF', 'Early intervention for children with cerebral palsy in rural Mwanza', 'early childhood rehabilitation', 'Mwanza Region, Tanzania', 'quasi-experimental study', 112, 'gross motor scores improved significantly more in the intervention group'],
        ['NCD', 'Pulmonary rehabilitation for post-tuberculosis lung disease in Mbeya', 'pulmonary rehabilitation', 'Mbeya, Tanzania', 'randomised controlled trial', 98, 'six-minute walk distance increased by 64 metres compared with usual care'],
        ['WDI', 'Barriers to maternal health care for women with disabilities in Zanzibar', 'disability-inclusive maternal care', 'Zanzibar', 'qualitative study', 40, 'inaccessible facilities and negative staff attitudes were the most reported barriers'],
        ['RIN', 'Validation of the Kiswahili version of the WHO Disability Assessment Schedule', 'disability measurement', 'Tanzania', 'psychometric validation study', 320, 'the Kiswahili version showed excellent internal consistency (alpha 0.91)'],
        ['HBR', 'Tele-supported home exercise after knee replacement in Nairobi', 'home exercise programmes', 'Nairobi, Kenya', 'randomised controlled trial', 76, 'adherence was 81% with weekly phone support against 52% without'],
        ['TAI', 'Smartphone gait analysis for community physiotherapists: accuracy and acceptability', 'mobile gait assessment', 'Kampala, Uganda', 'diagnostic accuracy study', 58, 'agreement with laboratory gait analysis was high (ICC 0.88)'],
        ['FIN', 'Including assistive products in national health insurance benefit packages', 'assistive product financing', 'East Africa', 'comparative policy review', 6, 'only one of six schemes reimbursed wheelchairs and hearing aids'],
        ['OCC', 'Ergonomic training for hospital porters to prevent low back pain', 'workplace injury prevention', 'Moshi, Tanzania', 'cluster-randomised trial', 124, 'reported back pain episodes fell by 41% over twelve months'],
        ['LEA', 'Rehabilitation champions in district councils: an advocacy model', 'district rehabilitation advocacy', 'Tanga Region, Tanzania', 'case study', 11, 'eight of eleven councils added rehabilitation lines to their annual budgets'],
        ['LIF', 'Falls prevention for older adults through community exercise groups', 'falls prevention', 'Moshi Rural, Tanzania', 'before-and-after study', 140, 'falls in the previous six months dropped from 34% to 15%'],
        ['NCD', 'Cardiac rehabilitation in a resource-limited referral hospital', 'cardiac rehabilitation', 'Dar es Salaam, Tanzania', 'retrospective cohort study', 210, 'completion of the programme was associated with fewer readmissions'],
        ['WDI', 'Economic empowerment of women with disabilities through savings groups', 'livelihood rehabilitation', 'Morogoro, Tanzania', 'mixed-methods evaluation', 85, 'monthly income rose by a median of 45% after two years'],
        ['RIN', 'Mapping rehabilitation research in East Africa, 2010–2026', 'rehabilitation research', 'East Africa', 'scoping review', 412, 'most studies focused on stroke and few addressed children or mental health'],
        ['HBR', 'Home-based rehabilitation after spinal cord injury: a five-year follow-up', 'spinal cord injury rehabilitation', 'Moshi, Tanzania', 'longitudinal cohort study', 47, 'pressure ulcer rates halved among those receiving regular home visits'],
        ['TAI', 'Virtual reality balance training for children with developmental delay', 'virtual reality therapy', 'Kigali, Rwanda', 'pilot randomised trial', 30, 'balance scores improved more with virtual reality than with standard therapy'],
        ['NCD', 'Integrating rehabilitation into diabetes clinics: foot care and mobility', 'diabetes-related rehabilitation', 'Mwanza, Tanzania', 'implementation study', 230, 'amputation referrals fell by a third after screening was introduced'],
        ['FIN', 'Cost-effectiveness of community wheelchair services in Malawi', 'wheelchair provision', 'Southern Malawi', 'economic evaluation', 150, 'community provision cost 40% less per user than hospital-based services'],
        ['OCC', 'Vocational rehabilitation for road traffic injury survivors', 'vocational rehabilitation', 'Dar es Salaam, Tanzania', 'prospective cohort study', 92, 'employment at one year was twice as likely with job-placement support'],
        ['LEA', 'Training emergency nurses in early rehabilitation: a national programme', 'early rehabilitation in acute care', 'Tanzania', 'programme evaluation', 360, 'nurses\' knowledge scores rose from 48% to 79% after training'],
        ['LIF', 'School-based screening and rehabilitation for children with hearing loss', 'paediatric hearing rehabilitation', 'Iringa, Tanzania', 'cross-sectional study', 1200, 'one in twenty-five children screened had previously undetected hearing loss'],
        ['WDI', 'Gender-based violence services for women with disabilities: an accessibility audit', 'accessible support services', 'Dar es Salaam, Tanzania', 'facility audit', 36, 'only four of thirty-six facilities met basic accessibility standards'],
        ['RIN', 'A core outcome set for stroke rehabilitation trials in Africa', 'stroke outcome measurement', 'sub-Saharan Africa', 'Delphi consensus study', 64, 'consensus was reached on nine core outcomes'],
        ['HBR', 'Caregiver burden in home-based rehabilitation of traumatic brain injury', 'brain injury rehabilitation', 'Kilimanjaro Region, Tanzania', 'cross-sectional study', 70, 'high caregiver burden was reported by 57% of caregivers'],
        ['TAI', 'SMS reminders to improve attendance at outpatient physiotherapy', 'appointment reminders', 'Arusha, Tanzania', 'randomised controlled trial', 300, 'missed appointments fell from 31% to 17%'],
        ['NCD', 'Group exercise for people living with HIV and chronic pain', 'exercise therapy', 'Mbeya, Tanzania', 'randomised controlled trial', 120, 'pain interference scores improved significantly at twelve weeks'],
        ['LIF', 'Rehabilitation needs of older adults after hip fracture in Tanzania', 'geriatric rehabilitation', 'Dar es Salaam, Tanzania', 'prospective cohort study', 66, 'only one in five regained their previous walking ability at six months'],
        ['OCC', 'Musculoskeletal disorders among smallholder farmers in Mbeya', 'occupational musculoskeletal health', 'Mbeya Rural, Tanzania', 'cross-sectional survey', 410, 'low back pain was reported by 68% of farmers in the past year'],
        ['WDI', 'Inclusive education for girls with disabilities: a community programme', 'inclusive education', 'Dodoma, Tanzania', 'programme evaluation', 150, 'school attendance among enrolled girls rose to 89%'],
        ['RIN', 'Patient-reported experience of rehabilitation services in referral hospitals', 'patient experience', 'Tanzania', 'multi-site survey', 540, 'waiting time and cost were the most frequent complaints'],
        ['LEA', 'Engaging parliamentarians on disability and rehabilitation policy', 'policy engagement', 'Tanzania', 'case study', 18, 'a parliamentary caucus on rehabilitation was established within a year'],
        ['FIN', 'Community health fund coverage of physiotherapy services', 'health insurance coverage', 'Morogoro, Tanzania', 'cross-sectional study', 260, 'only 12% of insured members knew physiotherapy was covered'],
    ];

    private const COMMENTS = [
        'good' => [
            'A clear and relevant study with practical implications for district services. The results section would benefit from confidence intervals, and the conclusion could be more specific about scale-up.',
            'Well written and highly relevant to the summit theme. Please clarify how participants were recruited and whether those lost to follow-up differed from completers.',
            'An important contribution from an under-researched setting. The methods are appropriate; consider reporting the effect size alongside the main outcome.',
            'Strong local evidence with clear policy messages. A short note on cost or feasibility would make the conclusions even more useful for decision makers.',
        ],
        'weak' => [
            'The topic is relevant, but the methods are not described in enough detail to judge the findings. Please state the study design, sample size calculation and outcome measures.',
            'The conclusions go beyond what the results show. With such a small sample, the findings should be presented as preliminary.',
            'Interesting question, but the abstract reads as a project description rather than a study. Please add results with numbers.',
            'Several statements need supporting data. The link between the intervention and the reported outcomes is not clear.',
        ],
    ];
}
