<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AmoCrmAuthControllerTest extends TestCase
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
        
        // This is usually handled by Tenancy/TenantManager in real app, let's just make sure we act as admin.
        $this->actingAs($this->admin);
    }

    public function test_redirects_with_error_if_no_integration_found()
    {
        $response = $this->get(route('integrations.amocrm', ['id' => 999]));
        
        $response->assertRedirect(route('integrations'));
        $response->assertSessionHas('status', 'Ошибка: интеграция не найдена.');
    }

    public function test_redirects_to_amocrm_login()
    {
        $integration = Integration::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'amocrm',
            'credentials' => [
                'client_id' => 'test-client',
                'domain' => 'test.amocrm.ru',
            ],
            'is_active' => false,
        ]);

        $response = $this->get(route('integrations.amocrm', ['id' => $integration->id]));

        $redirectUri = urlencode(route('amocrm.callback'));
        $state = urlencode(csrf_token() . '|' . $integration->id);

        $response->assertRedirectContains("https://test.amocrm.ru/oauth?client_id=test-client&state={$state}&redirect_uri=");
    }

    public function test_callback_updates_integration_with_tokens()
    {
        $integration = Integration::create([
            'tenant_id' => $this->tenant->id,
            'type' => 'amocrm',
            'credentials' => [
                'client_id' => 'test-client',
                'client_secret' => 'test-secret',
                'domain' => 'test.amocrm.ru',
            ],
            'is_active' => false,
        ]);

        Http::fake([
            'https://test.amocrm.ru/oauth2/access_token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'expires_in' => 86400,
            ], 200)
        ]);

        $state = csrf_token() . '|' . $integration->id;

        $response = $this->get(route('amocrm.callback', [
            'code' => 'auth-code',
            'state' => $state,
            'referer' => 'test.amocrm.ru',
        ]));

        $response->assertRedirect(route('integrations'));
        $response->assertSessionHas('status', 'AmoCRM успешно подключена!');

        $integration->refresh();
        $this->assertTrue($integration->is_active);
        $this->assertEquals('new-access-token', $integration->credentials['access_token']);
        $this->assertEquals('new-refresh-token', $integration->credentials['refresh_token']);
    }
}
