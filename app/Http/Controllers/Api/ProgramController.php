<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConferenceSession;
use App\Support\ConferenceTime;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    public function index()
    {
        $sessions = ConferenceSession::with([
            'speaker',
            'abstracts' => function ($query) {
                $query->where('status', 'accepted');
            },
        ])
            ->where('is_active', true)
            ->orderBy('start_time')
            ->get();

        $lastUpdated = $sessions->max('updated_at')?->toISOString() ?? now()->toISOString();

        $formattedSessions = $sessions->map(function ($session) {
            // Get the first scheduled day and combine with time
            $scheduleDay = is_array($session->schedule_days) && count($session->schedule_days) > 0
                ? $session->schedule_days[0]
                : now()->format('Y-m-d');

            // Ensure time columns are formatted from raw TIME values, not datetime casts.
            $startTime = ConferenceTime::displayWithSeconds(
                $session->start_time,
                $session->getRawOriginal('start_time'),
                '09:00:00'
            );
            $endTime = ConferenceTime::displayWithSeconds(
                $session->end_time,
                $session->getRawOriginal('end_time'),
                '17:00:00'
            );

            $startDateTime = $scheduleDay.'T'.$startTime;
            $endDateTime = $scheduleDay.'T'.$endTime;

            // Handle speaker - check if it's a relationship or just a string
            $speakerData = null;
            $speakerRelation = $session->relationLoaded('speaker') ? $session->getRelation('speaker') : null;

            if ($session->speaker_id && $speakerRelation) {
                $speakerData = [
                    'name' => (string) $speakerRelation->name,
                    'photo_url' => $speakerRelation->photo_url,
                    'id' => (string) $speakerRelation->id,
                ];
            } elseif ($session->getAttribute('speaker')) {
                $speakerData = [
                    'name' => (string) $session->getAttribute('speaker'),
                    'photo_url' => null,
                    'id' => null,
                ];
            }

            return [
                'id' => $session->id,
                'title' => $session->name,
                'type' => $session->session_type,
                'location' => $session->room_location,
                'start_time' => $startDateTime,
                'end_time' => $endDateTime,
                'description' => $session->description,
                'speaker' => $speakerData,
                'updated_at' => $session->updated_at?->toISOString()
                    ?? $session->created_at?->toISOString()
                    ?? now()->toISOString(),
                'abstracts' => $session->abstracts->map(function ($abstract) {
                    return [
                        'id' => $abstract->id,
                        'title' => $abstract->title,
                        'author' => $abstract->author_name,
                        'affiliation' => $abstract->author_institute,
                        'description' => $abstract->description,
                        'code' => $abstract->conference_code,
                    ];
                })->values(),
            ];
        });

        return response()->json([
            'sessions' => $formattedSessions,
            'last_updated' => $lastUpdated,
        ]);
    }

    /**
     * Quickly check if the local app data is stale.
     */
    public function checkUpdates(Request $request)
    {
        $appLastSync = $request->query('last_sync');
        $latestUpdate = ConferenceSession::where('is_active', true)->max('updated_at');

        $isStale = true;
        if ($appLastSync && $latestUpdate) {
            $isStale = \Carbon\Carbon::parse($appLastSync)->lt($latestUpdate);
        }

        return response()->json([
            'is_stale' => $isStale,
            'latest_update' => $latestUpdate
                ? \Carbon\Carbon::parse($latestUpdate)->toISOString()
                : now()->toISOString(),
        ]);
    }

}
