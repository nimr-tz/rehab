<?php

namespace App\Models;

use App\Payments\Concerns\HasPayments;
use App\Payments\Contracts\Payable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, Payable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasPayments, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'first_name',
        'last_name',
        'affiliation',
        'country',
        'phone',
        'email',
        'student_status',
        'student_document',
        'password',
        'profile_image',
        'bio',
        'linkedin_url',
        'twitter_url',
        'role',
        'institute',
        'specialization',
        'professional_board',
        'registration_number',
        'registration_category',
        'payment_status',
        'payment_method',
        'payment_verified_at',
        'payment_verified_by',
        'payment_notes',
        'checked_in_at',
        'checked_in_by',
        'qr_code_token',
        'badge_printed',
        'badge_printed_at',
        'payment_reference',
        'intent_attendee',
        'intent_presenter',
        'intent_group_leader',
        'student_verification_status',
        'student_verified_at',
        'student_verified_by',
        'student_verification_notes',
        'reviewer_max_load',
        'reviewer_preferences_set',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'payment_verified_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'badge_printed_at' => 'datetime',
            'badge_printed' => 'boolean',
            'intent_attendee' => 'boolean',
            'intent_presenter' => 'boolean',
            'intent_group_leader' => 'boolean',
            'student_verified_at' => 'datetime',
        ];
    }

    // Add this relationship method to your User model

    public function reviewedAbstracts1()
    {
        return $this->hasMany(AbstractSubmission::class, 'reviewer_id');
    }

    public function reviewedAbstracts2()
    {
        return $this->hasMany(AbstractSubmission::class, 'reviewer_2_id');
    }

    public function abstractSubmissions()
    {
        return $this->hasMany(AbstractSubmission::class, 'user_id');
    }

    /**
     * User's daily attendance records
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * User's conference feedback records
     */
    public function feedback()
    {
        return $this->hasMany(ConferenceFeedback::class);
    }

    public function interests()
    {
        return $this->hasMany(ReviewerSubtheme::class);
    }

    /**
     * Get the user's group member record (if any)
     */
    public function groupMember()
    {
        return $this->hasOne(GroupMember::class, 'email', 'email');
    }

    public function hasSubmissions(): bool
    {
        return $this->abstractSubmissions()->exists();
    }

    /**
     * Check if user is part of a group registration
     */
    public function isPartofGroup(): bool
    {
        return $this->groupMember()->exists();
    }

    /**
     * Get user's group registrations (as leader)
     */
    public function groupRegistrations()
    {
        return $this->hasMany(GroupRegistration::class, 'leader_user_id');
    }

    public function hasGroupRegistrations(): bool
    {
        return $this->groupRegistrations()->exists();
    }

    public function hasGroupRegistrationAccess(): bool
    {
        return (bool) $this->intent_group_leader || $this->hasGroupRegistrations();
    }

    /**
     * Get the user's full name.
     */
    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * User's roles (many-to-many relationship)
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
                    ->withTimestamps()
                    ->withPivot('is_primary', 'assigned_at', 'assigned_by');
    }

    /**
     * Get user's primary role for navigation
     */
    public function primaryRole()
    {
        return $this->roles()->wherePivot('is_primary', true)->first();
    }

    /**
     * Get primary role name (fallback to first role)
     */
    public function getPrimaryRoleNameAttribute()
    {
        $primaryRole = $this->primaryRole();
        if ($primaryRole) {
            return $primaryRole->name;
        }

        // Fallback to first role
        $firstRole = $this->roles()->first();
        return $firstRole ? $firstRole->name : 'author';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->exists();
    }

    /**
     * Check if user has all given roles
     */
    public function hasAllRoles(array $roleNames): bool
    {
        return $this->roles()->whereIn('name', $roleNames)->count() === count($roleNames);
    }

    /**
     * Assign role to user
     */
    public function assignRole(string $roleName, bool $isPrimary = false, ?int $assignedBy = null): bool
    {
        $role = Role::findByName($roleName);
        if (!$role) {
            return false;
        }

        // If setting as primary, remove primary from other roles
        if ($isPrimary) {
            $this->roles()->updateExistingPivot($this->roles()->pluck('roles.id'), ['is_primary' => false]);
        }

        $this->roles()->syncWithoutDetaching([
            $role->id => [
                'is_primary' => $isPrimary,
                'assigned_at' => now(),
                'assigned_by' => $assignedBy
            ]
        ]);

        return true;
    }

    /**
     * Remove role from user
     */
    public function removeRole(string $roleName): bool
    {
        $role = Role::findByName($roleName);
        if (!$role) {
            return false;
        }

        $this->roles()->detach($role->id);

        // If this was primary role, set another role as primary
        if (!$this->primaryRole() && $this->roles()->count() > 0) {
            $firstRole = $this->roles()->first();
            $this->roles()->updateExistingPivot($firstRole->id, ['is_primary' => true]);
        }

        return true;
    }

    /**
     * Set primary role
     */
    public function setPrimaryRole(string $roleName): bool
    {
        if (!$this->hasRole($roleName)) {
            return false;
        }

        // Remove primary from all roles
        $this->roles()->updateExistingPivot($this->roles()->pluck('roles.id'), ['is_primary' => false]);

        // Set new primary role
        $role = Role::findByName($roleName);
        $this->roles()->updateExistingPivot($role->id, ['is_primary' => true]);

        return true;
    }

    /**
     * Get role names as array
     */
    public function getRoleNamesAttribute(): array
    {
        return $this->roles()->pluck('name')->toArray();
    }

    /**
     * Get user's primary role name (fallback)
     */
    public function getRoleAttribute(): string
    {
        return $this->getPrimaryRoleNameAttribute();
    }

    /**
     * Get review conflicts for this user
     */
    public function reviewConflicts()
    {
        return $this->hasMany(ReviewConflict::class, 'reviewer_id');
    }

    /**
     * Get user's notifications
     */
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class);
    }

    /**
     * Get user's reviews
     */
    public function reviews()
    {
        return $this->hasMany(\App\Models\AbstractReview::class, 'reviewer_id');
    }

    /**
     * Get user's full name
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the display name attribute.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->title . ' ' . $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Get sanitized initials, ignoring honorific titles in first_name.
     */
    public function getInitialsAttribute(): string
    {
        $first = trim((string) $this->first_name);
        $last = trim((string) $this->last_name);

        // If first_name accidentally contains a title, strip common honorifics
        $honorifics = ['mr', 'mrs', 'ms', 'miss', 'dr', 'prof', 'sir', 'madam', 'rev', 'hon', 'eng'];
        $firstNormalized = strtolower(str_replace('.', '', $first));
        $tokens = preg_split('/\s+/', $firstNormalized) ?: [];
        if (!empty($tokens) && in_array($tokens[0], $honorifics, true)) {
            array_shift($tokens);
        }

        $firstInitial = '';
        foreach ($tokens as $tok) {
            if ($tok !== '') {
                $firstInitial = strtoupper(substr($tok, 0, 1));
                break;
            }
        }

        if ($firstInitial === '' && $first !== '') {
            $firstInitial = strtoupper(substr($first, 0, 1));
        }

        $lastInitial = $last !== '' ? strtoupper(substr($last, 0, 1)) : '';
        return $firstInitial . $lastInitial;
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmail);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new \App\Notifications\ResetPassword($token));
    }

    /**
     * Check if user has verified payment (direct or via group)
     */
    public function isPaid(): bool
    {
        if (in_array($this->payment_status, ['verified', 'waived'])) {
            return true;
        }

        // Check for group leader status
        $ledGroup = GroupRegistration::where('leader_user_id', $this->id)
            ->whereIn('payment_status', ['verified', 'waived'])
            ->exists();
        if ($ledGroup) {
            return true;
        }

        // Check for group membership (case-insensitive check for robustness)
        $groupMember = GroupMember::where('email', 'LIKE', $this->email)->first();
        if ($groupMember && $groupMember->groupRegistration) {
            return in_array($groupMember->groupRegistration->payment_status, ['verified', 'waived']);
        }

        return false;
    }

    /**
     * Registration fee for the user's category (config/payments.php).
     */
    public function getRegistrationFee(): array
    {
        $fee = config('payments.registration_fees.'.$this->registration_category);

        return $fee
            ? ['amount' => (float) $fee['amount'], 'currency' => $fee['currency'], 'label' => $fee['label']]
            : ['amount' => 0, 'currency' => 'TZS', 'label' => 'Not Selected'];
    }

    public function paymentAmount(): float
    {
        return (float) $this->getRegistrationFee()['amount'];
    }

    public function paymentCurrency(): string
    {
        return $this->getRegistrationFee()['currency'];
    }

    public function paymentDescription(): string
    {
        return 'Registration fee — '.$this->full_name;
    }

    public function paymentReferenceType(): string
    {
        return 'U';
    }

    /**
     * Get registration fee amount attribute
     */
    public function getRegistrationFeeAttribute()
    {
        return $this->getRegistrationFee()['amount'];
    }

    /**
     * Get registration currency attribute
     */
    public function getRegistrationCurrencyAttribute()
    {
        return $this->getRegistrationFee()['currency'];
    }

    public function isStudentRegistrationCategory(?string $category = null): bool
    {
        $category = $category ?? $this->registration_category;

        return in_array($category, ['student_local', 'student_international'], true);
    }

    public function syncStudentStateFromRegistrationCategory(?string $category = null): void
    {
        $category = $category ?? $this->registration_category;

        if ($this->isStudentRegistrationCategory($category)) {
            $this->student_status = 'yes';

            if ($this->student_verification_status !== 'verified') {
                $this->student_verification_status = 'pending';
                $this->student_verified_at = null;
                $this->student_verified_by = null;
            }

            return;
        }

        $this->student_status = 'no';
        $this->student_verification_status = null;
        $this->student_verified_at = null;
        $this->student_verified_by = null;
        $this->student_verification_notes = null;
    }

    /**
     * Generate a unique QR code token for this user
     */
    public function generateQrToken(): string
    {
        $prefix = config('conference.qr_prefix');
        $token = $prefix . '-' . strtoupper(\Illuminate\Support\Str::random(12));
        $this->update(['qr_code_token' => $token]);
        return $token;
    }

    /**
     * Get or generate QR code token
     */
    public function getOrCreateQrToken(): ?string
    {
        if (!$this->isPaid()) {
            return null;
        }

        if (!$this->qr_code_token) {
            // Check if this user exists as a group member and has a token there
            $groupMember = GroupMember::where('email', $this->email)->first();
            if ($groupMember && $groupMember->qr_token) {
                // Synchronize tokens so check-in is seamless
                $this->update(['qr_code_token' => $groupMember->qr_token]);
                return $groupMember->qr_token;
            }

            return $this->generateQrToken();
        }

        return $this->qr_code_token;
    }

    /**
     * Get QR code as base64 data URI (locally generated)
     */
    public function getQrCodeBase64Attribute(): ?string
    {
        $token = $this->getOrCreateQrToken();
        if (!$token) return null;

        try {
            $options = new \chillerlan\QRCode\QROptions([
                'outputType' => \chillerlan\QRCode\Output\QROutputInterface::GDIMAGE_PNG,
                'eccLevel' => \chillerlan\QRCode\Common\EccLevel::H,
                'addQuietzone' => true,
                'quietzoneSize' => 1,
                'scale' => 10,
            ]);

            $qrcode = new \chillerlan\QRCode\QRCode($options);
            return $qrcode->render(route('badge.public', $token));
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Check if user is checked in
     */
    public function isCheckedIn(): bool
    {
        return !is_null($this->checked_in_at);
    }

    /**
     * Check if user is a presenter (has accepted abstracts)
     */
    public function isPresenter(): bool
    {
        return $this->abstractSubmissions()->where('status', 'accepted')->exists();
    }

    /**
     * Get badge data for display/printing
     */
    public function getBadgeData(): array
    {
        $acceptedAbstracts = $this->abstractSubmissions()->where('status', 'accepted')->get();

        return [
            'name' => $this->full_name,
            'title' => $this->title,
            'affiliation' => $this->affiliation,
            'country' => $this->country,
            'registration_category' => $this->registration_category,
            'qr_code_token' => $this->getOrCreateQrToken(),
            'is_presenter' => $acceptedAbstracts->isNotEmpty(),
            'presentation_codes' => $acceptedAbstracts->pluck('conference_code')->filter()->values()->toArray(),
            'payment_status' => $this->payment_status,
            'checked_in' => $this->isCheckedIn(),
            'checked_in_at' => $this->checked_in_at?->format('Y-m-d H:i'),
        ];
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
     * Find user by QR token
     */
    public static function findByQrToken(string $token): ?self
    {
        $token = rawurldecode(trim($token));

        // Compatibility: If a full URL is scanned, extract the token segment.
        if (preg_match('~/((?:badge|connect))/([^/?#]+)~', $token, $matches)) {
            $token = rawurldecode($matches[2]);
        }

        return static::where('qr_code_token', $token)->first();
    }

    /**
     * Check if user has attended any day of the conference
     */
    public function hasAttendedConference(): bool
    {
        return $this->attendances()->where('day', '>=', 1)->exists();
    }

    /**
     * Whether the user can get an attendance certificate: attendance is
     * recorded and the conference has ended.
     */
    public function canDownloadCertificate(): bool
    {
        if (! $this->hasAttendedConference()) {
            return false;
        }

        $end = \Carbon\Carbon::parse(config('conference.end_date'), config('conference.timezone'))->endOfDay();

        return now()->isAfter($end);
    }
}
