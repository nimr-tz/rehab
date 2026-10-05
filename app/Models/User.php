<?php

namespace App\Models;

use App\Enums\Role;
use App\Support\Countries;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['title', 'first_name', 'last_name', 'email', 'phone', 'country', 'institution', 'profession', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

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
        ];
    }

    /** "Dr Amina Mussa", as printed on badges and letters. */
    protected function name(): Attribute
    {
        return Attribute::get(fn () => trim(implode(' ', array_filter([
            $this->title, $this->first_name, $this->last_name,
        ]))));
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function countryName(): ?string
    {
        return Countries::name($this->country);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function abstracts(): HasMany
    {
        return $this->hasMany(AbstractSubmission::class);
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class, 'reviewer_id');
    }

    public function registrationFor(?Edition $edition): ?Registration
    {
        return $edition ? $this->registrations()->where('edition_id', $edition->id)->first() : null;
    }

    public function isParticipant(): bool
    {
        return $this->hasRole(Role::Participant->value);
    }

    /** Holds any role besides participant. */
    public function isStaff(): bool
    {
        return $this->roles->contains(fn ($role) => $role->name !== Role::Participant->value);
    }
}
