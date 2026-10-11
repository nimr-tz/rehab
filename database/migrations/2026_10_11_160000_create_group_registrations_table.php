<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A leader registers several people and pays for all of them at once: one
 * invoice, the sum of each member's fee, in one currency. Each member has an
 * ordinary registration that belongs to the group; the payment belongs to
 * the group, so a payment now belongs to a registration or to a group.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('reference', 20)->unique(); // RH27-G0004, quoted on payments
            $table->char('currency', 3);
            $table->string('status', 24)->index(); // App\Enums\RegistrationStatus
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->foreignId('group_registration_id')->nullable()->after('registration_category_id')->constrained()->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('registration_id')->nullable()->change();
            $table->foreignId('group_registration_id')->nullable()->after('registration_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_registration_id');
        });

        Schema::table('registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_registration_id');
        });

        Schema::dropIfExists('group_registrations');
    }
};
