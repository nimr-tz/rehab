<?php

namespace App\Http\Controllers;

use App\Models\ConferenceFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FeedbackController extends Controller
{
    use AuthorizesRequests;

    /**
     * Development mode flag.
     * When false (default): enforces conference date rules.
     */
    private const DEV_MODE = false;

    public function __construct()
    {
        // No middleware needed here, we handle it in routes
    }

    // Show feedback form
    public function create()
    {
        // Check if feedback is available yet
        if (! $this->isFeedbackAvailable()) {
            $endDate = Carbon::parse(config('conference.end_date'))->format('F j, Y');

            return view('feedback.not-available', compact('endDate'));
        }

        $badge = strtoupper(trim((string) request('badge')));

        // Logged-in user already has account feedback — if they arrived from the
        // badge claim path, skip the form and send them straight to the download.
        if (Auth::check() && ConferenceFeedback::where('user_id', Auth::id())->exists()) {
            if ($badge !== '') {
                return redirect()->route('certificate.claim', ['code' => $badge, 'autodownload' => 1])
                    ->with('success', 'Feedback already received — downloading your certificate.');
            }

            return redirect()->route('feedback.thankyou');
        }

        // Badge-claim flow: already submitted for this badge → straight back to the download
        if ($badge !== '' && ConferenceFeedback::where('badge_code', $badge)->exists()) {
            return redirect()->route('certificate.claim', ['code' => $badge, 'autodownload' => 1])
                ->with('success', 'Feedback already received — downloading your certificate.');
        }

        return view('feedback.create');
    }

    // Store feedback
    public function store(Request $request)
    {
        // Check if feedback is available yet
        if (! $this->isFeedbackAvailable()) {
            return redirect()->route('home')->with('error', 'Feedback is not yet available. Please check back on the last day of the conference.');
        }

        // Prevent duplicate submissions for logged-in users
        if (Auth::check() && ConferenceFeedback::where('user_id', Auth::id())->exists()) {
            $badge = strtoupper(trim((string) $request->input('badge_code', '')));
            if ($badge !== '') {
                return redirect()->route('certificate.claim', ['code' => $badge, 'autodownload' => 1])
                    ->with('success', 'Feedback already received — downloading your certificate.');
            }

            return redirect()->route('feedback.thankyou')->with('error', 'You have already submitted feedback.');
        }

        $validated = $request->validate([
            'badge_code' => 'nullable|string|max:64',
            'guest_name' => 'nullable|string|max:255',
            'guest_email' => 'nullable|email|max:255',
            'participant_type' => 'nullable|string',
            'overall_rating' => 'required|integer|min:1|max:5',
            'overall_comments' => 'nullable|string',
            'venue_rating' => 'nullable|integer|min:1|max:5',
            'venue_comments' => 'nullable|string',
            'organization_rating' => 'nullable|integer|min:1|max:5',
            'organization_comments' => 'nullable|string',
            'content_quality_rating' => 'nullable|integer|min:1|max:5',
            'content_quality_comments' => 'nullable|string',
            'networking_rating' => 'nullable|integer|min:1|max:5',
            'networking_comments' => 'nullable|string',
            'registration_process_rating' => 'nullable|integer|min:1|max:5',
            'registration_process_comments' => 'nullable|string',
            'abstract_review_rating' => 'nullable|integer|min:1|max:5',
            'abstract_review_comments' => 'nullable|string',
            'food_quality_rating' => 'nullable|integer|min:1|max:5',
            'service_quality_rating' => 'nullable|integer|min:1|max:5',
            'hospitality_comments' => 'nullable|string',
            'session_variety_rating' => 'nullable|integer|min:1|max:5',
            'session_timing_rating' => 'nullable|integer|min:1|max:5',
            'speaker_quality_rating' => 'nullable|integer|min:1|max:5',
            'session_organization_rating' => 'nullable|integer|min:1|max:5',
            'learning_value_rating' => 'nullable|integer|min:1|max:5',
            'poster_session_rating' => 'nullable|integer|min:1|max:5',
            'communication_rating' => 'nullable|integer|min:1|max:5',
            'platform_usability' => 'nullable|integer|min:1|max:5',
            'platform_feedback' => 'nullable|string',
            'submission_ease_rating' => 'nullable|integer|min:1|max:5',
            'review_time_rating' => 'nullable|integer|min:1|max:5',
            'reviewer_comments_quality' => 'nullable|integer|min:1|max:5',
            'review_fair' => 'nullable|string|in:yes,no,unsure',
            'review_process_comments' => 'nullable|string',
            'field_discipline' => 'nullable|string|max:255',
            'attendance_history' => 'nullable|string|in:first_time,returning',
            'how_heard' => 'nullable|string|max:255',
            'met_expectations' => 'nullable|string|in:exceeded,fully_met,partially_met,not_met',
            'scientific_discussion_rating' => 'nullable|integer|min:1|max:5',
            'emerging_areas_covered' => 'nullable|string|in:yes,partially,no',
            'session_balance_rating' => 'nullable|integer|min:1|max:5',
            'session_pacing_rating' => 'nullable|integer|min:1|max:5',
            'accommodation_transport_rating' => 'nullable|integer|min:1|max:5',
            'materials_rating' => 'nullable|integer|min:1|max:5',
            'presenter_support_rating' => 'nullable|integer|min:1|max:5',
            'av_quality_rating' => 'nullable|integer|min:1|max:5',
            'audience_engagement_rating' => 'nullable|integer|min:1|max:5',
            'roles_held' => 'nullable|array',
            'roles_held.*' => 'string|in:presented,chaired',
            'what_worked_well' => 'nullable|string',
            'what_needs_improvement' => 'nullable|string',
            'suggestions_for_next_year' => 'nullable|string',
            'topics_want_to_see' => 'nullable|string',
            'likely_to_attend_next' => 'nullable|string|in:definitely,probably,maybe,no',
            'likely_to_recommend' => 'nullable|string|in:definitely,probably,maybe,no',
            'sessions_attended' => 'nullable|array',
            'most_valuable_session' => 'nullable|string',
            'least_valuable_session' => 'nullable|string',
            'age_group' => 'nullable|string|in:18-25,26-35,36-45,46-55,56-65,65+',
            'experience_level' => 'nullable|string|in:student,early_career,mid_career,senior,retired',
            'additional_comments' => 'nullable|string',
            'conference_value' => 'nullable|string',
            'would_present_again' => 'nullable|boolean',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['would_present_again'] = $request->has('would_present_again');

        // Badge-claim flow: key the feedback to the badge so the certificate
        // claim can verify it. If the badge belongs to a registered delegate,
        // credit their account too so both paths see the feedback.
        $badgeCode = strtoupper(trim((string) ($validated['badge_code'] ?? '')));
        if ($badgeCode !== '') {
            $validated['badge_code'] = $badgeCode;

            if (ConferenceFeedback::where('badge_code', $badgeCode)->exists()) {
                return redirect()->route('certificate.claim', ['code' => $badgeCode])
                    ->with('success', 'Feedback already received — download your certificate below.');
            }

            if (! $validated['user_id']) {
                $validated['user_id'] = \App\Models\User::where('qr_code_token', $badgeCode)->value('id');
            }
        } else {
            unset($validated['badge_code']);
        }

        // Only auto-assign participant type if the user didn't pick one
        if (empty($validated['participant_type'])) {
            $validated['participant_type'] = Auth::check() && Auth::user()->isPresenter() ? 'presenter' : 'attendee';
        }

        // For logged-in users, prefer their profile name/email over guest fields
        if (Auth::check()) {
            unset($validated['guest_name'], $validated['guest_email']);
        }

        ConferenceFeedback::create($validated);

        if ($badgeCode !== '') {
            return redirect()->route('certificate.claim', ['code' => $badgeCode, 'autodownload' => 1])
                ->with('success', 'Thank you for your feedback! Your certificate download will start automatically.');
        }

        return redirect()->route('feedback.thankyou')->with('success', 'Thank you for your feedback! Your insights will help us plan the next edition.');
    }

    // Thank you page
    public function thankyou()
    {
        return view('feedback.thankyou');
    }

    // Admin: View all feedback
    public function index()
    {
        $this->authorize('viewAny', ConferenceFeedback::class);

        $feedbacks = ConferenceFeedback::with('user')
            ->latest()
            ->paginate(15);

        $avg = ConferenceFeedback::getAverageRatings();
        $averageSatisfaction = $avg['overall'] ?? 0;
        $averageContent = $avg['content_quality'] ?? 0;
        $averageTechnical = $avg['venue'] ?? 0;
        $averageOrganization = $avg['organization'] ?? 0;

        $satisfactionDistribution = ConferenceFeedback::getSatisfactionDistribution();
        $futureParticipationStats = ConferenceFeedback::getFutureParticipationStats();
        $recommendationStats = ConferenceFeedback::getRecommendationStats();

        $totalFeedback = ConferenceFeedback::count();

        return view('admin.feedback.index', compact(
            'feedbacks',
            'averageSatisfaction',
            'averageContent',
            'averageTechnical',
            'averageOrganization',
            'satisfactionDistribution',
            'futureParticipationStats',
            'recommendationStats',
            'totalFeedback'
        ));
    }

    // Admin: PDF committee report
    public function report()
    {
        $this->authorize('viewAny', ConferenceFeedback::class);

        $total = ConferenceFeedback::count();
        if ($total === 0) {
            return back()->with('error', 'No feedback submissions yet.');
        }

        $avg = ConferenceFeedback::getAverageRatings();
        $satisfactionDist = ConferenceFeedback::getSatisfactionDistribution();
        $futureStats = ConferenceFeedback::getFutureParticipationStats();
        $recommendStats = ConferenceFeedback::getRecommendationStats();

        // NPS: promoters (5) − detractors (1-2) / total × 100
        $promoters = $satisfactionDist[5] ?? 0;
        $detractors = ($satisfactionDist[1] ?? 0) + ($satisfactionDist[2] ?? 0);
        $nps = $total > 0 ? round((($promoters - $detractors) / $total) * 100) : 0;

        // Met expectations distribution
        $metExpectations = ConferenceFeedback::select('met_expectations', DB::raw('count(*) as count'))
            ->whereNotNull('met_expectations')
            ->groupBy('met_expectations')
            ->pluck('count', 'met_expectations')
            ->toArray();

        // Detailed category averages
        $categoryAvgs = [
            'Scientific Programme' => [
                'Speaker / Keynote Quality'     => ConferenceFeedback::avg('speaker_quality_rating'),
                'Content Quality & Diversity'   => ConferenceFeedback::avg('content_quality_rating'),
                'Scientific Discussion'         => ConferenceFeedback::avg('scientific_discussion_rating'),
                'Learning Value'                => ConferenceFeedback::avg('learning_value_rating'),
                'Poster Sessions'               => ConferenceFeedback::avg('poster_session_rating'),
                'Session Variety'               => ConferenceFeedback::avg('session_variety_rating'),
            ],
            'Programme Structure' => [
                'Session Balance'               => ConferenceFeedback::avg('session_balance_rating'),
                'Session Pacing'                => ConferenceFeedback::avg('session_pacing_rating'),
                'Session Timing / Schedule'     => ConferenceFeedback::avg('session_timing_rating'),
                'Moderation / Chairing'         => ConferenceFeedback::avg('session_organization_rating'),
            ],
            'Logistics & Venue' => [
                'Venue'                         => ConferenceFeedback::avg('venue_rating'),
                'Overall Organisation'          => ConferenceFeedback::avg('organization_rating'),
                'Registration & Badge Process'  => ConferenceFeedback::avg('registration_process_rating'),
                'Accommodation & Transport'     => ConferenceFeedback::avg('accommodation_transport_rating'),
                'Food Quality'                  => ConferenceFeedback::avg('food_quality_rating'),
                'Staff & Service Quality'       => ConferenceFeedback::avg('service_quality_rating'),
            ],
            'Communication & Materials' => [
                'Pre-Conference Communication'  => ConferenceFeedback::avg('communication_rating'),
                'Website / App Usability'       => ConferenceFeedback::avg('platform_usability'),
                'Programme Materials'           => ConferenceFeedback::avg('materials_rating'),
            ],
            'Networking' => [
                'Networking Opportunities'      => ConferenceFeedback::avg('networking_rating'),
            ],
            'Presenter Experience' => [
                'Presenter Support'             => ConferenceFeedback::avg('presenter_support_rating'),
                'AV / Technical Quality'        => ConferenceFeedback::avg('av_quality_rating'),
                'Audience Engagement'           => ConferenceFeedback::avg('audience_engagement_rating'),
            ],
            'Abstract Review Process' => [
                'Submission Ease'               => ConferenceFeedback::avg('submission_ease_rating'),
                'Review Turnaround Speed'       => ConferenceFeedback::avg('review_time_rating'),
                'Quality of Reviewer Feedback'  => ConferenceFeedback::avg('reviewer_comments_quality'),
                'Overall Review Quality'        => ConferenceFeedback::avg('abstract_review_rating'),
            ],
        ];

        // Emerging areas covered
        $emergingAreas = ConferenceFeedback::select('emerging_areas_covered', DB::raw('count(*) as count'))
            ->whereNotNull('emerging_areas_covered')
            ->groupBy('emerging_areas_covered')
            ->pluck('count', 'emerging_areas_covered')
            ->toArray();

        // Review fairness
        $reviewFair = ConferenceFeedback::select('review_fair', DB::raw('count(*) as count'))
            ->whereNotNull('review_fair')
            ->groupBy('review_fair')
            ->pluck('count', 'review_fair')
            ->toArray();

        // Demographics
        $participantTypes = ConferenceFeedback::select('participant_type', DB::raw('count(*) as count'))
            ->whereNotNull('participant_type')
            ->groupBy('participant_type')
            ->orderByDesc('count')
            ->pluck('count', 'participant_type')
            ->toArray();

        $ageGroups = ConferenceFeedback::select('age_group', DB::raw('count(*) as count'))
            ->whereNotNull('age_group')
            ->groupBy('age_group')
            ->orderBy('age_group')
            ->pluck('count', 'age_group')
            ->toArray();

        $experienceLevels = ConferenceFeedback::select('experience_level', DB::raw('count(*) as count'))
            ->whereNotNull('experience_level')
            ->groupBy('experience_level')
            ->pluck('count', 'experience_level')
            ->toArray();

        $attendanceHistory = ConferenceFeedback::select('attendance_history', DB::raw('count(*) as count'))
            ->whereNotNull('attendance_history')
            ->groupBy('attendance_history')
            ->pluck('count', 'attendance_history')
            ->toArray();

        $wouldPresentAgain = ConferenceFeedback::whereNotNull('would_present_again')->count();
        $wouldPresentYes   = ConferenceFeedback::where('would_present_again', true)->count();

        // Qualitative responses
        $qualitative = ConferenceFeedback::with('user')
            ->where(function ($q) {
                $q->whereNotNull('what_worked_well')
                  ->orWhereNotNull('what_needs_improvement')
                  ->orWhereNotNull('suggestions_for_next_year')
                  ->orWhereNotNull('topics_want_to_see');
            })
            ->latest()
            ->get(['what_worked_well', 'what_needs_improvement', 'suggestions_for_next_year',
                   'topics_want_to_see', 'participant_type', 'created_at', 'user_id',
                   'guest_name', 'overall_rating']);

        $generatedAt = now()->format('F j, Y \a\t H:i T');
        $conferenceName = config('conference.name');

        $pdf = Pdf::loadView('admin.feedback.report', compact(
            'total', 'avg', 'satisfactionDist', 'futureStats', 'recommendStats',
            'nps', 'metExpectations', 'categoryAvgs', 'emergingAreas', 'reviewFair',
            'participantTypes', 'ageGroups', 'experienceLevels', 'attendanceHistory',
            'wouldPresentAgain', 'wouldPresentYes', 'qualitative',
            'generatedAt', 'conferenceName'
        ))->setPaper('a4', 'portrait');

        return $pdf->download(config('conference.file_prefix').'-Feedback-Report-'.date('Y-m-d').'.pdf');
    }

    // Admin: Export feedback data
    public function export()
    {
        $this->authorize('viewAny', ConferenceFeedback::class);

        $feedback = ConferenceFeedback::with('user')->get();

        $filename = 'conference_feedback_'.date('Y-m-d').'.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($feedback) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID', 'User Name', 'Email', 'Participant Type', 'Roles Held', 'Field/Institution',
                'First Time or Returning', 'How Heard', 'Parts Attended',
                'Overall Rating', 'Met Expectations', 'Likely to Attend Next', 'Likely to Recommend',
                'Keynote Quality', 'Research Quality/Diversity', 'Topic Depth (3 Days)',
                'Scientific Discussion', 'Emerging Areas Covered', 'Poster Sessions', 'Learning Value',
                'Session Balance', 'Session Pacing', 'Moderation/Chairing', 'Networking',
                'Venue', 'Registration/Badge', 'Schedule/Time Mgmt', 'Organization',
                'Signage/Staff', 'Accommodation/Transport', 'Food Quality',
                'Website/App Usability', 'Pre-Conference Comms', 'Programme Materials',
                'Hospitality Comments', 'Website/App Feedback',
                'Speaker Support', 'AV/Technical Quality', 'Audience Engagement',
                'Submission Ease', 'Review Speed', 'Reviewer Feedback Quality',
                'Review Quality Overall', 'Review Fair', 'Review Process Comments',
                'What Worked Well', 'What Needs Improvement', 'Suggestions for Next Year',
                'Topics Want to See', 'Conference Value', 'Would Present Again',
                'Age Group', 'Experience Level',
                'Most Valuable Session', 'Least Valuable Session', 'Additional Comments',
                'Submitted At',
            ]);

            $expectationsMap = [
                'exceeded'     => 'Exceeded',
                'fully_met'    => 'Fully Met',
                'partially_met'=> 'Partially Met',
                'not_met'      => 'Not Met',
            ];
            $attendMap = [
                'definitely' => 'Definitely',
                'probably'   => 'Probably',
                'maybe'      => 'Maybe',
                'no'         => 'No',
            ];
            $emergingMap = [
                'yes'       => 'Yes',
                'partially' => 'Partially',
                'no'        => 'No',
            ];
            $reviewFairMap = [
                'yes'    => 'Yes',
                'no'     => 'No',
                'unsure' => 'Unsure',
            ];
            $historyMap = [
                'first_time' => 'First Time',
                'returning'  => 'Returning',
            ];
            $experienceMap = [
                'student'      => 'Student',
                'early_career' => 'Early Career',
                'mid_career'   => 'Mid Career',
                'senior'       => 'Senior',
                'retired'      => 'Retired',
            ];
            $participantMap = [
                'attendee'   => 'Attendee',
                'presenter'  => 'Presenter',
                'reviewer'   => 'Reviewer',
                'organizer'  => 'Organizer',
                'sponsor'    => 'Sponsor',
                'media'      => 'Media',
            ];
            $rolesMap = [
                'presented' => 'Presented',
                'chaired'   => 'Chaired',
            ];

            foreach ($feedback as $r) {
                $roles = is_array($r->roles_held)
                    ? implode('; ', array_map(fn ($v) => $rolesMap[$v] ?? ucfirst($v), $r->roles_held))
                    : $r->roles_held;

                fputcsv($file, [
                    $r->id,
                    $r->user ? ($r->user->first_name.' '.$r->user->last_name) : ($r->guest_name ?: 'Anonymous'),
                    $r->user ? $r->user->email : ($r->guest_email ?: 'N/A'),
                    $this->enumLabel($r->participant_type, $participantMap),
                    $roles,
                    $r->field_discipline,
                    $this->enumLabel($r->attendance_history, $historyMap),
                    $r->how_heard,
                    is_array($r->sessions_attended) ? implode('; ', $r->sessions_attended) : $r->sessions_attended,
                    $this->ratingLabel($r->overall_rating),
                    $this->enumLabel($r->met_expectations, $expectationsMap),
                    $this->enumLabel($r->likely_to_attend_next, $attendMap),
                    $this->enumLabel($r->likely_to_recommend, $attendMap),
                    $this->ratingLabel($r->speaker_quality_rating),
                    $this->ratingLabel($r->content_quality_rating),
                    $this->ratingLabel($r->session_variety_rating),
                    $this->ratingLabel($r->scientific_discussion_rating),
                    $this->enumLabel($r->emerging_areas_covered, $emergingMap),
                    $this->ratingLabel($r->poster_session_rating),
                    $this->ratingLabel($r->learning_value_rating),
                    $this->ratingLabel($r->session_balance_rating),
                    $this->ratingLabel($r->session_pacing_rating),
                    $this->ratingLabel($r->session_organization_rating),
                    $this->ratingLabel($r->networking_rating),
                    $this->ratingLabel($r->venue_rating),
                    $this->ratingLabel($r->registration_process_rating),
                    $this->ratingLabel($r->session_timing_rating),
                    $this->ratingLabel($r->organization_rating),
                    $this->ratingLabel($r->service_quality_rating),
                    $this->ratingLabel($r->accommodation_transport_rating),
                    $this->ratingLabel($r->food_quality_rating),
                    $this->ratingLabel($r->platform_usability),
                    $this->ratingLabel($r->communication_rating),
                    $this->ratingLabel($r->materials_rating),
                    $r->hospitality_comments,
                    $r->platform_feedback,
                    $this->ratingLabel($r->presenter_support_rating),
                    $this->ratingLabel($r->av_quality_rating),
                    $this->ratingLabel($r->audience_engagement_rating),
                    $this->ratingLabel($r->submission_ease_rating),
                    $this->ratingLabel($r->review_time_rating),
                    $this->ratingLabel($r->reviewer_comments_quality),
                    $this->ratingLabel($r->abstract_review_rating),
                    $this->enumLabel($r->review_fair, $reviewFairMap),
                    $r->review_process_comments,
                    $r->what_worked_well,
                    $r->what_needs_improvement,
                    $r->suggestions_for_next_year,
                    $r->topics_want_to_see,
                    $r->conference_value,
                    is_null($r->would_present_again) ? '' : ($r->would_present_again ? 'Yes' : 'No'),
                    $r->age_group,
                    $this->enumLabel($r->experience_level, $experienceMap),
                    $r->most_valuable_session,
                    $r->least_valuable_session,
                    $r->additional_comments,
                    $r->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function ratingLabel(?int $rating): string
    {
        return match ($rating) {
            5 => 'Excellent',
            4 => 'Good',
            3 => 'Average',
            2 => 'Poor',
            1 => 'Very Poor',
            default => '',
        };
    }

    private function enumLabel(?string $value, array $map): string
    {
        return $map[$value] ?? (string) $value;
    }

    /**
     * Check if the conference has reached the stage where feedback can be collected.
     * Typically this is the last day of the conference or later.
     */
    private function isFeedbackAvailable(): bool
    {
        if (self::DEV_MODE) {
            return true;
        }

        $endDate = Carbon::parse(config('conference.end_date', '2026-06-11'));

        // Available starting from the beginning of the last day
        return now()->isAfter($endDate->startOfDay());
    }
}
