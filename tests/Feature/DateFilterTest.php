<?php

namespace Tests\Feature;

use Livewire\Livewire;
use App\Livewire\DateFilter;
use Carbon\Carbon;
use Tests\TestCase;

class DateFilterTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_initializes_with_default_dates()
    {
        $expectedStart = Carbon::now()->subDays(30)->format('Y-m-d');
        $expectedEnd   = Carbon::now()->format('Y-m-d');

        Livewire::test(DateFilter::class)
            ->assertSet('startDate', $expectedStart)
            ->assertSet('endDate', $expectedEnd);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_dispatches_event_when_period_updated()
    {
        $start = '2026-03-01';
        $end   = '2026-03-07';

        Livewire::test(DateFilter::class)
            ->call('updatePeriod', $start, $end)
            ->assertSet('startDate', $start)
            ->assertSet('endDate', $end)
            ->assertDispatched('dateRangeUpdated', $start, $end);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_formats_date_correctly()
    {
        $start = '2026-05-01';
        $end   = '2026-05-31';

        // May 1 - May 31
        // Expected format: "1 мая — 31 мая 2026"

        Livewire::test(DateFilter::class)
            ->set('startDate', $start)
            ->set('endDate', $end)
            ->assertSee('1 мая')
            ->assertSee('31 мая 2026');
    }
}
