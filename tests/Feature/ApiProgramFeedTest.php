<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiProgramFeedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['abstract_submissions', 'speakers', 'conference_sessions'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('conference_sessions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('session_type')->nullable();
            $table->string('room_location')->nullable();
            $table->text('description')->nullable();
            $table->string('speaker')->nullable();
            $table->unsignedBigInteger('speaker_id')->nullable();
            $table->json('schedule_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('speakers', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });

        Schema::create('abstract_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->string('status')->default('draft');
            $table->string('title')->nullable();
            $table->string('author_name')->nullable();
            $table->string('author_institute')->nullable();
            $table->text('description')->nullable();
            $table->string('conference_code')->nullable();
            $table->timestamps();
        });

    }

    public function test_program_feed_survives_session_with_null_timestamps(): void
    {
        // A session row whose created_at / updated_at are NULL (timestamps
        // bypassed on insert) must not break the feed.
        $sessionId = DB::table('conference_sessions')->insertGetId([
            'name' => 'Panel: Rehabilitation Across the Life Course',
            'session_type' => 'panel',
            'room_location' => 'Main Hall',
            'schedule_days' => json_encode(['2026-06-11']),
            'start_time' => '10:00:00',
            'end_time' => '11:40:00',
            'is_active' => true,
            'created_at' => null,
            'updated_at' => null,
        ]);

        DB::table('abstract_submissions')->insert([
            'session_id' => $sessionId,
            'status' => 'accepted',
            'title' => 'Community rehabilitation outcomes',
            'author_name' => 'Jane Doe',
            'conference_code' => 'A-02',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/program');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Community rehabilitation outcomes']);
        $this->assertStringStartsWith('2026-06-11T10:00', $response->json('sessions.0.start_time'));
    }

    public function test_program_feed_handles_session_with_null_times_and_accepted_abstract(): void
    {
        $sessionId = DB::table('conference_sessions')->insertGetId([
            'name' => 'Oral Session',
            'session_type' => 'oral',
            'schedule_days' => json_encode(['2026-06-10']),
            'start_time' => null,
            'end_time' => null,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('abstract_submissions')->insert([
            'session_id' => $sessionId,
            'status' => 'accepted',
            'title' => 'Accepted abstract',
            'author_name' => 'Jane Doe',
            'conference_code' => 'A-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/program');

        $response->assertOk();
        $response->assertJsonFragment(['title' => 'Accepted abstract']);
        // Null times fall back to the controller defaults.
        $this->assertStringEndsWith('T09:00:00', $response->json('sessions.0.start_time'));
    }

    public function test_check_updates_does_not_500_with_null_last_sync(): void
    {
        DB::table('conference_sessions')->insert([
            'name' => 'Any session',
            'is_active' => true,
            'schedule_days' => json_encode(['2026-06-10']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/program/check-updates');

        $response->assertOk();
        $response->assertJsonStructure(['is_stale', 'latest_update']);
    }
}
