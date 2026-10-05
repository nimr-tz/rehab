<?php

namespace Tests\Unit;

use App\Support\Summit;
use Tests\TestCase;

class SummitTest extends TestCase
{
    public function test_date_range_is_null_until_dates_are_set(): void
    {
        config(['summit.start_date' => null, 'summit.end_date' => null]);

        $this->assertNull(app(Summit::class)->dateRange());
    }

    public function test_date_range_formats(): void
    {
        $summit = app(Summit::class);

        config(['summit.start_date' => '2027-09-15', 'summit.end_date' => '2027-09-17']);
        $this->assertSame('15–17 September 2027', $summit->dateRange());

        config(['summit.start_date' => '2027-09-30', 'summit.end_date' => '2027-10-02']);
        $this->assertSame('30 Sep – 2 Oct 2027', $summit->dateRange());

        config(['summit.start_date' => '2027-09-15', 'summit.end_date' => null]);
        $this->assertSame('15 September 2027', $summit->dateRange());
    }

    public function test_venue_line_joins_venue_and_city(): void
    {
        config(['summit.venue' => null, 'summit.city' => null]);
        $this->assertNull(app(Summit::class)->venueLine());

        config(['summit.venue' => 'JNICC', 'summit.city' => 'Dar es Salaam']);
        $this->assertSame('JNICC, Dar es Salaam', app(Summit::class)->venueLine());
    }
}
