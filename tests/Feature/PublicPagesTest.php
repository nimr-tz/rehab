<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_home_page_shows_pending_details_as_to_be_announced(): void
    {
        config([
            'summit.year' => 2027,
            'summit.start_date' => null,
            'summit.end_date' => null,
            'summit.venue' => null,
            'summit.city' => null,
            'summit.theme' => null,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Rehabilitation Summit')
            ->assertSee('2027')
            ->assertSee('Exact dates to be announced')
            ->assertSee('Venue to be announced')
            ->assertSee('to be announced with the call for abstracts')
            ->assertDontSee('maps.google.com/maps', false)
            ->assertSee('<title>Rehabilitation Summit 2027 · Rehab Health</title>', false)
            ->assertDontSee(' description="', false); // layout props must not leak onto <body>
    }

    public function test_home_page_shows_the_details_once_they_are_set(): void
    {
        config([
            'summit.start_date' => '2027-09-15',
            'summit.end_date' => '2027-09-17',
            'summit.venue' => 'Example Convention Centre',
            'summit.city' => 'Dar es Salaam',
            'summit.theme' => 'Example theme',
            'summit.fees.0.amount' => 150000,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('15–17 September 2027')
            ->assertSee('Example Convention Centre')
            ->assertSee('Example theme')
            ->assertSee('TZS 150,000')
            ->assertSee('maps.google.com/maps', false)
            ->assertDontSee('Venue to be announced');
    }

    public function test_home_page_lists_every_configured_topic(): void
    {
        config(['summit.topics' => ['First topic' => 'AAA', 'Second topic' => 'BBB']]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Two topics, one theme')
            ->assertSeeInOrder(['AAA', 'First topic', 'BBB', 'Second topic']);
    }

    public function test_abstract_button_follows_the_submission_switch(): void
    {
        // The phrase also appears once in the FAQ; the hero button is the second.
        config(['summit.abstracts_open' => true]);
        $this->assertSame(2, substr_count($this->get(route('home'))->getContent(), 'Submit an abstract'));

        config(['summit.abstracts_open' => false]);
        $this->assertSame(1, substr_count($this->get(route('home'))->getContent(), 'Submit an abstract'));
    }

    public function test_sign_in_page_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Welcome back')
            ->assertSee('Email address')
            ->assertSee('Forgot password?');
    }
}
