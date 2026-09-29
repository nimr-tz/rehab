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
    public function up(): void
    {
        // Add Finance Officer Role
        if (!Role::where('name', 'finance_officer')->exists()) {
            Role::create([
                'name' => 'finance_officer',
                'display_name' => 'Finance Officer',
                'description' => 'Manages payment verification and financial operations for the conference',
                'color' => '#0d9488', // Teal
                'icon' => '💰',
                'is_active' => true,
            ]);
        }

        // Add Registration Officer Role
        if (!Role::where('name', 'registration_officer')->exists()) {
            Role::create([
                'name' => 'registration_officer',
                'display_name' => 'Registration Officer',
                'description' => 'Manages attendee check-in and registration verification during conference days',
                'color' => '#7c3aed', // Violet
                'icon' => '📋',
                'is_active' => true,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Role::whereIn('name', ['finance_officer', 'registration_officer'])->delete();
    }
};
