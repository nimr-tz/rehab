<?php

namespace App\Http\Controllers;

use App\Models\FeedbackResponse;
use App\Services\FeedbackService;
use App\Support\Csv;
use App\Support\Summit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** The feedback survey: participants answer it; admins read the results, without names. */
class FeedbackController extends Controller
{
    public function __construct(private Summit $summit, private FeedbackService $feedback) {}

    public function create(Request $request): View
    {
        $registration = $request->user()->registrationFor($this->summit->edition());

        return view('feedback.create', [
            'canRespond' => $this->feedback->canRespond($registration),
            'answered' => $registration && FeedbackResponse::where('registration_id', $registration->id)->exists(),
            'open' => $this->summit->edition() && $this->feedback->isOpen($this->summit->edition()),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $registration = $request->user()->registrationFor($this->summit->edition());
        abort_unless($registration, 404);

        $ratings = collect(config('feedback.ratings'))->keys()->mapWithKeys(fn ($key) => ["ratings.{$key}" => ['required', 'integer', 'between:1,5']]);
        $comments = collect(config('feedback.comments'))->keys()->mapWithKeys(fn ($key) => ["comments.{$key}" => ['nullable', 'string', 'max:2000']]);

        $data = $request->validate($ratings->all() + $comments->all() + [
            'recommend' => ['required', 'integer', 'between:0,10'],
            'next_year' => ['required', Rule::in(array_keys(config('feedback.next_year')))],
        ], [
            'ratings.*.required' => 'Please rate this.',
            'recommend.required' => 'Please choose a number from 0 to 10.',
            'next_year.required' => 'Please choose an answer.',
        ]);

        try {
            $this->feedback->submit($registration, $data);
        } catch (InvalidArgumentException $e) {
            return redirect()->route('feedback.create')->withErrors(['recommend' => $e->getMessage()]);
        }

        return redirect()->route('feedback.create')->with('status', 'Thank you for your feedback.');
    }

    public function results(): View
    {
        $edition = $this->summit->edition();
        abort_unless($edition, 404);

        return view('admin.feedback', $this->feedback->results($edition) + [
            'comments' => FeedbackResponse::where('edition_id', $edition->id)->whereNotNull('comments')->latest()->paginate(20),
            'open' => $this->feedback->isOpen($edition),
            'closes' => $edition->end_date?->copy()->addDays(config('feedback.open_days')),
        ]);
    }

    public function export(): StreamedResponse
    {
        $responses = FeedbackResponse::where('edition_id', $this->summit->edition()?->id)->oldest()->get();
        $ratings = config('feedback.ratings');
        $comments = config('feedback.comments');

        return Csv::download('feedback-'.now()->format('Y-m-d').'.csv',
            ['Answered', ...array_values($ratings), 'Would recommend (0-10)', 'Next year', ...array_values($comments)],
            $responses->map(fn (FeedbackResponse $r) => [
                $r->created_at->toDateString(),
                ...array_map(fn ($key) => $r->ratings[$key] ?? null, array_keys($ratings)),
                $r->recommend,
                config('feedback.next_year.'.$r->next_year),
                ...array_map(fn ($key) => $r->comments[$key] ?? null, array_keys($comments)),
            ]));
    }
}
