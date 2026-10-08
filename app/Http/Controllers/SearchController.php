<?php

namespace App\Http\Controllers;

use App\Enums\AbstractStatus;
use App\Enums\Role;
use App\Models\AbstractSubmission;
use App\Models\Payment;
use App\Models\ProgrammeSession;
use App\Models\Registration;
use App\Support\Summit;
use App\Support\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The search box in the top bar. Each person only finds what the role they
 * are working in can see: reviewers never see authors, participants only
 * their own abstracts.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, Summit $summit): View
    {
        $user = $request->user();
        $q = trim((string) $request->query('q'));
        $edition = $summit->edition();
        $like = '%'.$q.'%';
        $results = [];

        if (mb_strlen($q) >= 2 && $edition) {
            $staff = Workspace::is($user, Role::Admin, Role::RegistrationOfficer, Role::FinanceOfficer);
            $committee = Workspace::is($user, Role::Admin, Role::ScientificAdmin);

            if ($staff) {
                $results['People'] = Registration::where('edition_id', $edition->id)->with('user', 'category')
                    ->where(fn ($w) => $w->where('reference', 'like', $like)->orWhereHas('user', fn ($u) => $u
                        ->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('email', 'like', $like)->orWhere('institution', 'like', $like)))
                    ->limit(8)->get()
                    ->map(fn ($r) => [
                        'title' => $r->user->name,
                        'meta' => $r->reference.' · '.$r->category->name.' · '.$r->status->label(),
                        'url' => Workspace::is($user, Role::Admin) ? route('admin.participants.show', $r) : route('desk.index', ['q' => $r->reference]),
                    ]);
            }

            if (Workspace::is($user, Role::Admin, Role::FinanceOfficer)) {
                $results['Payments'] = Payment::with('registration.user')
                    ->where(fn ($w) => $w->where('transaction_reference', 'like', $like)->orWhere('payer_name', 'like', $like)
                        ->orWhereHas('registration', fn ($r) => $r->where('reference', 'like', $like)))
                    ->limit(8)->get()
                    ->map(fn ($p) => [
                        'title' => $p->registration->user->name.' · '.$p->formattedAmount(),
                        'meta' => $p->channel().' · '.$p->transaction_reference.' · '.$p->status->label(),
                        'url' => route('finance.payments.show', $p),
                    ]);
            }

            if ($committee) {
                $results['Abstracts'] = AbstractSubmission::where('edition_id', $edition->id)->where('status', '!=', AbstractStatus::Draft)->with('topic', 'submitter')
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('code', 'like', $like)->orWhere('keywords', 'like', $like)
                        ->orWhereHas('submitter', fn ($s) => $s->where('last_name', 'like', $like)))
                    ->limit(8)->get()
                    ->map(fn ($a) => [
                        'title' => $a->title,
                        'meta' => ($a->code ?? $a->blindId()).' · '.$a->topic->name.' · '.$a->submitter->name.' · '.$a->status->label(),
                        'url' => route('scientific.abstracts.show', $a),
                    ]);
            } elseif (Workspace::is($user, Role::Reviewer)) {
                // Blind: only their own assignments, and never the authors.
                $results['Your assigned abstracts'] = $user->reviewAssignments()->with('abstract.topic', 'abstract.edition')
                    ->whereHas('abstract', fn ($a) => $a->where('title', 'like', $like)->orWhere('keywords', 'like', $like))
                    ->limit(8)->get()
                    ->map(fn ($r) => [
                        'title' => $r->abstract->title,
                        'meta' => $r->abstract->blindId().' · '.$r->abstract->topic->name.' · '.($r->isComplete() ? 'Reviewed' : 'Waiting for you'),
                        'url' => route('reviews.edit', $r),
                    ]);
            }

            if (Workspace::is($user, Role::Participant) && $user->hasRole(Role::Participant->value)) {
                $results['Your abstracts'] = $user->abstracts()->where('edition_id', $edition->id)->with('topic')
                    ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('code', 'like', $like))
                    ->limit(8)->get()
                    ->map(fn ($a) => ['title' => $a->title, 'meta' => ($a->code ?? $a->topic->name).' · '.$a->status->label(), 'url' => route('abstracts.show', $a)]);
            }

            $results['Programme'] = ProgrammeSession::where('edition_id', $edition->id)->where('kind', '!=', 'break')
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('chair', 'like', $like)->orWhere('hall', 'like', $like)
                    ->orWhereHas('abstracts', fn ($a) => $a->where('title', 'like', $like)->orWhere('code', 'like', $like)))
                ->orderBy('starts_at')->limit(8)->get()
                ->map(fn ($s) => ['title' => $s->title, 'meta' => $s->starts_at->format('D j M, H:i').' · '.$s->kindLabel().($s->hall ? ' · '.$s->hall : ''), 'url' => route('programme')]);
        }

        return view('search', [
            'q' => $q,
            'results' => collect($results)->filter(fn ($items) => $items->isNotEmpty()),
        ]);
    }
}
