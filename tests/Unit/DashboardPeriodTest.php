<?php

namespace Tests\Unit;

use App\Support\DashboardStats;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * The dashboard compares like with like: the same stretch of the period
 * before, cut at the same point.
 */
class DashboardPeriodTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_periods_compare_with_the_same_stretch_before(): void
    {
        CarbonImmutable::setTestNow('2026-03-31 15:00:00');

        [$start, $end] = DashboardStats::range('month');
        $this->assertSame(['2026-03-01 00:00:00', '2026-04-01 00:00:00'], [$start->toDateTimeString(), $end->toDateTimeString()]);
        // February has no 31st: the comparison stops at its last day
        $this->assertSame(['2026-02-01 00:00:00', '2026-02-28 15:00:00'], array_map(fn ($d) => $d->toDateTimeString(), DashboardStats::previous($start, $end)));

        [$start, $end] = DashboardStats::range('year');
        $this->assertSame(['2025-01-01 00:00:00', '2025-03-31 15:00:00'], array_map(fn ($d) => $d->toDateTimeString(), DashboardStats::previous($start, $end)));

        [$start, $end] = DashboardStats::range('today');
        $this->assertSame(['2026-03-30 00:00:00', '2026-03-30 15:00:00'], array_map(fn ($d) => $d->toDateTimeString(), DashboardStats::previous($start, $end)));
    }
}
