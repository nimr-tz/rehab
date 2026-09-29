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
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('onsite_visitor_id')
                ->nullable()
                ->after('group_member_id')
                ->constrained('onsite_visitors')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\OnsiteVisitor::class);
            $table->dropColumn('onsite_visitor_id');
        });
    }
};
