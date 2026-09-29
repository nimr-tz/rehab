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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->string('conference_code', 20)->nullable()->unique()->after('status');
            $table->text('committee_notes')->nullable()->after('conference_code');
            $table->boolean('committee_selected')->default(false)->after('committee_notes');
            $table->unsignedBigInteger('code_assigned_by')->nullable()->after('committee_selected');
            $table->timestamp('code_assigned_at')->nullable()->after('code_assigned_by');
            $table->string('presentation_session', 100)->nullable()->after('code_assigned_at');
            $table->date('presentation_date')->nullable()->after('presentation_session');
            $table->time('presentation_time')->nullable()->after('presentation_date');
            
            $table->foreign('code_assigned_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['code_assigned_by']);
            $table->dropColumn([
                'conference_code',
                'committee_notes',
                'committee_selected',
                'code_assigned_by',
                'code_assigned_at',
                'presentation_session',
                'presentation_date',
                'presentation_time'
            ]);
        });
    }
};
