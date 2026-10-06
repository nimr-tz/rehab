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
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('programme_session_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->date('day')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['edition_id', 'day']);
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('album_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // the photographer
            $table->string('original_path');
            $table->string('display_path');
            $table->string('thumb_path');
            $table->string('original_name');
            $table->string('mime', 20);
            $table->unsignedBigInteger('bytes');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->char('checksum', 64);
            $table->dateTime('taken_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            $table->index(['album_id', 'published_at']);
            $table->index('checksum');
        });

        Schema::create('photo_removal_requests', function (Blueprint $table) {
            $table->id();
            // Kept after the photo is removed, as a record of what was asked and done.
            $table->foreignId('photo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('album_title');
            $table->string('name');
            $table->string('email');
            $table->text('reason')->nullable();
            $table->string('outcome', 10)->nullable(); // removed | kept
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Roles are otherwise seeded once per deployment, so the new role is added here.
        DB::table('roles')->insertOrIgnore([
            'name' => 'photographer', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now(),
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('photo_removal_requests');
        Schema::dropIfExists('photos');
        Schema::dropIfExists('albums');
        DB::table('roles')->where('name', 'photographer')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
