<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AbstractSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnalyticsController extends Controller
{
    public function dashboard()
    {
        // BOSS-LEVEL STATISTICS
        
        // 1. REGISTRATION INSIGHTS - REAL DATA
        $registrationStats = [
            'total_registered' => User::where('email_verified_at', '!=', null)->count(),
            'total_paid' => 0, // Will be updated when payment module is ready
            'pending_payment' => User::where('email_verified_at', '!=', null)->count(), // All for now
            'total_submitted_abstracts' => User::whereHas('abstractSubmissions')->count(),
            'registered_no_submission' => User::where('email_verified_at', '!=', null)
                ->whereDoesntHave('abstractSubmissions')->count(),
        ];

        // 2. ABSTRACT SUBMISSION INSIGHTS - REAL DATA
        $abstractStats = [
            'total_abstracts' => AbstractSubmission::count(),
            'pending_review' => AbstractSubmission::where('status', 'submitted')->count(),
            'under_review' => AbstractSubmission::where('status', 'under_review')->count(),
            'accepted' => AbstractSubmission::where('status', 'accepted')->count(),
            'rejected' => AbstractSubmission::where('status', 'rejected')->count(),
            'revision' => AbstractSubmission::where('status', 'revision')->count(),
            'draft' => AbstractSubmission::where('status', 'draft')->count(),
            'accepted_oral' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['oral'])
                ->count(),
            'accepted_poster' => AbstractSubmission::where('status', 'accepted')
                ->where(function ($query) {
                    $query->whereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['poster'])
                        ->orWhereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['audio_poster']);
                })
                ->count(),
            'accepted_audio_poster' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['audio_poster'])
                ->count(),
        ];

        // 3. GEOGRAPHIC DISTRIBUTION - REAL DATA using 'country' column
        $countryDistribution = User::select('country', DB::raw('COUNT(*) as count'))
            ->where('email_verified_at', '!=', null)
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->take(10)
            ->get();

        // If no country data, show message
        if ($countryDistribution->isEmpty()) {
            $countryDistribution = collect([
                (object)['country' => 'No country data yet', 'count' => 0]
            ]);
        }

        // 4. AFFILIATION DISTRIBUTION - REAL DATA using 'affiliation' column (not institution)
        $affiliationDistribution = $this->getNormalizedAffiliationDistribution();

        // If no affiliation data, show message
        if ($affiliationDistribution->isEmpty()) {
            $affiliationDistribution = collect([
                (object)['affiliation' => 'No affiliation data yet', 'count' => 0]
            ]);
        }

        // 5. STUDENT vs PROFESSIONAL BREAKDOWN - REAL DATA using 'student_status'
        $participantTypeStats = [
            'students' => User::where('email_verified_at', '!=', null)
                ->where('student_status', 'yes')->count(),
            'professionals' => User::where('email_verified_at', '!=', null)
                ->where('student_status', 'no')->count(),
            'unspecified' => User::where('email_verified_at', '!=', null)
                ->whereNull('student_status')->count(),
        ];

        // 6. REGISTRATION TRENDS (Last 30 days) - REAL DATA
        $registrationTrends = User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays(30))
        ->where('email_verified_at', '!=', null)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // 7. SUBMISSION vs REGISTRATION TRENDS - REAL DATA
        $submissionTrends = AbstractSubmission::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
        ->where('created_at', '>=', now()->subDays(30))
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // 8. TOP PERFORMING COUNTRIES (By abstracts) - REAL DATA using 'country'
        $topCountriesByAbstracts = User::select('country', DB::raw('COUNT(abstract_submissions.id) as abstract_count'))
            ->join('abstract_submissions', 'users.id', '=', 'abstract_submissions.user_id')
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('abstract_count')
            ->take(5)
            ->get();

        // If no data, show empty state
        if ($topCountriesByAbstracts->isEmpty()) {
            $topCountriesByAbstracts = collect([
                (object)['country' => 'No submissions yet', 'abstract_count' => 0]
            ]);
        }

        // 9. TOP PERFORMING AFFILIATIONS (By abstracts) - REAL DATA using 'affiliation'
        $topAffiliationsByAbstracts = $this->getNormalizedAffiliationsByAbstracts();

        // If no data, show empty state
        if ($topAffiliationsByAbstracts->isEmpty()) {
            $topAffiliationsByAbstracts = collect([
                (object)['affiliation' => 'No submissions yet', 'abstract_count' => 0]
            ]);
        }

        // 10. REVIEWER WORKLOAD - REAL DATA
        $reviewerStats = [
            'total_reviewers' => User::whereHas('roles', function($q) {
                $q->where('name', 'reviewer');
            })->count(),
            'active_reviewers' => User::whereHas('roles', function($q) {
                $q->where('name', 'reviewer');
            })->where(function($q) {
                $q->whereHas('reviewedAbstracts1')->orWhereHas('reviewedAbstracts2');
            })->count(),
        ];

        // 11. CONVERSION RATES - REAL DATA
        $conversionRates = [
            'registration_to_submission' => $registrationStats['total_registered'] > 0 
                ? round(($registrationStats['total_submitted_abstracts'] / $registrationStats['total_registered']) * 100, 1) 
                : 0,
            'submission_to_acceptance' => $abstractStats['total_abstracts'] > 0 
                ? round(($abstractStats['accepted'] / $abstractStats['total_abstracts']) * 100, 1) 
                : 0,
            'student_participation' => $registrationStats['total_registered'] > 0
                ? round(($participantTypeStats['students'] / $registrationStats['total_registered']) * 100, 1)
                : 0,
        ];

        // 12. RECENT HIGH-LEVEL ACTIVITY - REAL DATA
        $recentActivity = AbstractSubmission::with(['user'])
            ->latest()
            ->take(10)
            ->get();

        // 13. TITLE DISTRIBUTION - REAL DATA
        $titleStats = User::select('title', DB::raw('COUNT(*) as count'))
            ->where('email_verified_at', '!=', null)
            ->whereNotNull('title')
            ->where('title', '!=', '')
            ->groupBy('title')
            ->orderByDesc('count')
            ->get();

        return view('admin.analytics.dashboard', compact(
            'registrationStats',
            'abstractStats', 
            'countryDistribution',
            'affiliationDistribution', // Changed from institutionDistribution
            'participantTypeStats', // New student vs professional stats
            'registrationTrends',
            'submissionTrends',
            'topCountriesByAbstracts',
            'topAffiliationsByAbstracts', // Changed from topInstitutionsByAbstracts
            'reviewerStats',
            'conversionRates',
            'recentActivity',
            'titleStats' // New title statistics
        ));
    }

    public function getRealtimeStats()
    {
        // Get real-time counts
        $totalRegistered = User::where('email_verified_at', '!=', null)->count();
        $totalAbstracts = AbstractSubmission::count();
        $submittedAbstracts = User::whereHas('abstractSubmissions')->count();

        return response()->json([
            // Registration metrics - REAL DATA
            'total_registered' => $totalRegistered,
            'total_paid' => 0, // Placeholder for payment module
            'total_submitted_abstracts' => $submittedAbstracts,
            'registered_no_submission' => $totalRegistered - $submittedAbstracts,
            
            // Abstract metrics - REAL DATA
            'total_abstracts' => $totalAbstracts,
            'pending_review' => AbstractSubmission::where('status', 'submitted')->count(),
            'under_review' => AbstractSubmission::where('status', 'under_review')->count(),
            'accepted' => AbstractSubmission::where('status', 'accepted')->count(),
            'rejected' => AbstractSubmission::where('status', 'rejected')->count(),
            'accepted_oral' => AbstractSubmission::where('status', 'accepted')
                ->whereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['oral'])
                ->count(),
            'accepted_poster' => AbstractSubmission::where('status', 'accepted')
                ->where(function ($query) {
                    $query->whereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['poster'])
                        ->orWhereRaw('LOWER(COALESCE(presentation_mode, "")) = ?', ['audio_poster']);
                })
                ->count(),
            
            // Conversion rates - REAL DATA
            'registration_to_submission' => $totalRegistered > 0 
                ? round(($submittedAbstracts / $totalRegistered) * 100, 1) 
                : 0,
                
            // Student stats
            'students' => User::where('email_verified_at', '!=', null)
                ->where('student_status', 'yes')->count(),
            'professionals' => User::where('email_verified_at', '!=', null)
                ->where('student_status', 'no')->count(),
            
            'last_updated' => now()->format('H:i:s'),
        ]);
    }

    private function getNormalizedAffiliationDistribution()
    {
        $users = User::query()
            ->whereNotNull('email_verified_at')
            ->where(function ($query) {
                $query->whereNotNull('affiliation')->where('affiliation', '!=', '')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('institute')->where('institute', '!=', '');
                    });
            })
            ->get(['affiliation', 'institute']);

        return $users
            ->map(function (User $user) {
                $raw = trim((string) ($user->affiliation ?: $user->institute));
                return $this->normalizeAffiliationName($raw);
            })
            ->filter()
            ->countBy()
            ->map(fn ($count, $affiliation) => (object) ['affiliation' => $affiliation, 'count' => $count])
            ->sortByDesc('count')
            ->take(10)
            ->values();
    }

    private function getNormalizedAffiliationsByAbstracts()
    {
        $rows = User::query()
            ->join('abstract_submissions', 'users.id', '=', 'abstract_submissions.user_id')
            ->where(function ($query) {
                $query->whereNotNull('users.affiliation')->where('users.affiliation', '!=', '')
                    ->orWhere(function ($nested) {
                        $nested->whereNotNull('users.institute')->where('users.institute', '!=', '');
                    });
            })
            ->get(['users.affiliation', 'users.institute']);

        return $rows
            ->map(function ($row) {
                $raw = trim((string) ($row->affiliation ?: $row->institute));
                return $this->normalizeAffiliationName($raw);
            })
            ->filter()
            ->countBy()
            ->map(fn ($count, $affiliation) => (object) ['affiliation' => $affiliation, 'abstract_count' => $count])
            ->sortByDesc('abstract_count')
            ->take(5)
            ->values();
    }

    private function normalizeAffiliationName(string $value): ?string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value));

        if ($value === '') {
            return null;
        }

        $normalized = Str::lower($value);
        $normalized = str_replace([',', '.', ';', ':', '-', '_', '/', '\\', '(', ')'], ' ', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        $normalized = trim($normalized);


        if (
            str_contains($normalized, 'muhimbili university of health and allied sciences') ||
            preg_match('/\bmuhas\b/', $normalized)
        ) {
            return 'Muhimbili University of Health and Allied Sciences (MUHAS)';
        }

        if (
            str_contains($normalized, 'kilimanjaro christian medical university college') ||
            preg_match('/\bkcmu?co?\b/', $normalized)
        ) {
            return 'Kilimanjaro Christian Medical University College (KCMUCo)';
        }

        if (
            str_contains($normalized, 'ifakara health institute') ||
            preg_match('/\bihi\b/', $normalized)
        ) {
            return 'Ifakara Health Institute (IHI)';
        }

        return Str::title($value);
    }
}
