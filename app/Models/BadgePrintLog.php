<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BadgePrintLog extends Model
{
    protected $fillable = [
        'entity_type',
        'entity_id',
        'entity_name',
        'entity_institution',
        'entity_category',
        'print_number',
        'printed_at',
    ];

    protected $casts = [
        'printed_at' => 'datetime',
    ];

    /**
     * Record a print event for any entity. Automatically calculates print_number.
     */
    public static function record(string $type, int $id, string $name, ?string $institution = null, ?string $category = null): self
    {
        $printNumber = self::where('entity_type', $type)->where('entity_id', $id)->count() + 1;

        return self::create([
            'entity_type'        => $type,
            'entity_id'          => $id,
            'entity_name'        => $name,
            'entity_institution' => $institution,
            'entity_category'    => $category,
            'print_number'       => $printNumber,
            'printed_at'         => now(),
        ]);
    }

    public function getIsReprintAttribute(): bool
    {
        return $this->print_number > 1;
    }

    public function getEntityTypeLabelAttribute(): string
    {
        return match ($this->entity_type) {
            'user'             => 'Delegate',
            'group_member'     => 'Group Member',
            'onsite_visitor'   => 'Walk-In',
            default            => ucfirst(str_replace('_', ' ', $this->entity_type)),
        };
    }
}
