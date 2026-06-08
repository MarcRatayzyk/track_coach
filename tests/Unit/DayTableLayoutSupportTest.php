<?php

namespace Tests\Unit;

use App\Support\DayTableLayoutSupport;
use PHPUnit\Framework\TestCase;

class DayTableLayoutSupportTest extends TestCase
{
    public function test_normalize_payload_filters_unknown_columns(): void
    {
        $normalized = DayTableLayoutSupport::normalizePayload([
            'name' => 'Test',
            'columns' => ['sets', 'unknown', 'load'],
            'exercise_mode' => 'split_lift',
            'load_mode' => 'rpe',
        ]);

        $this->assertSame(['sets', 'load'], $normalized['columns']);
        $this->assertSame('split_lift', $normalized['exercise_mode']);
        $this->assertSame('rpe', $normalized['load_mode']);
    }

    public function test_resolve_snapshot_falls_back_to_classic_layout(): void
    {
        $snapshot = DayTableLayoutSupport::resolveSnapshot(null);

        $this->assertSame([
            'columns' => ['section', 'sets', 'reps', 'load'],
            'exercise_mode' => 'name',
            'load_mode' => 'kg',
        ], $snapshot);
    }
}
