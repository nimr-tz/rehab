<?php

namespace Tests\Unit;

use App\Support\Rubric;
use Tests\TestCase;

class RubricTest extends TestCase
{
    public function test_the_criteria_add_up_to_one_hundred(): void
    {
        $this->assertSame(100, Rubric::max());
        $this->assertSame(['score_originality', 'score_technical', 'score_significance', 'score_clarity'], Rubric::fields());
    }

    public function test_totals_fall_into_the_committee_bands(): void
    {
        $this->assertSame('accept', Rubric::band(70)['key']);
        $this->assertSame('accept', Rubric::band(100)['key']);
        $this->assertSame('revise', Rubric::band(69)['key']);
        $this->assertSame('revise', Rubric::band(50)['key']);
        $this->assertSame('reject', Rubric::band(49.9)['key']);
        $this->assertSame('reject', Rubric::band(0)['key']);
        $this->assertNull(Rubric::band(null));
    }

    public function test_levels_describe_a_share_of_the_criterion(): void
    {
        $this->assertSame('Excellent', Rubric::level(18, 20));
        $this->assertSame('Strong', Rubric::level(28, 40));
        $this->assertSame('Adequate', Rubric::level(15, 30));
        $this->assertSame('Limited', Rubric::level(3, 10));
        $this->assertSame('Weak', Rubric::level(0, 10));
    }
}
