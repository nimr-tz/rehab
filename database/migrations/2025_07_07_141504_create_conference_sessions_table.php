<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conference_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Morning Session - Clinical Research"
            $table->string('subtheme'); // Links to abstracts by subtheme
            $table->enum('presentation_type', ['oral', 'poster'])->default('oral');
            $table->text('description')->nullable();
            $table->string('room_location')->nullable(); // e.g., "Main Hall", "Conference Room A"
            $table->string('session_chair')->nullable(); // Person moderating the session
            $table->string('session_chair_email')->nullable();
            
            // Flexible multi-day support
            $table->json('schedule_days')->nullable(); // Array of dates this session spans
            $table->time('start_time')->nullable(); // Daily start time
            $table->time('end_time')->nullable(); // Daily end time
            $table->integer('estimated_duration_minutes')->nullable(); // Total session duration
            
            // Session management
            $table->integer('max_abstracts')->default(10); // Maximum abstracts per session
            $table->integer('current_abstracts')->default(0); // Current count
            $table->integer('sort_order')->default(0); // For ordering sessions
            $table->enum('status', ['draft', 'scheduled', 'ongoing', 'completed', 'cancelled'])->default('draft');
            
            // Admin fields
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['subtheme', 'presentation_type']);
            $table->index(['status', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conference_sessions');
    }
};
