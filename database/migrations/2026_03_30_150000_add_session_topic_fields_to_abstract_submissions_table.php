<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->string('session_topic', 120)->nullable()->after('session_order');
            $table->string('session_topic_source', 20)->nullable()->after('session_topic');
            $table->timestamp('session_topic_detected_at')->nullable()->after('session_topic_source');
            $table->timestamp('session_topic_locked_at')->nullable()->after('session_topic_detected_at');
            $table->unsignedBigInteger('session_topic_locked_by')->nullable()->after('session_topic_locked_at');

            $table->index(['status', 'subtheme', 'session_topic'], 'abstract_status_subtheme_topic_idx');
        });
    }

    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropIndex('abstract_status_subtheme_topic_idx');
            $table->dropColumn([
                'session_topic',
                'session_topic_source',
                'session_topic_detected_at',
                'session_topic_locked_at',
                'session_topic_locked_by',
            ]);
        });
    }
};
