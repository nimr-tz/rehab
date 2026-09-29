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
        Schema::table('users', function (Blueprint $table) {
            $table->string('registration_category')->nullable()->after('role');
            $table->enum('payment_status', ['pending', 'submitted', 'verified', 'rejected'])->default('pending')->after('registration_category');
            $table->timestamp('payment_verified_at')->nullable()->after('payment_status');
            $table->foreignId('payment_verified_by')->nullable()->constrained('users')->after('payment_verified_at');
            $table->text('payment_notes')->nullable()->after('payment_verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_verified_by');
            $table->dropColumn([
                'registration_category',
                'payment_status',
                'payment_verified_at',
                'payment_notes'
            ]);
        });
    }
};
