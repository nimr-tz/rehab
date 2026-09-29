<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The 2025 migration created these as MySQL ENUMs (e.g. 'probably_not',
     * 'definitely_not') but the form submits 'no' — strict MySQL rejects the
     * insert with "Data truncated". Convert all enum survey columns to plain
     * strings; allowed values are enforced by controller validation instead.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE conference_feedback MODIFY likely_to_attend_next VARCHAR(255) NULL");
            DB::statement("ALTER TABLE conference_feedback MODIFY likely_to_recommend VARCHAR(255) NULL");
            DB::statement("ALTER TABLE conference_feedback MODIFY conference_value VARCHAR(255) NULL");
            DB::statement("ALTER TABLE conference_feedback MODIFY age_group VARCHAR(255) NULL");
            DB::statement("ALTER TABLE conference_feedback MODIFY experience_level VARCHAR(255) NULL");
        } else {
            // SQLite created them as varchar + CHECK constraint; change()
            // rebuilds the table without the constraint.
            Schema::table('conference_feedback', function ($table) {
                $table->string('likely_to_attend_next')->nullable()->change();
                $table->string('likely_to_recommend')->nullable()->change();
                $table->string('conference_value')->nullable()->change();
                $table->string('age_group')->nullable()->change();
                $table->string('experience_level')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Intentionally left as strings — reverting to enums would re-break inserts.
    }
};
