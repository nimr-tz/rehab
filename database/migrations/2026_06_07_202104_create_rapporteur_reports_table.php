<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rapporteur_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conference_session_id')->constrained()->cascadeOnDelete();

            // Table 2: Subtheme
            $table->string('subtheme')->nullable();

            // Second rapporteur (first is the logged-in user)
            $table->string('rapporteur2_name')->nullable();
            $table->string('rapporteur2_institution')->nullable();
            $table->string('rapporteur2_phone')->nullable();
            $table->string('rapporteur2_email')->nullable();

            // Table 3: Presentation record
            $table->json('presentations')->nullable();

            // Table 4: Discussion
            $table->json('discussion_questions')->nullable();
            $table->text('areas_of_agreement')->nullable();
            $table->text('areas_of_debate')->nullable();
            $table->text('follow_up_issues')->nullable();

            // Table 5: Scientific synthesis
            $table->text('scientific_message_1')->nullable();
            $table->text('scientific_message_2')->nullable();
            $table->text('scientific_message_3')->nullable();
            $table->text('most_important_finding')->nullable();
            $table->json('evidence_nature')->nullable();
            $table->string('evidence_status')->nullable();
            $table->text('important_method')->nullable();
            $table->text('main_limitation')->nullable();

            // Table 6: Recommendations (up to 3)
            $table->json('recommendations')->nullable();

            $table->enum('status', ['draft', 'submitted'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rapporteur_reports');
    }
};
