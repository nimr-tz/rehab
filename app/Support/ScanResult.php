<?php

namespace App\Support;

use App\Models\Registration;
use App\Models\SessionAttendance;

/** What happened when a badge was scanned at a session. */
final readonly class ScanResult
{
    public const RECORDED = 'recorded';

    public const ALREADY = 'already_recorded';

    public const NOT_FOUND = 'not_found';

    public const NOT_PAID = 'not_paid';

    public const CLOSED = 'session_closed';

    public const NOT_SCANNABLE = 'not_scannable';

    public function __construct(
        public string $outcome,
        public string $message,
        public ?Registration $registration = null,
        public ?SessionAttendance $attendance = null,
    ) {}

    /** The person counts as attending: newly recorded or already recorded. */
    public function counted(): bool
    {
        return in_array($this->outcome, [self::RECORDED, self::ALREADY], true);
    }
}
