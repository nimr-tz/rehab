<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** The slides for an accepted abstract, uploaded by the presenter before the deadline. One file per abstract. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presentation_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('abstract_id')->unique()->constrained('abstracts')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->unsignedBigInteger('bytes');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presentation_files');
    }
};
