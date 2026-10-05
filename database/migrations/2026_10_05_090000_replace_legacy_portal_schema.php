<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/*
 | One-time switch from the previous Rehab Summit portal to this codebase.
 |
 | The deployed `rehab` database still holds the previous portal's tables, and
 | the new schema cannot install on top of them. Deploys run
 | `php artisan migrate --force` automatically, so the switch happens here.
 |
 | Nothing is deleted. When the previous portal's tables are present (and this
 | portal's are not), each one is renamed to legacy_<name>, its foreign keys
 | are dropped (MySQL constraint names must be unique per database), and the
 | old migration history moves to legacy_migrations. The migrations that
 | follow then build the new schema in the same run. The legacy tables can be
 | archived or dropped later by hand.
 |
 | On a fresh database, or once the switch has happened, this does nothing.
 */
return new class extends Migration
{
    public function up(): void
    {
        $legacy = Schema::hasTable('abstract_submissions') || Schema::hasTable('conference_sessions');

        if (! $legacy || Schema::hasTable('editions')) {
            return;
        }

        Schema::disableForeignKeyConstraints();

        $tables = collect(Schema::getTables())->pluck('name')
            ->reject(fn (string $name) => $name === 'migrations' || str_starts_with($name, 'legacy_'));

        foreach ($tables as $name) {
            foreach (Schema::getForeignKeys($name) as $foreignKey) {
                Schema::table($name, fn ($table) => $table->dropForeign($foreignKey['name']));
            }
        }

        foreach ($tables as $name) {
            Schema::rename($name, substr('legacy_'.$name, 0, 64));
        }

        // Keep the old history, and start a fresh one for this portal.
        Schema::rename('migrations', 'legacy_migrations');
        app('migration.repository')->createRepository();

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Restoring the previous portal means renaming the legacy_ tables back by hand.
    }
};
