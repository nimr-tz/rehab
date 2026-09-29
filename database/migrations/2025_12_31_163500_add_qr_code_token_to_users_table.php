<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('qr_code_token', 64)->nullable()->unique()->after('checked_in_by');
            $table->boolean('badge_printed')->default(false)->after('qr_code_token');
            $table->timestamp('badge_printed_at')->nullable()->after('badge_printed');
        });

        // Generate QR tokens for all users with verified payment
        User::where('payment_status', 'verified')
            ->whereNull('qr_code_token')
            ->each(function ($user) {
                $user->update([
                    'qr_code_token' => config('conference.qr_prefix') . '-' . strtoupper(Str::random(12))
                ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['qr_code_token', 'badge_printed', 'badge_printed_at']);
        });
    }
};
