<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    use HasFactory;

    public const CHANNEL_SYSTEM = 'system';
    public const USER_NOTIFICATION_NOT_REQUESTED = 'not_requested';
    public const USER_NOTIFICATION_SENT = 'sent';
    public const USER_NOTIFICATION_FAILED = 'failed';

    protected $fillable = [
        'level',
        'channel',
        'source',
        'message',
        'context',
        'user_id',
        'ip_address',
        'resolved',
        'resolution_notes',
        'resolved_by',
        'resolved_at',
        'fingerprint',
        'first_seen_at',
        'last_seen_at',
        'occurrence_count',
        'resolution_summary',
        'user_notification_email',
        'user_notification_status',
        'user_notification_sent_at',
        'user_notification_error',
        'user_action_required',
        'user_action_details',
    ];

    protected $casts = [
        'context' => 'array',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'user_notification_sent_at' => 'datetime',
        'user_action_required' => 'boolean',
    ];

    /**
     * The user related to this log entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The admin who resolved this log
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Quick helper to log a warning
     */
    public static function warn(string $channel, string $message, array $context = [], ?int $userId = null): self
    {
        return static::create([
            'level' => 'warning',
            'channel' => $channel,
            'source' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? 'Unknown',
            'message' => $message,
            'context' => $context,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Quick helper to log a system error
     */
    public static function logError(string $channel, string $message, array $context = [], ?int $userId = null): self
    {
        return static::create([
            'level' => 'error',
            'channel' => $channel,
            'source' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'] ?? 'Unknown',
            'message' => $message,
            'context' => $context,
            'user_id' => $userId,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Mark as resolved
     */
    public function markResolved(string $notes = '', ?int $resolvedBy = null): void
    {
        $this->update([
            'resolved' => true,
            'resolution_notes' => $notes,
            'resolved_by' => $resolvedBy ?? auth()->id(),
            'resolved_at' => now(),
        ]);
    }

    public function scopeCriticalIncidents($query)
    {
        return $query->where('channel', self::CHANNEL_SYSTEM)
            ->whereNotNull('fingerprint');
    }

    /**
     * Get level badge
     */
    public function getLevelBadgeAttribute(): string
    {
        return match($this->level) {
            'error' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-100 text-rose-700 uppercase tracking-wider">Error</span>',
            'warning' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-700 uppercase tracking-wider">Warning</span>',
            'info' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-blue-100 text-blue-700 uppercase tracking-wider">Info</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700 uppercase tracking-wider">Debug</span>',
        };
    }

    /**
     * Get channel badge
     */
    public function getChannelBadgeAttribute(): string
    {
        return match($this->channel) {
            'auth' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-purple-100 text-purple-700 uppercase tracking-wider">Auth</span>',
            'system' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-slate-100 text-slate-700 uppercase tracking-wider">System</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-black bg-gray-100 text-gray-700 uppercase tracking-wider">' . ucfirst($this->channel) . '</span>',
        };
    }

    /**
     * Scope for unresolved logs
     */
    public function scopeUnresolved($query)
    {
        return $query->where('resolved', false);
    }

}
