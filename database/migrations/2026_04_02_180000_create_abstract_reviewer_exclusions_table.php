<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abstract_reviewer_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['abstract_submission_id', 'reviewer_id'], 'abstract_reviewer_exclusions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abstract_reviewer_exclusions');
    }
};
