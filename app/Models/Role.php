<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'color',
        'icon',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users with this role
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withTimestamps()->withPivot('is_primary', 'assigned_at', 'assigned_by');
    }

    /**
     * Get role by name
     */
    public static function findByName(string $name)
    {
        return static::where('name', $name)->first();
    }

    /**
     * Scope for active roles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
