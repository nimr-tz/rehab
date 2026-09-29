<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imported_session_role_people', function (Blueprint $table) {
            $table->id();
            $table->string('source_sheet')->nullable();
            $table->string('subtheme')->nullable();
            $table->string('subtheme_key', 80)->nullable()->index();
            $table->string('role', 30)->index();
            $table->string('source_name');
            $table->string('normalized_name');
            $table->string('institution')->nullable();
            $table->foreignId('matched_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('match_confidence', 30)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subtheme_key', 'role', 'normalized_name'], 'imported_role_people_unique');
            $table->index(['role', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('imported_session_role_people');
    }
};
