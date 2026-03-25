<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Integration;
use App\Livewire\IntegrationsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IntegrationsManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'domain' => 'test-tenant'
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_new_amo_integration()
    {
        $this->actingAs($this->admin);

        Livewire::test(IntegrationsManager::class)
            ->call('createAmoIntegration');

        $this->assertDatabaseHas('integrations', [
            'tenant_id' => $this->tenant->id,
            'type' => 'amocrm',
            'is_active' => false,
        ]);

        $integration = Integration::where('type', 'amocrm')->first();
        $this->assertNotNull($integration);
        $this->assertEquals('AmoCRM 1', $integration->credentials['name']);
    }

    public function test_can_save_amo_keys()
    {
        $this->actingAs($this->admin);

        $integration = Integration::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'amocrm',
            'credentials' => ['name' => 'AmoCRM 1'],
            'is_active' => false,
        ]);

        Livewire::test(IntegrationsManager::class)
            ->set('amoIntegrations.0.name', 'My Integration')
            ->set('amoIntegrations.0.domain', 'test.amocrm.ru')
            ->set('amoIntegrations.0.client_id', 'client-123')
            ->set('amoIntegrations.0.client_secret', 'secret-456')
            ->call('saveAmoKeys', 0)
            ->assertHasNoErrors();

        $integration->refresh();
        $this->assertEquals('My Integration', $integration->credentials['name']);
        $this->assertEquals('test.amocrm.ru', $integration->credentials['domain']);
        $this->assertEquals('client-123', $integration->credentials['client_id']);
        $this->assertEquals('secret-456', $integration->credentials['client_secret']);
    }

    public function test_can_delete_amo_integration()
    {
        $this->actingAs($this->admin);

        $integration = Integration::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'amocrm',
            'credentials' => ['name' => 'AmoCRM 1'],
            'is_active' => false,
        ]);

        Livewire::test(IntegrationsManager::class)
            ->call('deleteAmoIntegration', $integration->id);

        $this->assertDatabaseMissing('integrations', [
            'id' => $integration->id,
        ]);
    }
}
