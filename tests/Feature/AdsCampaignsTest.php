<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\AdCampaign;
use App\Models\AdStat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\AdsCampaigns;

class AdsCampaignsTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_renders_ads_campaigns_list()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($user);

        AdCampaign::factory()->count(3)->create(['tenant_id' => $tenant->id]);

        Livewire::test(AdsCampaigns::class)
            ->assertStatus(200)
            ->assertViewHas('totalCampaignsCount', 3)
            ->assertViewHas('groups', function ($groups) {
                return count($groups) > 0 && count($groups[0]['campaigns']) === 3;
            });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_filters_campaigns_by_search()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($user);

        AdCampaign::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Apple Campaign']);
        AdCampaign::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Google Ads']);

        Livewire::test(AdsCampaigns::class)
            ->set('search', 'Apple')
            ->assertViewHas('groups', function ($groups) {
                return count($groups) > 0 && count($groups[0]['campaigns']) === 1 && $groups[0]['campaigns']->first()->name === 'Apple Campaign';
            });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_sorts_campaigns_by_spend()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($user);

        $camp1 = AdCampaign::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Cheap']);
        $camp2 = AdCampaign::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Expensive']);

        AdStat::factory()->create(['tenant_id' => $tenant->id, 'ad_campaign_id' => $camp1->id, 'spend' => 10, 'date' => now()->format('Y-m-d')]);
        AdStat::factory()->create(['tenant_id' => $tenant->id, 'ad_campaign_id' => $camp2->id, 'spend' => 100, 'date' => now()->format('Y-m-d')]);

        // Default sort is spend desc
        Livewire::test(AdsCampaigns::class)
            ->assertViewHas('groups', function ($groups) {
                return count($groups) > 0 && $groups[0]['campaigns']->first()->name === 'Expensive';
            })
            ->call('sort', 'spend') // Toggle to asc
            ->assertViewHas('groups', function ($groups) {
                return count($groups) > 0 && $groups[0]['campaigns']->first()->name === 'Cheap';
            });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_open_and_close_campaign_details()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create(['tenant_id' => $tenant->id, 'role' => 'admin']);
        $this->actingAs($user);

        $campaign = AdCampaign::factory()->create(['tenant_id' => $tenant->id]);

        Livewire::test(AdsCampaigns::class)
            ->call('openCampaign', $campaign->id)
            ->assertSet('selectedCampaignId', $campaign->id)
            ->assertViewHas('selectedCampaign', function ($c) use ($campaign) {
                return $c->id === $campaign->id;
            })
            ->call('closeCampaign')
            ->assertSet('selectedCampaignId', null);
    }
}
