<?php

namespace App\Services;

use App\Models\Edition;
use App\Models\FeedbackResponse;
use App\Models\Registration;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * The feedback survey after the summit: open from its last day for a month,
 * once per person who attended. Results are shown without names.
 */
class FeedbackService
{
    public function isOpen(Edition $edition): bool
    {
        $end = $edition->end_date;

        return $end && today()->gte($end) && today()->lte($end->copy()->addDays(config('feedback.open_days')));
    }

    /** Attended, the survey is open, and not answered yet. */
    public function canRespond(?Registration $registration): bool
    {
        return $registration
            && $this->isOpen($registration->edition)
            && $registration->attended()
            && ! FeedbackResponse::where('registration_id', $registration->id)->exists();
    }

    /**
     * @param  array{ratings: array<string, int>, recommend: int, next_year: string, comments?: array<string, ?string>}  $answers
     */
    public function submit(Registration $registration, array $answers): FeedbackResponse
    {
        if (! $this->canRespond($registration)) {
            throw new InvalidArgumentException('The survey is not open to you, or you have already answered it. Thank you.');
        }

        return FeedbackResponse::create([
            'edition_id' => $registration->edition_id,
            'registration_id' => $registration->id,
            'ratings' => collect(config('feedback.ratings'))->keys()->mapWithKeys(fn ($key) => [$key => (int) $answers['ratings'][$key]])->all(),
            'recommend' => (int) $answers['recommend'],
            'next_year' => $answers['next_year'],
            'comments' => collect($answers['comments'] ?? [])->only(array_keys(config('feedback.comments')))
                ->map(fn ($text) => filled($text) ? trim($text) : null)->filter()->all() ?: null,
        ]);
    }

    /**
     * The survey's results for the organisers.
     *
     * @return array{responses: int, attended: int, ratings: Collection, recommend: ?float, promoters: int, detractors: int, nps: ?int, nextYear: Collection}
     */
    public function results(Edition $edition): array
    {
        $responses = FeedbackResponse::where('edition_id', $edition->id)->get();
        $count = $responses->count();
        $promoters = $responses->where('recommend', '>=', 9)->count();
        $detractors = $responses->where('recommend', '<=', 6)->count();

        return [
            'responses' => $count,
            'attended' => Registration::where('edition_id', $edition->id)->get()->filter->attended()->count(),
            'ratings' => collect(config('feedback.ratings'))->map(fn ($label, $key) => [
                'label' => $label,
                'average' => $count ? round($responses->avg(fn ($r) => $r->ratings[$key] ?? 0), 1) : null,
                'spread' => collect(range(1, 5))->mapWithKeys(fn ($n) => [$n => $responses->filter(fn ($r) => ($r->ratings[$key] ?? null) === $n)->count()]),
            ]),
            'recommend' => $count ? round($responses->avg('recommend'), 1) : null,
            'promoters' => $promoters,
            'detractors' => $detractors,
            // Net promoter score: the share who would recommend it (9–10), less the share who would not (0–6).
            'nps' => $count ? (int) round(($promoters - $detractors) / $count * 100) : null,
            'nextYear' => collect(config('feedback.next_year'))->map(fn ($label, $key) => $responses->where('next_year', $key)->count()),
        ];
    }
}
