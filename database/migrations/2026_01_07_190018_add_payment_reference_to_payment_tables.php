<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The payment reference payers quote when paying (bank narration or
     * mobile money account field). Generated on first visit to the payment page.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('payment_reference', 40)->nullable()->unique()->after('payment_status');
        });

        Schema::table('group_registrations', function (Blueprint $table) {
            $table->string('payment_reference', 40)->nullable()->unique()->after('payment_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn('payment_reference');
        });

        Schema::table('group_registrations', function (Blueprint $table) {
            $table->dropUnique(['payment_reference']);
            $table->dropColumn('payment_reference');
        });
    }
};
