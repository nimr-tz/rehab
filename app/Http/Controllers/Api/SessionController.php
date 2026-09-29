<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConferenceSession;
use App\Support\ConferenceTime;
use App\Support\PosterCoordinator;
use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function index(Request $request)
    {
        $query = ConferenceSession::with(['speaker', 'abstracts' => function($q) {
            $q->where('status', 'accepted')->orderBy('session_order');
        }])
            ->where('is_active', true)
            ->orderBy('start_time');

        // Optional filter by day
        if ($request->filled('day')) {
            $query->whereJsonContains('schedule_days', $request->string('day')->toString());
        }

        $sessions = $query->get()->map(function (ConferenceSession $session) {
            $start = ConferenceTime::display($session->start_time, $session->getRawOriginal('start_time'));
            $end = ConferenceTime::display($session->end_time, $session->getRawOriginal('end_time'));

            $speaker = null;
            if ($session->speaker_id && $session->relationLoaded('speaker') && $session->speaker) {
                $speaker = [
                    'id' => 'invited_' . $session->speaker->id,
                    'name' => (string) $session->speaker->name,
                    'photo_url' => $session->speaker->photo_url,
                    'affiliation' => (string) $session->speaker->affiliation,
                    'type' => (string) $session->speaker->type,
                ];
            } elseif ($session->getAttribute('speaker')) {
                $speaker = [
                    'id' => null,
                    'name' => (string) $session->getAttribute('speaker'),
                    'photo_url' => null,
                    'affiliation' => null,
                    'type' => 'presenter',
                ];
            }

            $isPoster = strtolower((string) $session->session_type) === 'poster';
            $posterCoordinator = $isPoster ? PosterCoordinator::forSession($session) : null;

            return [
                'id' => (int) $session->id,
                'name' => (string) $session->name,
                'session_type' => (string) $session->session_type,
                'subtheme' => (string) $session->subtheme,
                'presentation_type' => (string) $session->presentation_type,
                'description' => (string) $session->description,
                'room_location' => (string) $session->room_location,
                // Poster sessions use a single per-day coordinator, not a chair per screen.
                'session_chair' => $isPoster ? '' : (string) $session->session_chair,
                'poster_coordinator' => (string) ($posterCoordinator ?? ''),
                'schedule_days' => $session->schedule_days ?? [],
                'start_time' => (string) $start,
                'end_time' => (string) $end,
                'speaker' => $speaker,
                'abstract_count' => $session->abstracts->count(),
            ];
        });

        return response()->json($sessions->values());
    }

    public function show(ConferenceSession $session)
    {
        $session->load(['speaker', 'abstracts' => function($q) {
            $q->where('status', 'accepted')->orderBy('session_order');
        }]);

        $start = ConferenceTime::display($session->start_time, $session->getRawOriginal('start_time'));
        $end = ConferenceTime::display($session->end_time, $session->getRawOriginal('end_time'));

        $speaker = null;
        if ($session->speaker_id && $session->speaker) {
            $speaker = [
                'id' => 'invited_' . $session->speaker->id,
                'name' => (string) $session->speaker->name,
                'photo_url' => $session->speaker->photo_url,
                'affiliation' => (string) $session->speaker->affiliation,
                'bio' => (string) $session->speaker->bio,
            ];
        } elseif ($session->getAttribute('speaker')) {
            $speaker = [
                'id' => null,
                'name' => (string) $session->getAttribute('speaker'),
                'photo_url' => null,
                'affiliation' => null,
                'bio' => null,
            ];
        }

        $isPoster = strtolower((string) $session->session_type) === 'poster';
        $posterCoordinator = $isPoster ? PosterCoordinator::forSession($session) : null;

        return response()->json([
            'id' => $session->id,
            'name' => (string) $session->name,
            'session_type' => (string) $session->session_type,
            'subtheme' => (string) $session->subtheme,
            'description' => (string) $session->description,
            'room_location' => (string) $session->room_location,
            // Poster sessions use a single per-day coordinator, not a chair per screen.
            'session_chair' => $isPoster ? '' : (string) $session->session_chair,
            'poster_coordinator' => (string) ($posterCoordinator ?? ''),
            'session_rapporteur' => (string) $session->session_rapporteur,
            'schedule_days' => $session->schedule_days ?? [],
            'start_time' => $start,
            'end_time' => $end,
            'speaker' => $speaker,
            'abstracts' => $session->abstracts->map(function($abstract) {
                return [
                    'id' => $abstract->id,
                    'title' => (string) $abstract->title,
                    'author_name' => (string) $abstract->author_name,
                    'author_institute' => (string) $abstract->author_institute,
                    'conference_code' => (string) $abstract->conference_code,
                    'presentation_mode' => (string) $abstract->presentation_mode,
                ];
            })
        ]);
    }
}

