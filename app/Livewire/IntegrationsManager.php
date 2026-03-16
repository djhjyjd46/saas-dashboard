<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Integration;
use App\Services\ThemeService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;

class IntegrationsManager extends Component
{
    public $yandexToken = '';
    public $yandexCode = '';
    public $yandexStatus = '';
    public $statusMessage = '';

    protected ThemeService $themeService;
    protected TenantManager $tenantManager;

    public function boot(ThemeService $themeService, TenantManager $tenantManager)
    {
        $this->themeService  = $themeService;
        $this->tenantManager = $tenantManager;
    }

    public function mount()
    {
        $yandex = $this->getYandexIntegration();
        if ($yandex) {
            $this->yandexToken = $yandex->credentials['access_token'] ?? '';
        }
    }

    private function getYandexIntegration(): ?Integration
    {
        return Integration::where('type', 'yandex')->first();
    }

    private function getAmoIntegration(): ?Integration
    {
        return Integration::where('type', 'amocrm')->first();
    }

    public function syncYandex()
    {
        try {
            Artisan::call('sync:yandex-campaigns');
            Artisan::call('sync:yandex-stats', ['--days' => 30]);
            $this->yandexStatus = 'Синхронизация Яндекс.Директ завершена!';
        } catch (\Throwable $e) {
            $this->yandexStatus = 'Ошибка синхронизации: ' . $e->getMessage();
        }
    }

    public function syncAmo()
    {
        $this->statusMessage = 'Синхронизация AmoCRM будет добавлена в следующей версии.';
    }

    public function render()
    {
        $yandex = $this->getYandexIntegration();
        $amo    = $this->getAmoIntegration();

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.integrations-manager'), [
            'isYandexConnected' => (bool) $yandex,
            'isAmoConnected'    => (bool) $amo,
            'amoLeadsCount'     => \App\Models\Lead::count(),
            'amoDealsCount'     => \App\Models\Deal::where('status', 'won')->count(),
        ])->layout($layout, ['header' => 'Интеграции']);
    }
}
