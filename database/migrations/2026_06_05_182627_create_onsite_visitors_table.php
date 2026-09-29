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
        Schema::create('onsite_visitors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('institution')->nullable();
            // invitee | vip | guest | media | staff | speaker
            $table->string('badge_category')->default('invitee');
            $table->text('notes')->nullable();
            $table->string('qr_token', 100)->unique()->nullable();
            $table->boolean('badge_printed')->default(false);
            $table->timestamp('badge_printed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onsite_visitors');
    }
};
