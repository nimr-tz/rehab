<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->default('error'); // error, warning, info, debug
            $table->string('channel', 50)->default('system'); // payments, auth, system, etc.
            $table->string('source', 100)->nullable(); // PaymentService, AuthController, etc.
            $table->text('message');
            $table->json('context')->nullable(); // payload, user info, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->boolean('resolved')->default(false);
            $table->text('resolution_notes')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['level', 'channel']);
            $table->index('created_at');
            $table->index('resolved');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
