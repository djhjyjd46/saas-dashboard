<?php

namespace Tests\Feature\Integrations;

use App\Models\Integration;
use App\Models\Lead;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Integrations\Providers\AmoCrmProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Carbon\Carbon;

class AmoCrmQualificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_qualification_dates_from_events()
    {
        // 1. Setup
        $user = User::factory()->create();
        $tenant = Tenant::factory()->create();
        $integration = Integration::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'type' => 'amocrm',
            'credentials' => [
                'access_token' => 'fake_token',
                'base_domain' => 'test.amocrm.ru',
            ],
            'is_active' => true,
        ]);

        $lead = Lead::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'integration_id' => $integration->id,
            'external_id' => '12345',
            'status' => 'new',
            'created_at_source' => now()->subDays(5),
            'qualified_at' => null,
        ]);

        $qualDate = now()->subDays(2);
        
        // 2. Mock AmoCRM Events API
        Http::fake([
            '*/api/v4/events*' => Http::response([
                '_embedded' => [
                    'events' => [
                        [
                            'entity_id' => 12345,
                            'entity_type' => 'leads',
                            'type' => 'lead_status_changed',
                            'created_at' => $qualDate->timestamp,
                            'value_after' => [
                                [
                                    'lead_status' => [
                                        'id' => 73458306,
                                        'pipeline_id' => 9049414
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        // 3. Execution
        $provider = new AmoCrmProvider();
        $provider->setIntegration($integration);
        $provider->syncQualificationDates(10);

        // 4. Assertions
        $lead->refresh();
        $this->assertNotNull($lead->qualified_at);
        $this->assertEquals($qualDate->timestamp, $lead->qualified_at->timestamp);
    }
}
