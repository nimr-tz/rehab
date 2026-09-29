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
        Schema::table('invitation_letters', function (Blueprint $table) {
            $table->string('passport_number')->nullable()->change();
            $table->date('date_of_birth')->nullable()->change();
            $table->string('institute')->nullable()->after('passport_name');
            $table->boolean('agreed_to_terms')->default(false)->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitation_letters', function (Blueprint $table) {
            $table->string('passport_number')->nullable(false)->change();
            $table->date('date_of_birth')->nullable(false)->change();
            $table->dropColumn(['institute', 'agreed_to_terms']);
        });
    }
};
