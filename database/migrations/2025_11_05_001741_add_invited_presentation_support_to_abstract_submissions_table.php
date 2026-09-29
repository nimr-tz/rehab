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
        Schema::table('abstract_submissions', function (Blueprint $table) {
            // Mark if this is an invited/guest presentation (bypasses normal submission/review process)
            $table->boolean('is_invited')->default(false)->after('status');
            // Track who created this invited presentation
            $table->unsignedBigInteger('invited_created_by')->nullable()->after('is_invited');
            // When the invitation was added to the system
            $table->timestamp('invited_created_at')->nullable()->after('invited_created_by');
            
            $table->foreign('invited_created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('abstract_submissions', function (Blueprint $table) {
            $table->dropForeign(['invited_created_by']);
            $table->dropColumn([
                'is_invited',
                'invited_created_by',
                'invited_created_at'
            ]);
        });
    }
};
