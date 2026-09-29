<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('roles')->insert([
            'name' => 'scientific_admin',
            'display_name' => 'Scientific Coordinator',
            'description' => 'Oversees the scientific program, including abstracts, reviewers, and sessions.',
            'color' => '#2563eb', // A professional blue
            'icon' => '🎓',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where('name', 'scientific_admin')->delete();
    }

};
