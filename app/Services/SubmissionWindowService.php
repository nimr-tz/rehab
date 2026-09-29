<?php

namespace App\Services;

use App\Models\SubmissionWindowSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class SubmissionWindowService
{
    public function supportsOverrides(): bool
    {
        return Schema::hasTable('submission_window_settings');
    }

    public function deadline(): Carbon
    {
        return Carbon::parse(config('conference.submission_deadline'))->endOfDay();
    }

    public function getSetting(): ?SubmissionWindowSetting
    {
        if (!$this->supportsOverrides()) {
            return null;
        }

        return SubmissionWindowSetting::query()->firstOrCreate(['id' => 1], [
            'override_until' => null,
            'updated_by' => null,
        ]);
    }

    public function getOverrideUntil(): ?Carbon
    {
        return $this->getSetting()?->override_until;
    }

    public function hasActiveOverride(): bool
    {
        $overrideUntil = $this->getOverrideUntil();

        return $overrideUntil !== null && now()->lte($overrideUntil);
    }

    public function isOpenForPublic(): bool
    {
        return now()->lte($this->deadline()) || $this->hasActiveOverride();
    }

    public function openOverride(int $minutes, ?int $userId = null): ?SubmissionWindowSetting
    {
        if (!$this->supportsOverrides()) {
            return null;
        }

        $setting = $this->getSetting();
        $setting->update([
            'override_until' => now()->addMinutes(max(1, $minutes)),
            'updated_by' => $userId,
        ]);

        return $setting->fresh();
    }

    public function closeOverride(?int $userId = null): ?SubmissionWindowSetting
    {
        if (!$this->supportsOverrides()) {
            return null;
        }

        $setting = $this->getSetting();
        $setting->update([
            'override_until' => null,
            'updated_by' => $userId,
        ]);

        return $setting->fresh();
    }

    public function getClosedMessage(string $label = 'submissions'): string
    {
        $deadlineText = $this->deadline()->timezone(config('app.timezone'))->format('F d, Y \a\t H:i');

        return ucfirst($label) . " are closed. The submission deadline passed on {$deadlineText} EAT.";
    }

    public function status(): array
    {
        $deadline = $this->deadline();
        $overrideUntil = $this->getOverrideUntil();

        return [
            'deadline' => $deadline,
            'override_until' => $overrideUntil,
            'override_active' => $this->hasActiveOverride(),
            'is_open' => $this->isOpenForPublic(),
            'supports_overrides' => $this->supportsOverrides(),
        ];
    }
}
