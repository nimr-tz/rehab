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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // admin, reviewer, author
            $table->string('display_name'); // Administrator, Reviewer, Author
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#6b7280'); // Hex color for UI
            $table->string('icon', 10)->default('👤'); // Emoji icon
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Insert default roles
        DB::table('roles')->insert([
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access and management',
                'color' => '#dc2626',
                'icon' => '👑',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'reviewer',
                'display_name' => 'Reviewer',
                'description' => 'Review and evaluate abstract submissions',
                'color' => '#4f46e5',
                'icon' => '🔍',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'author',
                'display_name' => 'Author',
                'description' => 'Submit and manage abstract submissions',
                'color' => '#059669',
                'icon' => '✏️',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
