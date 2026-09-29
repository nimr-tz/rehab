<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->string('fingerprint', 64)->nullable()->after('source');
            $table->timestamp('first_seen_at')->nullable()->after('ip_address');
            $table->timestamp('last_seen_at')->nullable()->after('first_seen_at');
            $table->unsignedInteger('occurrence_count')->default(1)->after('last_seen_at');
            $table->text('resolution_summary')->nullable()->after('resolution_notes');
            $table->string('user_notification_email')->nullable()->after('resolution_summary');
            $table->string('user_notification_status', 32)->default('not_requested')->after('user_notification_email');
            $table->timestamp('user_notification_sent_at')->nullable()->after('user_notification_status');
            $table->text('user_notification_error')->nullable()->after('user_notification_sent_at');
            $table->boolean('user_action_required')->default(false)->after('user_notification_error');
            $table->text('user_action_details')->nullable()->after('user_action_required');

            $table->index('fingerprint');
            $table->index('user_notification_status');
        });
    }

    public function down(): void
    {
        Schema::table('system_logs', function (Blueprint $table) {
            $table->dropIndex(['fingerprint']);
            $table->dropIndex(['user_notification_status']);
            $table->dropColumn([
                'fingerprint',
                'first_seen_at',
                'last_seen_at',
                'occurrence_count',
                'resolution_summary',
                'user_notification_email',
                'user_notification_status',
                'user_notification_sent_at',
                'user_notification_error',
                'user_action_required',
                'user_action_details',
            ]);
        });
    }
};
