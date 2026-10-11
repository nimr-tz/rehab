<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Answers to the feedback survey: one per registration, shown to the organisers without names. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('ratings'); // key => 1..5, see config/feedback.php
            $table->unsignedTinyInteger('recommend'); // 0..10
            $table->string('next_year', 8);
            $table->json('comments')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
    }
};
