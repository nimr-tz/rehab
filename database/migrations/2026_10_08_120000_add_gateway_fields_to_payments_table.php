<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Payments taken through a gateway (M-Pesa) rather than submitted with a proof. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway', 20)->nullable()->after('provider'); // mpesa
            $table->string('gateway_reference', 40)->nullable()->unique()->after('gateway'); // our ThirdPartyConversationID
            $table->string('gateway_code', 20)->nullable()->after('gateway_reference'); // last result code, e.g. INS-0
            $table->string('gateway_message')->nullable()->after('gateway_code');
            $table->timestamp('gateway_checked_at')->nullable()->after('gateway_message');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_reference']);
            $table->dropColumn(['gateway', 'gateway_reference', 'gateway_code', 'gateway_message', 'gateway_checked_at']);
        });
    }
};
