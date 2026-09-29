<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per payment attempt against a payable (individual registration,
     * group registration or sponsor invoice). The payable keeps a summary
     * payment_status; this table is the auditable record of how it was paid.
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->morphs('payable');
            $table->string('method', 30);               // bank_transfer | mobile_money
            $table->string('provider', 50)->nullable(); // mobile money operator key
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3);
            $table->string('payer_name')->nullable();
            $table->string('payer_phone', 30)->nullable();
            // Reference issued by the bank / operator (slip number, transaction ID).
            $table->string('external_reference', 100)->nullable()->index();
            $table->string('proof_path')->nullable();
            $table->string('status', 20)->default('submitted')->index();
            $table->timestamp('paid_on')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable();
            // Raw request/response data from automated gateways.
            $table->json('gateway_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
