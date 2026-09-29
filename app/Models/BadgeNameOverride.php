<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class BadgeNameOverride extends Model
{
    protected $fillable = ['entity_type', 'entity_id', 'print_name', 'print_institute', 'updated_by'];

    const TYPES = ['user', 'group_member'];

    public static function getFor(string $type, int $id): ?string
    {
        return static::where('entity_type', $type)->where('entity_id', $id)->value('print_name');
    }

    public static function getInstituteFor(string $type, int $id): ?string
    {
        return static::where('entity_type', $type)->where('entity_id', $id)->value('print_institute');
    }

    public static function setFor(string $type, int $id, string $printName): void
    {
        static::updateOrCreate(
            ['entity_type' => $type, 'entity_id' => $id],
            ['print_name' => $printName, 'updated_by' => Auth::id()]
        );
    }

    public static function setInstituteFor(string $type, int $id, string $printInstitute): void
    {
        static::updateOrCreate(
            ['entity_type' => $type, 'entity_id' => $id],
            ['print_institute' => $printInstitute, 'updated_by' => Auth::id()]
        );
    }

    public static function clearFor(string $type, int $id): void
    {
        static::where('entity_type', $type)->where('entity_id', $id)->delete();
    }

    /**
     * Preload overrides for multiple entities. Returns a keyed collection:
     * ["{type}:{id}" => ['print_name' => ..., 'print_institute' => ...]]
     */
    public static function bulkLoad(array $pairs): Collection
    {
        if (empty($pairs)) return collect();

        $query = static::query()->where(function ($q) use ($pairs) {
            foreach ($pairs as [$type, $id]) {
                $q->orWhere(fn($sub) => $sub->where('entity_type', $type)->where('entity_id', $id));
            }
        });

        return $query->get()->keyBy(fn($r) => "{$r->entity_type}:{$r->entity_id}");
    }
}
