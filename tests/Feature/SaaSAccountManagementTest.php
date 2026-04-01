<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use App\Models\AdCampaign;
use App\Models\Lead;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Admin\Clients;
use App\Livewire\Client\Campaigns;
use App\Livewire\IntegrationsManager;
use App\Http\Controllers\AmoCrmAuthController;

class SaaSAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function only_admins_can_access_client_management()
    {
        $adminTenant = Tenant::factory()->create();
        $admin = User::factory()->create(['role' => 'admin', 'tenant_id' => $adminTenant->id]);

        $clientTenant = Tenant::factory()->create();
        $client = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        // Admin access
        $this->actingAs($admin)
            ->get('/admin/clients')
            ->assertStatus(200);

        // Client access (Expect 403 because of IsAdmin middleware)
        $this->actingAs($client)
            ->get('/admin/clients')
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function creating_a_new_client_automatically_creates_a_new_tenant()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $initialTenantCount = Tenant::count();

        Livewire::test(Clients::class)
            ->set('name', 'John Client')
            ->set('email', 'john@example.com')
            ->set('password', 'secret-pass')
            ->set('role', 'client')
            ->call('save');

        $this->assertEquals($initialTenantCount + 1, Tenant::count());
        $newClient = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($newClient->tenant_id);
        $this->assertNotEquals($admin->tenant_id, $newClient->tenant_id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function clients_only_see_their_assigned_campaigns()
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // These campaigns exist globally
        $camp1 = AdCampaign::factory()->create(['name' => 'Visible Cam', 'external_id' => 'EXT-1']);
        $camp2 = AdCampaign::factory()->create(['name' => 'Hidden Cam', 'external_id' => 'EXT-2']);

        // Create a client with a new tenant that only has EXT-1
        $clientTenant = Tenant::factory()->create([
            'settings' => ['allowed_external_ids' => ['EXT-1']]
        ]);
        $client = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $clientTenant->id
        ]);

        $this->actingAs($client);

        Livewire::test(Campaigns::class)
            ->assertSee('Visible Cam')
            ->assertDontSee('Hidden Cam');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function integrations_manager_saves_dynamic_amocrm_keys()
    {
        $tenant = Tenant::factory()->create();
        $client = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($client);

        $integration = Integration::create([
            'tenant_id' => $tenant->id,
            'user_id' => $client->id,
            'type' => 'amocrm',
            'is_active' => false,
            'credentials' => ['name' => 'Amo 1']
        ]);

        Livewire::test(IntegrationsManager::class)
            ->set('amoIntegrations.0.client_id', 'CLIENT-123')
            ->set('amoIntegrations.0.client_secret', 'SECRET-789')
            ->set('amoIntegrations.0.domain', 'test.amocrm.ru')
            ->call('saveAmoKeys', 0);

        $integration->refresh();
        $this->assertEquals('CLIENT-123', $integration->credentials['client_id']);
        $this->assertEquals('SECRET-789', $integration->credentials['client_secret']);
        $this->assertEquals('test.amocrm.ru', $integration->credentials['domain']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function amocrm_auth_redirect_uses_tenant_credentials()
    {
        $tenant = Tenant::factory()->create();
        $client = User::factory()->create(['tenant_id' => $tenant->id]);
        $this->actingAs($client);

        // First, no integration ID found - should redirect back with error
        $response = $this->get('/integrations/amocrm');
        $response->assertSessionHas('status', 'Ошибка: интеграция не найдена.');

        // Now save keys in DB
        $integration = Integration::create([
            'tenant_id' => $tenant->id,
            'type' => 'amocrm',
            'credentials' => [
                'client_id' => 'TENANT-ID',
                'client_secret' => 'TENANT-SECRET',
                'domain' => 'tenant.amocrm.ru'
            ],
            'is_active' => false
        ]);

        // Should now redirect with tenant client_id
        $response = $this->get('/integrations/amocrm?id=' . $integration->id);
        $response->assertStatus(302);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_save_category_mapping_in_client_panel()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clientTenant = Tenant::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        Livewire::test(Clients::class)
            ->call('openPanel', $clientUser->id)
            ->set('uiMapping', [
                ['name' => 'Search Group', 'campaigns' => '1001, 1002'],
                ['name' => 'Display Group', 'campaigns' => '2001']
            ])
            ->call('saveCampaigns');

        $clientUser->refresh();
        $mapping = $clientUser->settings['category_mapping'];

        $this->assertArrayHasKey('Search Group', $mapping);
        $this->assertContains('1001', $mapping['Search Group']);
        $this->assertContains('1002', $mapping['Search Group']);

        $this->assertArrayHasKey('Display Group', $mapping);
        $this->assertContains('2001', $mapping['Display Group']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_can_expand_marker_tokens_into_campaign_ids_in_client_panel()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clientTenant = Tenant::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        AdCampaign::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'external_id' => 'EXT-VUZ-1',
            'name' => 'Поиск _glavnaya_vuz Минск',
        ]);
        AdCampaign::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'external_id' => 'EXT-VUZ-2',
            'name' => 'РСЯ _GLAVNAYA_VUZ Брест',
        ]);
        AdCampaign::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'external_id' => 'EXT-OTHER',
            'name' => 'Обычная кампания без маркера',
        ]);

        Livewire::test(Clients::class)
            ->call('openPanel', $clientUser->id)
            ->set('uiMapping', [
                ['name' => 'VUZ Group', 'campaigns' => '_glavnaya_vuz, MANUAL-1001'],
            ])
            ->call('saveCampaigns');

        $clientUser->refresh();
        $mapping = $clientUser->settings['category_mapping'];
        $allowed = $clientUser->settings['allowed_external_ids'];

        $this->assertArrayHasKey('VUZ Group', $mapping);
        $this->assertContains('MANUAL-1001', $mapping['VUZ Group']);
        $this->assertContains('EXT-VUZ-1', $mapping['VUZ Group']);
        $this->assertContains('EXT-VUZ-2', $mapping['VUZ Group']);
        $this->assertNotContains('EXT-OTHER', $mapping['VUZ Group']);

        $this->assertContains('MANUAL-1001', $allowed);
        $this->assertContains('EXT-VUZ-1', $allowed);
        $this->assertContains('EXT-VUZ-2', $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_keeps_category_row_even_when_marker_matches_nothing()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clientTenant = Tenant::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        Livewire::test(Clients::class)
            ->call('openPanel', $clientUser->id)
            ->set('uiMapping', [
                ['name' => 'New Group', 'campaigns' => '_no_such_marker'],
            ])
            ->call('saveCampaigns');

        $clientUser->refresh();
        $uiMapping = $clientUser->settings['category_mapping_ui'] ?? [];
        $mapping = $clientUser->settings['category_mapping'] ?? [];
        $allowed = $clientUser->settings['allowed_external_ids'] ?? [];

        $this->assertArrayHasKey('New Group', $uiMapping);
        $this->assertEquals('_no_such_marker', $uiMapping['New Group']);

        $this->assertArrayHasKey('New Group', $mapping);
        $this->assertEquals([], $mapping['New Group']);

        $this->assertEquals([], $allowed);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_marker_matches_campaign_name_without_underscore()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clientTenant = Tenant::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        AdCampaign::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'external_id' => 'EXT-VUZ-PLAIN',
            'name' => 'Поиск glavnaya vuz Минск',
        ]);

        Livewire::test(Clients::class)
            ->call('openPanel', $clientUser->id)
            ->set('uiMapping', [
                ['name' => 'VUZ Group', 'campaigns' => '_glavnaya_vuz'],
            ])
            ->call('saveCampaigns');

        $clientUser->refresh();
        $mapping = $clientUser->settings['category_mapping'] ?? [];

        $this->assertArrayHasKey('VUZ Group', $mapping);
        $this->assertContains('EXT-VUZ-PLAIN', $mapping['VUZ Group']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function admin_marker_matches_campaign_by_utm_campaign_with_campaign_id_prefix()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $clientTenant = Tenant::factory()->create();
        $clientUser = User::factory()->create(['role' => 'client', 'tenant_id' => $clientTenant->id]);

        AdCampaign::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'external_id' => '777001',
            'name' => 'МК | Бренд запросы | 25-55 | рф_новая метка',
        ]);

        Lead::factory()->create([
            'tenant_id' => $admin->tenant_id,
            'meta_data' => [
                'utm_campaign' => '777001_glavnaya_vuz',
                'campaign_id' => '777001_glavnaya_vuz',
            ],
        ]);

        Livewire::test(Clients::class)
            ->call('openPanel', $clientUser->id)
            ->set('uiMapping', [
                ['name' => 'VUZ Group', 'campaigns' => '_glavnaya_vuz'],
            ])
            ->call('saveCampaigns');

        $clientUser->refresh();
        $mapping = $clientUser->settings['category_mapping'] ?? [];

        $this->assertArrayHasKey('VUZ Group', $mapping);
        $this->assertContains('777001', $mapping['VUZ Group']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function users_can_have_different_campaign_access_within_same_tenant()
    {
        $tenant = Tenant::factory()->create();

        $userA = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenant->id,
            'settings' => [
                'allowed_external_ids' => ['EXT-1'],
                'category_mapping' => ['A Group' => ['EXT-1']],
                'category_mapping_ui' => ['A Group' => 'EXT-1'],
            ],
        ]);

        $userB = User::factory()->create([
            'role' => 'client',
            'tenant_id' => $tenant->id,
            'settings' => [
                'allowed_external_ids' => ['EXT-2'],
                'category_mapping' => ['B Group' => ['EXT-2']],
                'category_mapping_ui' => ['B Group' => 'EXT-2'],
            ],
        ]);

        AdCampaign::factory()->create([
            'tenant_id' => $tenant->id,
            'external_id' => 'EXT-1',
            'name' => 'Campaign One',
        ]);
        AdCampaign::factory()->create([
            'tenant_id' => $tenant->id,
            'external_id' => 'EXT-2',
            'name' => 'Campaign Two',
        ]);

        $this->actingAs($userA);
        Livewire::test(Campaigns::class)
            ->assertSee('Campaign One')
            ->assertDontSee('Campaign Two');

        $this->actingAs($userB);
        Livewire::test(Campaigns::class)
            ->assertSee('Campaign Two')
            ->assertDontSee('Campaign One');
    }
}
