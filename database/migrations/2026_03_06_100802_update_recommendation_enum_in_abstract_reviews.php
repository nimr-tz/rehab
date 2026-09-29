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
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE abstract_reviews MODIFY recommendation ENUM('accept_oral', 'accept_poster', 'accept', 'accept_with_revisions', 'minor_revisions', 'major_revisions', 'reject')");
        } elseif ($driver === 'sqlite') {
            $row = \Illuminate\Support\Facades\DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name='abstract_reviews'");
            if ($row) {
                $sql = $row->sql;
                // Replace table name at the start of the CREATE TABLE statement only
                $sql = preg_replace('/CREATE TABLE\s+"abstract_reviews"\s*\(/', 'CREATE TABLE "abstract_reviews_new" (', $sql);
                $sql = preg_replace('/CREATE TABLE\s+abstract_reviews\s*\(/', 'CREATE TABLE "abstract_reviews_new" (', $sql);
                // Expand the CHECK constraint to include accept_with_revisions
                $sql = str_replace(
                    'recommendation IN ("accept_oral", "accept_poster", "accept", "minor_revisions", "major_revisions", "reject")',
                    'recommendation IN ("accept_oral", "accept_poster", "accept", "accept_with_revisions", "minor_revisions", "major_revisions", "reject")',
                    $sql
                );
                \Illuminate\Support\Facades\DB::statement('DROP TABLE IF EXISTS abstract_reviews_new');
                \Illuminate\Support\Facades\DB::statement($sql);
                \Illuminate\Support\Facades\DB::statement('INSERT INTO abstract_reviews_new SELECT * FROM abstract_reviews');
                \Illuminate\Support\Facades\DB::statement('DROP TABLE abstract_reviews');
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE abstract_reviews_new RENAME TO abstract_reviews');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'mysql') {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE abstract_reviews MODIFY recommendation ENUM('accept_oral', 'accept_poster', 'accept', 'minor_revisions', 'major_revisions', 'reject')");
        }
    }
};
