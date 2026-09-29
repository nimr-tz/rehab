<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GuestLayoutGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('conference_sessions');

        Schema::create('conference_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('session_chair_id')->nullable();
            $table->unsignedBigInteger('session_rapporteur_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_guest_can_render_feedback_page_without_layout_user_errors(): void
    {
        config()->set('conference.end_date', now()->subDay()->toDateString());

        $this->get(route('feedback.create'))
            ->assertOk()
            ->assertSee('Overall Conference Experience');
    }
}
