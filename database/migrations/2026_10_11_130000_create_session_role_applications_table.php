<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Participants apply to chair a session or to be its rapporteur. The committee
 * accepts an application by giving it a session, or declines it. Rapporteurs
 * write a short report of their session afterwards.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_role_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 12); // App\Enums\SessionRole
            $table->json('topic_ids')->nullable(); // preferred topics
            $table->text('experience');
            $table->string('status', 12)->default('pending')->index(); // App\Enums\ApplicationStatus
            $table->foreignId('programme_session_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('decision_note')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'user_id', 'role'], 'session_role_unique');
        });

        Schema::table('programme_sessions', function (Blueprint $table) {
            $table->foreignId('chair_user_id')->nullable()->after('chair')->constrained('users')->nullOnDelete();
            $table->string('rapporteur')->nullable()->after('chair_user_id');
            $table->foreignId('rapporteur_user_id')->nullable()->after('rapporteur')->constrained('users')->nullOnDelete();
            $table->text('rapporteur_report')->nullable()->after('rapporteur_user_id');
            $table->timestamp('rapporteur_report_at')->nullable()->after('rapporteur_report');
        });
    }

    public function down(): void
    {
        Schema::table('programme_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chair_user_id');
            $table->dropConstrainedForeignId('rapporteur_user_id');
            $table->dropColumn(['rapporteur', 'rapporteur_report', 'rapporteur_report_at']);
        });

        Schema::dropIfExists('session_role_applications');
    }
};
