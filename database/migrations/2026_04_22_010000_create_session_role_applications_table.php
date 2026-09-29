<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_role_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role_requested', 30);
            $table->string('preferred_theme');
            $table->boolean('attendance_confirmed')->default(false);
            $table->text('notes')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['role_requested', 'preferred_theme']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_role_applications');
    }
};
