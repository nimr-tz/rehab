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
        Schema::create('abstract_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('author_name');
            $table->string('author_institute');
            $table->string('title');
            $table->text('description');
            $table->string('subtheme');
            $table->string('presentation_mode');
            $table->boolean('include_in_proceedings')->default(false);
            $table->json('coauthors')->nullable(); // Store co-authors as JSON for simplicity
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->default('draft'); // e.g., draft, submitted, under_review, accepted, rejected
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('abstract_submissions');
    }
};
