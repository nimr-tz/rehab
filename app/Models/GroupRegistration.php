<?php

namespace App\Models;

use App\Payments\Concerns\HasPayments;
use App\Payments\Contracts\Payable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupRegistration extends Model implements Payable
{
    use HasFactory;
    use HasPayments;

    protected $fillable = [
        'leader_user_id',
        'group_name',
        'organization',
        'total_amount',
        'currency',
        'payment_status',
        'payment_notes',
        'admin_notes',
        'payment_submitted_at',
        'payment_verified_at',
        'verified_by',
        'payment_reference',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_submitted_at' => 'datetime',
        'payment_verified_at' => 'datetime',
    ];

    /**
     * Registration fee structure (config/payments.php).
     */
    public static function getFeeStructure(): array
    {
        return collect(config('payments.registration_fees', []))
            ->map(fn ($fee) => ['amount' => (float) $fee['amount'], 'currency' => $fee['currency'], 'label' => $fee['label']])
            ->all();
    }

    public function paymentAmount(): float
    {
        return (float) $this->calculateTotal()['amount'];
    }

    public function paymentCurrency(): string
    {
        return $this->calculateTotal()['currency'];
    }

    public function paymentDescription(): string
    {
        return 'Group registration — '.($this->group_name ?: 'Group #'.$this->id);
    }

    public function paymentReferenceType(): string
    {
        return 'G';
    }

    /**
     * Get the group leader
     */
    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_user_id');
    }

    /**
     * Get the admin who verified the payment
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get all members in this group
     */
    public function members(): HasMany
    {
        return $this->hasMany(GroupMember::class);
    }

    /**
     * Get member count
     */
    public function getMemberCountAttribute(): int
    {
        return $this->members()->count();
    }

    /**
     * Calculate total amount from members
     */
    public function calculateTotal(): array
    {
        $members = $this->members;
        
        $totalTZS = 0;
        $totalUSD = 0;

        foreach ($members as $member) {
            if ($member->fee_currency === 'TZS') {
                $totalTZS += $member->fee_amount;
            } else {
                $totalUSD += $member->fee_amount;
            }
        }

        // If mixed currencies, we need to handle this
        // For simplicity, if there's any USD, use USD as primary
        if ($totalUSD > 0 && $totalTZS > 0) {
            // Use historical rate if payment was submitted/verified, otherwise today's rate
            $date = $this->payment_submitted_at ?? $this->created_at;
            $rate = ExchangeRate::getRate('USD', 'TZS', $date);
            
            $totalUSD += $totalTZS / $rate;
            return ['amount' => $totalUSD, 'currency' => 'USD', 'mixed' => true, 'tzs' => $totalTZS, 'usd' => $totalUSD, 'rate_used' => $rate];
        } elseif ($totalUSD > 0) {
            return ['amount' => $totalUSD, 'currency' => 'USD', 'mixed' => false];
        } else {
            return ['amount' => $totalTZS, 'currency' => 'TZS', 'mixed' => false];
        }
    }

    /**
     * Recalculate and update total amount
     */
    public function recalculateTotal(): void
    {
        $totals = $this->calculateTotal();
        $this->update([
            'total_amount' => $totals['amount'],
            'currency' => $totals['currency'],
        ]);
    }

    /**
     * Check if payment is verified
     */
    public function isVerified(): bool
    {
        return $this->payment_status === 'verified';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->payment_status === 'pending';
    }

    /**
     * Get status badge HTML
     */
    public function getStatusBadge(): string
    {
        return match($this->payment_status) {
            'verified', 'waived' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 uppercase tracking-wider">Settled</span>',
            'submitted' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 uppercase tracking-wider">Under Review</span>',
            'rejected' => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700 uppercase tracking-wider">Issue</span>',
            default => '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 uppercase tracking-wider">Inactive</span>',
        };
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        $members = $this->members;
        $totalTZS = 0;
        $totalUSD = 0;

        foreach ($members as $member) {
            if ($member->fee_currency === 'TZS') {
                $totalTZS += $member->fee_amount;
            } else {
                $totalUSD += $member->fee_amount;
            }
        }

        if ($totalUSD > 0 && $totalTZS > 0) {
            $date = $this->payment_submitted_at ?? $this->created_at;
            $rate = ExchangeRate::getRate('USD', 'TZS', $date);
            $convertedTZS = $totalTZS / $rate;
            
            $totalUSDCalc = $totalUSD + $convertedTZS;
            return '$' . number_format($totalUSDCalc, 2);
        }

        if ($this->currency === 'USD') {
            return '$' . number_format($this->total_amount, 2);
        }
        return 'TZS ' . number_format($this->total_amount, 0);
    }

    /**
     * Get checked in count
     */
    public function getCheckedInCountAttribute(): int
    {
        return $this->members()->where('checked_in', true)->count();
    }
}
