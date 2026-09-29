<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Speaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'title', // Prof., Dr., Hon., etc.
        'email',
        'bio',
        'photo_path',
        'affiliation',
        'position',
        'type',
        'social_links',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_active' => 'bool',
        'display_order' => 'integer',
    ];

    public function sessions(): HasMany
    {
        return $this->hasMany(ConferenceSession::class);
    }

    public function getPhotoUrlAttribute()
    {
        if (!$this->photo_path) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
        }

        // Check if photo_path is already a full URL
        if (filter_var($this->photo_path, FILTER_VALIDATE_URL)) {
            return $this->photo_path;
        }

        return asset('storage/' . $this->photo_path);
    }
}
