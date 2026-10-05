<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abstracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // submitting author
            $table->foreignId('topic_id')->constrained()->restrictOnDelete();
            $table->string('preferred_type', 10); // oral | poster | either
            $table->string('title');
            $table->text('background');
            $table->text('methods');
            $table->text('results');
            $table->text('conclusions');
            $table->string('keywords')->nullable();
            $table->string('status', 20)->index(); // App\Enums\AbstractStatus
            $table->string('decision_type', 10)->nullable(); // oral | poster, once accepted
            $table->text('decision_note')->nullable();
            $table->string('code', 20)->nullable()->unique(); // OR-HBR-01
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });

        Schema::create('abstract_authors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_id')->constrained('abstracts')->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('affiliation');
            $table->boolean('is_presenter')->default(false);
            $table->unsignedSmallInteger('position');
            $table->timestamps();
        });

        // Double-blind: reviewers see the abstract text, never the authors.
        Schema::create('review_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_id')->constrained('abstracts')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('due_on')->nullable();
            $table->unsignedTinyInteger('score_relevance')->nullable(); // 1-5
            $table->unsignedTinyInteger('score_originality')->nullable();
            $table->unsignedTinyInteger('score_methods')->nullable();
            $table->unsignedTinyInteger('score_clarity')->nullable();
            $table->string('recommendation', 20)->nullable(); // accept_oral | accept_poster | reject
            $table->text('comments_for_author')->nullable();
            $table->text('comments_for_committee')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['abstract_id', 'reviewer_id']);
        });

        // Programme
        Schema::create('programme_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('kind', 12); // plenary | parallel | posters | panel | break
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('hall')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('chair')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('programme_session_abstract', function (Blueprint $table) {
            $table->id();
            $table->foreignId('programme_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('abstract_id')->constrained('abstracts')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);

            $table->unique(['programme_session_id', 'abstract_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programme_session_abstract');
        Schema::dropIfExists('programme_sessions');
        Schema::dropIfExists('review_assignments');
        Schema::dropIfExists('abstract_authors');
        Schema::dropIfExists('abstracts');
    }
};
