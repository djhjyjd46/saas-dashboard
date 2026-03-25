<?php

namespace Tests\Feature;

use Livewire\Livewire;
use App\Livewire\TrendChart;
use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Deal;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrendChartTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_calculates_trend_data_correctly()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($user);

        // Period: 3 days (March 1 - March 3)
        $start = '2026-03-01';
        $end   = '2026-03-03';

        $campaign = AdCampaign::factory()->create(['tenant_id' => $tenant->id]);

        // Spend for March 1: 100
        AdStat::factory()->create([
            'tenant_id' => $tenant->id,
            'ad_campaign_id' => $campaign->id,
            'date' => '2026-03-01',
            'spend' => 100
        ]);

        // Spend for March 2: 200
        AdStat::factory()->create([
            'tenant_id' => $tenant->id,
            'ad_campaign_id' => $campaign->id,
            'date' => '2026-03-02',
            'spend' => 200
        ]);

        // Income (revenue) for March 1: 500
        $lead = Lead::factory()->create([
            'tenant_id' => $tenant->id,
            'created_at_source' => Carbon::parse('2026-03-01 12:00:00')
        ]);
        Deal::factory()->create([
            'tenant_id' => $tenant->id,
            'lead_id' => $lead->id,
            'revenue' => 500,
            'status' => 'won'
        ]);

        Livewire::test(TrendChart::class, ['startDate' => $start, 'endDate' => $end])
            ->assertSet('spendData', json_encode([100.0, 200.0, 0.0]))
            ->assertSet('incomeData', json_encode([500.0, 0.0, 0.0]))
            ->assertSet('labels', json_encode(['01 мар', '02 мар', '03 мар']));
    }
}
