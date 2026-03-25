<?php

namespace Tests\Unit;

use App\Models\AdCampaign;
use App\Models\Integration;
use App\Models\Tenant;
use App\Services\Providers\YandexProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YandexProviderUtmTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_saves_utm_campaign_from_direct_tracking_params(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = Integration::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'yandex',
            'credentials' => [
                'access_token' => 'test-access-token',
            ],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.direct.yandex.com/json/v5/campaigns' => Http::response([
                'result' => [
                    'Campaigns' => [
                        [
                            'Id' => 708247940,
                            'Name' => 'Brand Campaign',
                            'State' => 'ON',
                            'Status' => 'ACCEPTED',
                            'StatusPayment' => 'ALLOWED',
                            'TextCampaign' => [
                                'TrackingParams' => 'utm_source=yandex&utm_medium=cpc&utm_campaign=rf_novaya_metka',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        (new YandexProvider($integration))->syncCampaigns();

        $campaign = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('external_id', '708247940')
            ->first();

        $this->assertNotNull($campaign);
        $this->assertSame('rf_novaya_metka', $campaign->utm_campaign);
        $this->assertSame('utm_source=yandex&utm_medium=cpc&utm_campaign=rf_novaya_metka', $campaign->tracking_params);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_campaign_id_when_utm_campaign_is_missing(): void
    {
        $tenant = Tenant::factory()->create();
        $integration = Integration::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'yandex',
            'credentials' => [
                'access_token' => 'test-access-token',
            ],
            'is_active' => true,
        ]);

        Http::fake([
            'https://api.direct.yandex.com/json/v5/campaigns' => Http::response([
                'result' => [
                    'Campaigns' => [
                        [
                            'Id' => 123456789,
                            'Name' => 'No UTM Campaign',
                            'State' => 'ON',
                            'Status' => 'ACCEPTED',
                            'StatusPayment' => 'ALLOWED',
                            'TextCampaign' => [
                                'TrackingParams' => 'utm_source=yandex&utm_medium=cpc',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        (new YandexProvider($integration))->syncCampaigns();

        $campaign = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('external_id', '123456789')
            ->first();

        $this->assertNotNull($campaign);
        $this->assertSame('123456789', $campaign->utm_campaign);
        $this->assertSame('utm_source=yandex&utm_medium=cpc', $campaign->tracking_params);
    }
}
