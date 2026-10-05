<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per yearly summit. Exactly one is current.
        Schema::create('editions', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year')->unique();
            $table->string('name');
            $table->string('short_name');
            $table->string('ordinal', 10); // "5th"
            $table->string('theme')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('venue')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->boolean('registration_open')->default(false);
            $table->boolean('abstracts_open')->default(false);
            $table->date('abstract_deadline')->nullable();
            $table->date('review_deadline')->nullable();
            $table->date('session_role_deadline')->nullable();
            $table->date('presentation_deadline')->nullable();
            $table->boolean('is_current')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('registration_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2)->nullable(); // null until confirmed
            $table->boolean('is_student')->default(false);
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 6);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();

            $table->unique(['edition_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
        Schema::dropIfExists('registration_categories');
        Schema::dropIfExists('editions');
    }
};
