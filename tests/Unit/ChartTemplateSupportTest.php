<?php

namespace Tests\Unit;

use App\Support\ChartTemplateSupport;
use Tests\TestCase;

class ChartTemplateSupportTest extends TestCase
{
    public function test_normalize_payload_applies_defaults(): void
    {
        $normalized = ChartTemplateSupport::normalizePayload([
            'name' => 'Test',
        ]);

        $this->assertSame('Test', $normalized['name']);
        $this->assertSame('bar', $normalized['config']['chartType']);
        $this->assertSame('volume', $normalized['config']['metric']);
        $this->assertSame(['squat', 'bench', 'deadlift'], $normalized['config']['series']);
    }

    public function test_normalize_payload_filters_invalid_series(): void
    {
        $normalized = ChartTemplateSupport::normalizePayload([
            'name' => 'Test',
            'series' => ['squat', 'invalid', 'bench'],
        ]);

        $this->assertSame(['squat', 'bench'], $normalized['config']['series']);
    }

    public function test_normalize_payload_swaps_week_range_when_inverted(): void
    {
        $normalized = ChartTemplateSupport::normalizePayload([
            'name' => 'Test',
            'filters' => [
                'weekFrom' => 8,
                'weekTo' => 3,
            ],
        ]);

        $this->assertSame(3, $normalized['config']['filters']['weekFrom']);
        $this->assertSame(8, $normalized['config']['filters']['weekTo']);
    }
}
