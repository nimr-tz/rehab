<?php

namespace App\Models;

use App\Enums\ApplicationStatus;
use App\Enums\SessionRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;

class SessionRoleApplication extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'role' => SessionRole::class,
            'status' => ApplicationStatus::class,
            'topic_ids' => 'array',
            'decided_at' => 'datetime',
        ];
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(Edition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ProgrammeSession::class, 'programme_session_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /** @return Collection<int, Topic> */
    public function topics(): Collection
    {
        return Topic::whereIn('id', $this->topic_ids ?? [])->orderBy('sort')->get();
    }
}
