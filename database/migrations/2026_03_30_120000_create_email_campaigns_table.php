<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('target_group', 100);
            $table->string('audience_label', 150);
            $table->string('subject');
            $table->text('message');
            $table->string('action_text', 100)->nullable();
            $table->string('action_url')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->string('status', 50)->default('sent');
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['target_group', 'sent_at']);
            $table->index(['subject', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_campaigns');
    }
};
