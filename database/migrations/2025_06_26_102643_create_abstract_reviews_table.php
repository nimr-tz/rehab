<?php
// Create migration: php artisan make:migration create_abstract_reviews_table

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
        Schema::create('abstract_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_submission_id')->constrained()->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('reviewer_number')->comment('1 or 2'); // Which reviewer (1st or 2nd)
            
            // Add review round for revision workflow
            $table->integer('review_round')->default(1)->nullable()->comment('Review round: 1=original, 2=first revision, etc.');
            
            // Review Criteria (1-100 scale)
            $table->decimal('technical_quality', 5, 2)->nullable()->comment('Technical soundness (1-100)');
            $table->decimal('novelty', 5, 2)->nullable()->comment('Novelty and originality (1-100)');
            $table->decimal('relevance', 5, 2)->nullable()->comment('Relevance to the conference (1-100)');
            $table->decimal('clarity', 5, 2)->nullable()->comment('Clarity of presentation (1-100)');
            $table->decimal('score', 5, 2)->nullable()->comment('Overall score (0-100)');
            
            // Review Content
            $table->text('comments')->nullable()->comment('Detailed reviewer comments');
            $table->enum('recommendation', [
                'accept_oral',
                'accept_poster',
                'accept',
                'minor_revisions',
                'major_revisions',
                'reject'
            ])->nullable();
            
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Ensure one review per reviewer per abstract
            $table->unique(['abstract_submission_id', 'reviewer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_reviews');
    }
};
