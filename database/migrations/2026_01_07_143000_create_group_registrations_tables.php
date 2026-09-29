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
        // Create group_registrations table
        Schema::create('group_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leader_user_id')->constrained('users')->onDelete('cascade');
            $table->string('group_name')->nullable();
            $table->string('organization')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('TZS');
            $table->enum('payment_status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending');
            $table->text('payment_notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('payment_submitted_at')->nullable();
            $table->timestamp('payment_verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes
            $table->index('payment_status');
            $table->index('created_at');
        });

        // Create group_members table
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_registration_id')->constrained('group_registrations')->onDelete('cascade');
            $table->string('full_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('institution')->nullable();
            $table->string('country')->nullable();
            $table->enum('registration_category', [
                'professional_local',
                'professional_international',
                'student_local',
                'student_international'
            ]);
            $table->decimal('fee_amount', 12, 2);
            $table->string('fee_currency', 3)->default('TZS');
            $table->string('qr_token', 64)->unique()->nullable();
            $table->boolean('checked_in')->default(false);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('email');
            $table->index('qr_token');
            $table->index('checked_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
        Schema::dropIfExists('group_registrations');
    }
};
