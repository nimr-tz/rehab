<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConferenceFeedback extends Model
{
    use HasFactory;

    protected $table = 'conference_feedback';

    protected $fillable = [
        'user_id',
        'badge_code',
        'guest_name',
        'guest_email',
        'participant_type',
        'overall_rating',
        'overall_comments',
        'venue_rating',
        'venue_comments',
        'organization_rating',
        'organization_comments',
        'content_quality_rating',
        'content_quality_comments',
        'networking_rating',
        'networking_comments',
        'registration_process_rating',
        'registration_process_comments',
        'abstract_review_rating',
        'abstract_review_comments',
        'food_quality_rating',
        'service_quality_rating',
        'hospitality_comments',
        'session_variety_rating',
        'session_timing_rating',
        'speaker_quality_rating',
        'session_organization_rating',
        'learning_value_rating',
        'poster_session_rating',
        'communication_rating',
        'platform_usability',
        'platform_feedback',
        'submission_ease_rating',
        'review_time_rating',
        'reviewer_comments_quality',
        'review_fair',
        'review_process_comments',
        'field_discipline',
        'attendance_history',
        'how_heard',
        'met_expectations',
        'scientific_discussion_rating',
        'emerging_areas_covered',
        'session_balance_rating',
        'session_pacing_rating',
        'accommodation_transport_rating',
        'materials_rating',
        'presenter_support_rating',
        'av_quality_rating',
        'audience_engagement_rating',
        'what_worked_well',
        'what_needs_improvement',
        'suggestions_for_next_year',
        'topics_want_to_see',
        'likely_to_attend_next',
        'likely_to_recommend',
        'sessions_attended',
        'most_valuable_session',
        'least_valuable_session',
        'age_group',
        'experience_level',
        'additional_comments',
        'conference_value',
        'would_present_again',
        'roles_held',
    ];

    protected $casts = [
        'sessions_attended' => 'array',
        'roles_held' => 'array',
        'would_present_again' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get average ratings for dashboard
    public static function getAverageRatings()
    {
        return [
            'overall' => round(self::avg('overall_rating'), 1),
            'venue' => round(self::avg('venue_rating'), 1),
            'organization' => round(self::avg('organization_rating'), 1),
            'content_quality' => round(self::avg('content_quality_rating'), 1),
            'networking' => round(self::avg('networking_rating'), 1),
            'registration_process' => round(self::avg('registration_process_rating'), 1),
            'abstract_review' => round(self::avg('abstract_review_rating'), 1),
        ];
    }

    // Get satisfaction distribution with numeric keys
    public static function getSatisfactionDistribution()
    {
        return [
            5 => self::where('overall_rating', 5)->count(),
            4 => self::where('overall_rating', 4)->count(),
            3 => self::where('overall_rating', 3)->count(),
            2 => self::where('overall_rating', 2)->count(),
            1 => self::where('overall_rating', 1)->count(),
        ];
    }

    // Get future participation stats
    public static function getFutureParticipationStats()
    {
        $stats = [
            'definitely' => self::where('likely_to_attend_next', 'definitely')->count(),
            'probably' => self::where('likely_to_attend_next', 'probably')->count(),
            'maybe' => self::where('likely_to_attend_next', 'maybe')->count(),
            'no' => self::where('likely_to_attend_next', 'no')->count(),
        ];

        $stats['yes'] = $stats['definitely'] + $stats['probably'];

        return $stats;
    }

    // Get recommendation stats
    public static function getRecommendationStats()
    {
        $stats = [
            'definitely' => self::where('likely_to_recommend', 'definitely')->count(),
            'probably' => self::where('likely_to_recommend', 'probably')->count(),
            'maybe' => self::where('likely_to_recommend', 'maybe')->count(),
            'no' => self::where('likely_to_recommend', 'no')->count(),
        ];

        $stats['yes'] = $stats['definitely'] + $stats['probably'];

        return $stats;
    }
}
