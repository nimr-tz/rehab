<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fee waivers, granted by finance: the whole fee or part of it. Every waiver
 * is kept, including withdrawn ones, so there is a full record of who waived
 * what and why. The registration holds the amount currently waived, so the
 * amount still due is its fee minus that.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->decimal('waived_amount', 12, 2)->default(0)->after('amount');
        });

        Schema::create('fee_waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2); // the part of the fee waived
            $table->string('reason', 20); // App\Enums\WaiverReason
            $table->text('note')->nullable();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('revoke_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_waivers');

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn('waived_amount');
        });
    }
};
