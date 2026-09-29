<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proceedings_correction_settings', function (Blueprint $table) {
            $table->id();
            // Master switch — corrections are closed until an admin opens them.
            $table->boolean('is_open')->default(false);
            // Optional auto-close moment. Null means "open until closed by hand".
            $table->timestamp('closes_at')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proceedings_correction_settings');
    }
};
