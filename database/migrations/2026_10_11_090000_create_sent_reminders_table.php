<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reminders the portal has sent on its own (reviews due, revisions due,
 * unpaid fees), so each one goes out once only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sent_reminders', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 40);
            $table->morphs('remindable');
            $table->timestamp('sent_at');

            $table->unique(['kind', 'remindable_type', 'remindable_id'], 'sent_reminder_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sent_reminders');
    }
};
