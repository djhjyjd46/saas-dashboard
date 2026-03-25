<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Lead;
use App\Models\Deal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Dashboard;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_redirects_unauthenticated_users()
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_the_dashboard_for_authenticated_users()
    {
        $tenant = \App\Models\Tenant::factory()->create(['active_theme' => 'default']);
        $user = \App\Models\User::factory()->create(['tenant_id' => $tenant->id, 'theme' => 'default']);

        $this->actingAs($user);

        Livewire::test(Dashboard::class)
            ->assertStatus(200)
            ->assertViewIs('themes.default.dashboard');
    }

    /** @test */
    public function it_calculates_correct_metrics()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($user);

        // Create campaign
        $campaign = AdCampaign::factory()->create([
            'tenant_id' => $tenant->id,
            'external_id' => '12345',
            'status' => 'active'
        ]);

        // Create stats for today (within default '7 дней' period)
        AdStat::factory()->create([
            'tenant_id' => $tenant->id,
            'ad_campaign_id' => $campaign->id,
            'date' => now()->format('Y-m-d'),
            'spend' => 1000,
            'clicks' => 100,
            'impressions' => 10000,
            'conversions' => 1,
            'revenue' => 5000
        ]);

        // Temporarily, Leads and Deals are bypassed entirely in Dashboard metrics
        // So we don't need to create them for now.

        Livewire::test(Dashboard::class)
            ->assertViewHas('stats', function ($stats) {
                return $stats['spend'] == 1000 &&
                    $stats['leads'] == 1 &&
                    $stats['deals'] == 0 &&
                    $stats['revenue'] == 5000 &&
                    $stats['cpl'] == 1000 &&
                    $stats['cps'] == 0;
            });
    }
}
