<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rapporteur_reports', function (Blueprint $table) {
            $table->foreignId('rapporteur2_user_id')->nullable()->after('subtheme')->constrained('users')->nullOnDelete();
            $table->dropColumn(['rapporteur2_name', 'rapporteur2_institution', 'rapporteur2_phone', 'rapporteur2_email']);
        });
    }

    public function down(): void
    {
        Schema::table('rapporteur_reports', function (Blueprint $table) {
            $table->dropForeign(['rapporteur2_user_id']);
            $table->dropColumn('rapporteur2_user_id');
            $table->string('rapporteur2_name')->nullable();
            $table->string('rapporteur2_institution')->nullable();
            $table->string('rapporteur2_phone')->nullable();
            $table->string('rapporteur2_email')->nullable();
        });
    }
};
