<?php

namespace App\Services;

use App\Models\AbstractSubmission;
use Illuminate\Support\Facades\Auth;

class SessionTopicDetectionService
{
    /**
     * Code prefix => topic name, from config('conference.subtheme_prefixes').
     *
     * @return array<string, string>
     */
    public static function codes(): array
    {
        return array_flip(config('conference.subtheme_prefixes', []));
    }

    /**
     * Resolve an abstract's topic and code prefix.
     *
     * An existing conference code (OR-HBR-01, HBR-001) wins, so admin
     * assignments stay authoritative; otherwise the abstract's topic
     * (subtheme) decides.
     */
    public function detect(AbstractSubmission $abstract): ?array
    {
        $codes = self::codes();
        if (empty($codes)) {
            return null;
        }

        if ($abstract->conference_code) {
            $prefix = $this->topicPrefixFromConferenceCode((string) $abstract->conference_code);
            if (isset($codes[$prefix])) {
                return [
                    'code' => $prefix,
                    'topic' => $codes[$prefix],
                    'source' => 'code_prefix',
                ];
            }
        }

        $subtheme = trim((string) $abstract->subtheme);
        $prefix = config('conference.subtheme_prefixes', [])[$subtheme] ?? null;

        if (! $prefix) {
            return null;
        }

        return [
            'code' => $prefix,
            'topic' => $subtheme,
            'source' => 'subtheme',
        ];
    }

    public function syncSuggestedTopic(AbstractSubmission $abstract, bool $force = false): bool
    {
        $isLockedManual = $abstract->session_topic_source === 'manual' && $abstract->session_topic_locked_at;
        if ($isLockedManual && ! $force) {
            return false;
        }

        $detection = $this->detect($abstract);
        if (! $detection) {
            return false;
        }

        $currentTopic = $abstract->session_topic;
        $currentSource = $abstract->session_topic_source;

        if (! $force && $currentSource === 'manual' && $currentTopic) {
            return false;
        }

        if ($currentTopic === $detection['topic'] && $currentSource === 'auto') {
            $abstract->update(['session_topic_detected_at' => now()]);

            return true;
        }

        $abstract->update([
            'session_topic' => $detection['topic'],
            'session_topic_source' => 'auto',
            'session_topic_detected_at' => now(),
            'session_topic_locked_at' => null,
            'session_topic_locked_by' => null,
        ]);

        return true;
    }

    public function applyManualTopic(AbstractSubmission $abstract, ?string $topic): void
    {
        $topic = $topic !== null ? trim($topic) : null;

        $abstract->update([
            'session_topic' => $topic ?: null,
            'session_topic_source' => $topic ? 'manual' : null,
            'session_topic_locked_at' => $topic ? now() : null,
            'session_topic_locked_by' => $topic ? Auth::id() : null,
            'session_topic_detected_at' => $topic ? $abstract->session_topic_detected_at : null,
        ]);
    }

    /**
     * Detect which conference code prefix an abstract should get.
     * Returns the code string (e.g. 'HBR') or null if the topic is unknown.
     */
    public function detectCode(AbstractSubmission $abstract): ?string
    {
        $result = $this->detect($abstract);

        return $result ? $result['code'] : null;
    }

    private function topicPrefixFromConferenceCode(string $conferenceCode): string
    {
        $conferenceCode = strtoupper(trim($conferenceCode));

        if (preg_match('/^(?:OR|PO)-([A-Z0-9]+)-\d+$/', $conferenceCode, $matches)) {
            return $matches[1];
        }

        if (preg_match('/^([A-Z0-9]+)-\d+$/', $conferenceCode, $matches)) {
            return $matches[1];
        }

        return strtoupper(explode('-', $conferenceCode, 2)[0]);
    }
}
