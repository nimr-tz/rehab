<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A person attending one edition. The fee is copied in so later fee
        // changes never alter what someone was asked to pay.
        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('registration_category_id')->constrained()->restrictOnDelete();
            $table->string('reference', 20)->unique(); // RH27-000123, quoted on payments
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('status', 24)->index(); // App\Enums\RegistrationStatus
            $table->string('badge_name')->nullable();
            $table->string('passport_number', 30)->nullable();
            $table->boolean('needs_invitation_letter')->default(false);
            $table->string('dietary_needs')->nullable();
            $table->string('accessibility_needs')->nullable();
            $table->string('qr_token', 40)->unique();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('badge_printed_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamps();

            $table->unique(['edition_id', 'user_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_id')->constrained()->cascadeOnDelete();
            $table->string('method', 20); // bank_transfer | mobile_money
            $table->string('provider', 30)->nullable(); // mpesa, airtel_money, ...
            $table->char('currency', 3);
            $table->decimal('amount', 12, 2);
            $table->string('transaction_reference', 60);
            $table->string('payer_name');
            $table->string('payer_phone', 32)->nullable();
            $table->date('paid_on');
            $table->string('proof_path')->nullable(); // private disk
            $table->string('status', 20)->index(); // App\Enums\PaymentStatus
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('registrations');
    }
};
