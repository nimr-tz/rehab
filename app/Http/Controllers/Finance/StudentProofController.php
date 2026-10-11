<?php

namespace App\Http\Controllers\Finance;

use App\Enums\StudentProofStatus;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\RegistrationService;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Proofs of enrolment for student rates. Approved before the student can pay. */
class StudentProofController extends Controller
{
    public function index(Request $request, Summit $summit): View
    {
        $status = StudentProofStatus::tryFrom((string) $request->query('status')) ?? StudentProofStatus::Pending;
        $base = fn () => Registration::where('edition_id', $summit->edition()?->id)->whereNotNull('student_status');

        return view('finance.students.index', [
            'status' => $status,
            'registrations' => $base()->where('student_status', $status)->with('user', 'category', 'studentReviewer')
                ->oldest('updated_at')->paginate(20)->withQueryString(),
            'counts' => $base()->selectRaw('student_status, count(*) as n')->groupBy('student_status')->pluck('n', 'student_status'),
        ]);
    }

    public function show(Registration $registration): View
    {
        abort_unless($registration->student_status, 404);
        $registration->load('user', 'category', 'studentReviewer');

        return view('finance.students.show', [
            'registration' => $registration,
            'proofIsImage' => $registration->student_proof_path && in_array(strtolower(pathinfo($registration->student_proof_path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png'], true),
        ]);
    }

    /** Proofs are private, like payment proofs. */
    public function proof(Registration $registration): StreamedResponse
    {
        $disk = Storage::disk(config('payments.proof_disk'));
        abort_unless($registration->student_proof_path && $disk->exists($registration->student_proof_path), 404);

        return $disk->response($registration->student_proof_path, 'student-proof-'.$registration->reference.'.'.pathinfo($registration->student_proof_path, PATHINFO_EXTENSION));
    }

    public function approve(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        try {
            $service->reviewStudentProof($registration, $request->user(), true);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['student_rejection_reason' => $e->getMessage()]);
        }

        return $this->next($registration, 'Student rate approved. '.$registration->user->name.' can now pay.');
    }

    public function reject(Request $request, Registration $registration, RegistrationService $service): RedirectResponse
    {
        $data = $request->validate(['student_rejection_reason' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $service->reviewStudentProof($registration, $request->user(), false, $data['student_rejection_reason']);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['student_rejection_reason' => $e->getMessage()]);
        }

        return $this->next($registration, 'Student proof not accepted. '.$registration->user->name.' has been told why.');
    }

    /** After a decision, go straight to the next proof waiting, if any. */
    private function next(Registration $registration, string $message): RedirectResponse
    {
        $next = Registration::where('edition_id', $registration->edition_id)
            ->where('student_status', StudentProofStatus::Pending)->whereKeyNot($registration->id)
            ->oldest('updated_at')->first();

        return $next
            ? redirect()->route('finance.students.show', $next)->with('status', $message)
            : redirect()->route('finance.students.index')->with('status', $message);
    }
}
