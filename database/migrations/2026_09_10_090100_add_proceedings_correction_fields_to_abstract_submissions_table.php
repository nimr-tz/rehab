<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Last time the author corrected their camera-ready entry. Compared
            // against the abstract book's completed_at so admins can see how many
            // entries changed since the last generation run.
            $table->timestamp('proceedings_corrected_at')->nullable()->after('code_is_final');
            $table->unsignedBigInteger('proceedings_corrected_by')->nullable()->after('proceedings_corrected_at');

            $table->foreign('proceedings_corrected_by')->references('id')->on('users')->nullOnDelete();
            $table->index('proceedings_corrected_at');
        });
    }

    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['proceedings_corrected_by']);
            $table->dropIndex(['proceedings_corrected_at']);
            $table->dropColumn(['proceedings_corrected_at', 'proceedings_corrected_by']);
        });
    }
};
