<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GroupMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'group_registration_id',
        'full_name',
        'email',
        'phone',
        'institution',
        'country',
        'registration_category',
        'student_id_path',
        'fee_amount',
        'fee_currency',
        'qr_token',
        'checked_in',
        'checked_in_at',
        'badge_printed',
        'badge_printed_at',
    ];

    protected $casts = [
        'fee_amount' => 'decimal:2',
        'checked_in' => 'boolean',
        'checked_in_at' => 'datetime',
        'badge_printed' => 'boolean',
        'badge_printed_at' => 'datetime',
    ];

    /**
     * Get Attendances for this member
     */
    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class, 'group_member_id');
    }

    /**
     * Mark badge as printed
     */
    public function markBadgePrinted(): void
    {
        $this->update([
            'badge_printed' => true,
            'badge_printed_at' => now(),
        ]);
    }

    /**
     * Get member initials
     */
    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->full_name));
        $initials = '';

        foreach (array_slice($words, 0, 2) as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }

        return $initials ?: '??';
    }

    /**
     * Get registration category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->registration_category) {
            'professional_local' => 'Professional (Local)',
            'professional_international' => 'Professional (International)',
            'student_local' => 'Student (Local)',
            'student_international' => 'Student (International)',
            default => str_replace('_', ' ', ucfirst($this->registration_category)),
        };
    }

    /**
     * Get formatted fee amount
     */
    public function getFormattedFeeAttribute(): string
    {
        $symbol = $this->fee_currency === 'TZS' ? 'TSh' : '$';
        if ($this->fee_currency === 'USD') {
            return '$' . number_format($this->fee_amount, 2);
        }
        return 'TSh ' . number_format($this->fee_amount, 0);
    }

    /**
     * Generate a new QR token for this member
     */
    public function generateQrToken(): string
    {
        $token = 'GM-' . strtoupper(Str::random(12));
        $this->update(['qr_token' => $token]);
        return $token;
    }

    /**
     * Boot method to generate QR token
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($member) {
            if (!$member->qr_token) {
                $member->qr_token = 'GM-' . strtoupper(Str::random(12));
            }
        });
    }

    /**
     * Get the group registration this member belongs to
     */
    public function groupRegistration(): BelongsTo
    {
        return $this->belongsTo(GroupRegistration::class);
    }

    /**
     * Check if member's group payment is verified
     */
    public function isPaymentVerified(): bool
    {
        return $this->groupRegistration?->isVerified() ?? false;
    }

    /**
     * Get status badge
     */
    public function getStatusBadge(): string
    {
        if ($this->checked_in) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Checked In</span>';
        }

        if ($this->isPaymentVerified()) {
            return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">Registered</span>';
        }

        return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">Pending Payment</span>';
    }
}
