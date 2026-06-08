<?php

namespace Tests\Unit;

use App\Support\GlPointsCalculator;
use PHPUnit\Framework\TestCase;

class GlPointsCalculatorTest extends TestCase
{
    public function test_calculates_gl_points_for_known_total(): void
    {
        $points = GlPointsCalculator::calculate(600, 93.0, 'male');

        $this->assertNotNull($points);
        $this->assertGreaterThan(70, $points);
        $this->assertLessThan(90, $points);
    }

    public function test_parses_bodyweight_from_weight_class(): void
    {
        $this->assertSame(72.0, GlPointsCalculator::bodyweightFromClass('72 kg'));
        $this->assertSame(93.0, GlPointsCalculator::bodyweightFromClass('93kg'));
        $this->assertNull(GlPointsCalculator::bodyweightFromClass(null));
    }

    public function test_returns_null_without_bodyweight_or_total(): void
    {
        $this->assertNull(GlPointsCalculator::calculate(0, 93.0));
        $this->assertNull(GlPointsCalculator::calculate(500, null));
    }
}
