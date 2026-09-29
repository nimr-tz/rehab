<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function run(): void
    {
        // Add Chair Role
        if (!Role::where('name', 'chair')->exists()) {
            Role::create([
                'name' => 'chair',
                'display_name' => 'Session Chairperson',
                'description' => 'Moderates and leads conference sessions',
                'color' => '#8b5cf6', // Purple
                'icon' => 'user-group',
                'is_active' => true,
            ]);
        }

        // Add Rapporteur Role
        if (!Role::where('name', 'rapporteur')->exists()) {
            Role::create([
                'name' => 'rapporteur',
                'display_name' => 'Session Rapporteur',
                'description' => 'Documents and reports on conference sessions',
                'color' => '#10b981', // Emerald
                'icon' => 'clipboard-list',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::whereIn('name', ['chair', 'rapporteur'])->delete();
    }
};
