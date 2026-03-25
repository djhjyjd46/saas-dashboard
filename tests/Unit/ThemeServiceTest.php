<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Tenant;
use App\Services\ThemeService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Mockery;

class ThemeServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_default_theme_for_admins()
    {
        $admin = new User(['role' => 'admin', 'theme' => 'gold']);
        $this->actingAs($admin);

        $tenantManager = Mockery::mock(TenantManager::class);
        $service = new ThemeService($tenantManager);

        // Даже если у админа стоит 'gold', сервис должен вернуть 'default' для админки (согласно логике в ThemeService)
        $viewPath = $service->getView('dashboard');
        $this->assertStringContainsString('themes.default', $viewPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_user_theme_for_regular_users()
    {
        $user = new User(['role' => 'user', 'theme' => 'gold']);
        $this->actingAs($user);

        $tenantManager = Mockery::mock(TenantManager::class);
        $service = new ThemeService($tenantManager);

        $viewPath = $service->getView('dashboard');

        // Если тема 'gold' существует, вернет ее. Если нет — упадет в fallback.
        // В юнит-тестах мы проверяем саму логику выбора строки.
        $this->assertStringContainsString('themes.gold', $viewPath);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_falls_back_to_default_if_user_has_no_theme()
    {
        $user = new User(['role' => 'user', 'theme' => null]);
        $this->actingAs($user);

        $tenantManager = Mockery::mock(TenantManager::class);
        $service = new ThemeService($tenantManager);

        $viewPath = $service->getView('dashboard');
        $this->assertStringContainsString('themes.default', $viewPath);
    }
}
