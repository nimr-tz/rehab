<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_print_logs', function (Blueprint $table) {
            $table->id();
            // entity_type: user | group_member | symposium_member | onsite_visitor
            $table->string('entity_type', 30);
            $table->unsignedBigInteger('entity_id');
            $table->string('entity_name');
            $table->string('entity_institution')->nullable();
            $table->string('entity_category')->nullable();
            $table->unsignedSmallInteger('print_number')->default(1); // 1 = first print, 2+ = reprint
            $table->timestamp('printed_at');
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
            $table->index('printed_at');
        });

        // Backfill existing badge_printed = true records as print_number 1
        $this->backfill('users',            'user',             'full_name',  'affiliation',    'registration_category', 'badge_printed_at');
        $this->backfill('group_members',    'group_member',     'full_name',  'institution',    'registration_category', 'badge_printed_at');
        $this->backfill('onsite_visitors',  'onsite_visitor',   'name',       'institution',    'badge_category',        'badge_printed_at');

        if (Schema::hasTable('symposium_members')) {
            $this->backfill('symposium_members', 'symposium_member', 'full_name', 'institution', 'role_in_symposium', 'badge_printed_at');
        }
    }

    private function backfill(string $table, string $type, string $nameCol, string $instCol, string $catCol, string $printedAtCol): void
    {
        \DB::table($table)
            ->where('badge_printed', true)
            ->whereNotNull($printedAtCol)
            ->orderBy('id')
            ->each(function ($row) use ($type, $nameCol, $instCol, $catCol, $printedAtCol) {
                \DB::table('badge_print_logs')->insert([
                    'entity_type'       => $type,
                    'entity_id'         => $row->id,
                    'entity_name'       => $row->$nameCol ?? '',
                    'entity_institution'=> $row->$instCol ?? null,
                    'entity_category'   => $row->$catCol ?? null,
                    'print_number'      => 1,
                    'printed_at'        => $row->$printedAtCol,
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('badge_print_logs');
    }
};
