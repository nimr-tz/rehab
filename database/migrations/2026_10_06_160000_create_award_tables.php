<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('award_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('kind', 12); // App\Enums\AwardKind: presentation | honour
            $table->string('presentation_type', 10)->nullable(); // oral | poster; null for both
            $table->boolean('students_only')->default(false);
            $table->unsignedTinyInteger('places')->default(1); // winners: 1 to 3
            $table->string('prize')->nullable();
            $table->date('nominations_close_on')->nullable(); // honours that take nominations
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamp('announced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('award_category_judge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->unique(['award_category_id', 'user_id']);
        });

        // A finalist (a shortlisted abstract) or a nominee for an honour.
        Schema::create('award_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('abstract_id')->nullable()->constrained('abstracts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // the recipient's account, when they have one
            $table->string('name');
            $table->string('institution')->nullable();
            $table->string('email')->nullable();
            $table->text('citation')->nullable();
            $table->foreignId('nominated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('place')->nullable(); // 1, 2 or 3 once the committee decides
            $table->timestamps();

            $table->unique(['award_category_id', 'abstract_id']);
        });

        Schema::create('award_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('award_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('judge_id')->constrained('users')->cascadeOnDelete();
            $table->json('scores'); // criterion => points, see config/awards.php
            $table->unsignedSmallInteger('total');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['award_entry_id', 'judge_id']);
        });

        // Roles are otherwise seeded once per deployment, so the new role is added here.
        DB::table('roles')->insertOrIgnore([
            'name' => 'judge', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('award_scores');
        Schema::dropIfExists('award_entries');
        Schema::dropIfExists('award_category_judge');
        Schema::dropIfExists('award_categories');
        DB::table('roles')->where('name', 'judge')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
