<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Integration;
use App\Services\ThemeService;
use App\Services\Sync\YandexSyncThrottleService;
use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;

class IntegrationsManager extends Component
{
    public $yandexToken = '';
    public $yandexClientLogin = ''; // Yandex Direct Client-Login (for trusted rep / доверенный представитель)
    public $yandexGoalIds = ''; // Comma-separated Metrica goal IDs (e.g. "12345,67890")
    public $yandexMetrikaCounterId = ''; // Yandex Metrica counter ID for per-goal conversion data
    public $yandexGoals = []; // Fetched goals list from Metrica API: [{id, name, type}]
    public $yandexStatus = '';
    public $statusMessage = '';

    public $amoIntegrations = []; // Array of AmoCRM integration models/arrays

    // New Integration dropdown/modal state if needed
    public $showAddMenu = false;

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
            $this->yandexToken       = $yandex->credentials['access_token'] ?? '';
            $this->yandexClientLogin = $yandex->credentials['client_login'] ?? '';
            $goalIds = $yandex->credentials['goal_ids'] ?? [];
            $this->yandexGoalIds = is_array($goalIds) ? implode(',', $goalIds) : (string) $goalIds;
            $this->yandexMetrikaCounterId = (string)($yandex->credentials['metrika_counter_id'] ?? '');
        }

        $this->loadAmoIntegrations();
    }

    private function loadAmoIntegrations()
    {
        $tenant = $this->tenantManager->getTenant();
        if ($tenant) {
            $this->amoIntegrations = Integration::where('tenant_id', $tenant->id)
                ->where('type', 'amocrm')
                ->get()
                ->map(function ($i) {
                    return [
                        'id' => $i->id,
                        'name' => $i->credentials['name'] ?? 'AmoCRM Интеграция',
                        'user_id' => $i->user_id,
                        'client_id' => $i->credentials['client_id'] ?? '',
                        'client_secret' => $i->credentials['client_secret'] ?? '',
                        'domain' => $i->credentials['domain'] ?? '',
                        'refresh_token' => $i->credentials['refresh_token'] ?? '',
                        'is_active' => $i->is_active,
                        'is_connected' => !empty($i->credentials['access_token']),
                        'leads_count' => \App\Models\Lead::where('integration_id', $i->id)->count(),
                        'deals_count' => \App\Models\Deal::where('integration_id', $i->id)->where('status', 'won')->count(),
                    ];
                })
                ->toArray();
        }
    }

    public function createAmoIntegration()
    {
        $tenant = $this->tenantManager->getTenant();
        if (!$tenant) return;

        Integration::create([
            'tenant_id' => $tenant->id,
            'user_id' => auth()->id(),
            'type' => 'amocrm',
            'credentials' => [
                'name' => 'AmoCRM ' . (count($this->amoIntegrations) + 1),
            ],
            'is_active' => false,
        ]);

        $this->loadAmoIntegrations();
        $this->showAddMenu = false;
    }

    public function deleteAmoIntegration($id)
    {
        $tenant = $this->tenantManager->getTenant();
        Integration::where('tenant_id', $tenant->id)
            ->where('user_id', auth()->id())
            ->where('id', $id)
            ->delete();
        $this->loadAmoIntegrations();
    }

    private function getYandexIntegration(): ?Integration
    {
        return Integration::where('type', 'yandex')
            ->where('user_id', auth()->id())
            ->first();
    }


    public function saveAmoKeys($index)
    {
        $tenant = $this->tenantManager->getTenant();
        $data = $this->amoIntegrations[$index];

        $integration = Integration::where('tenant_id', $tenant->id)->where('id', $data['id'])->first();
        if (!$integration) return;

        $credentials = $integration->credentials ?? [];
        $credentials['name']          = $data['name'] ?? 'AmoCRM';
        $credentials['client_id']     = $data['client_id'] ?? '';
        $credentials['client_secret'] = $data['client_secret'] ?? '';
        $credentials['domain']        = $data['domain'] ?? '';
        $credentials['refresh_token'] = $data['refresh_token'] ?? '';

        $integration->update([
            'credentials' => $credentials,
        ]);

        session()->flash('amo_keys_saved_' . $data['id'], 'Настройки сохранены. Теперь можно нажать "Подключить".');
        $this->loadAmoIntegrations();
    }

    public function saveYandexClientLogin()
    {
        $yandex = $this->getYandexIntegration();
        if (!$yandex) return;

        $login = trim($this->yandexClientLogin);
        $credentials = $yandex->credentials ?? [];
        $credentials['client_login'] = $login;
        $yandex->update(['credentials' => $credentials]);

        $this->yandexStatus = $login
            ? 'Логин рекламодателя сохранён: ' . $login
            : 'Логин рекламодателя очищен (используется собственный аккаунт).';
    }

    public function saveYandexGoalIds()
    {
        $yandex = $this->getYandexIntegration();
        if (!$yandex) return;

        $ids = array_values(array_filter(
            array_map('intval', preg_split('/[,\s]+/', trim($this->yandexGoalIds)))
        ));

        $credentials = $yandex->credentials ?? [];
        $credentials['goal_ids'] = $ids;
        $counterId = (int) trim($this->yandexMetrikaCounterId);
        if ($counterId) {
            $credentials['metrika_counter_id'] = $counterId;
        } else {
            unset($credentials['metrika_counter_id']);
        }
        $yandex->update(['credentials' => $credentials]);

        $parts = [];
        if ($ids) $parts[] = 'Цели: ' . implode(', ', $ids);
        if ($counterId) $parts[] = 'Счётчик: ' . $counterId;
        $this->yandexStatus = $parts ? 'Сохранено. ' . implode('. ', $parts) : 'Сохранено (без фильтрации по целям).';
    }

    /**
     * Fetch goals from Yandex Metrica Management API.
     * The "цели" shown in Yandex Direct UI are actually Metrica goals —
     * the same OAuth token works for both Direct and Metrica APIs.
     * 1. GET /management/v1/counters — all counters accessible to this token
     * 2. For each counter, GET /management/v1/counter/{id}/goals
     */
    public function fetchMetricaGoals()
    {
        $yandex = $this->getYandexIntegration();
        if (!$yandex) return;

        $token   = $yandex->credentials['access_token'] ?? '';
        $http    = \Illuminate\Support\Facades\Http::withoutVerifying();
        $baseHdr = ['Authorization' => 'Bearer ' . $token, 'Accept-Language' => 'ru'];
        $directUrl = 'https://api.direct.yandex.com/json/v5/campaigns';

        // ─── Step 1: list of client logins (agency accounts) ─────────────────
        // Use saved client_login only if explicitly set
        $savedLogin = trim($yandex->credentials['client_login'] ?? '');
        $hdrs = $savedLogin ? array_merge($baseHdr, ['Client-Login' => $savedLogin]) : $baseHdr;

        // ─── Step 2: collect Metrica counter IDs from campaigns ──────────────
        $counterIds = [];
        $resp = $http->withHeaders($hdrs)->post('https://api.direct.yandex.com/json/v5/campaigns', [
            'method' => 'get',
            'params' => [
                'SelectionCriteria'            => (object)[],
                'FieldNames'                   => ['Id', 'Type'],
                'TextCampaignFieldNames'        => ['CounterIds'],
                'SmartCampaignFieldNames'       => ['CounterId'],   // singular!
                'DynamicTextCampaignFieldNames' => ['CounterIds'],
            ],
        ]);

        \Illuminate\Support\Facades\Log::info('Direct campaigns for CounterIds', [
            'login'  => $savedLogin ?: 'self',
            'status' => $resp->status(),
            'body'   => mb_substr($resp->body(), 0, 800),
        ]);

        foreach ($resp->json('result.Campaigns') ?? [] as $camp) {
            $ids = $camp['TextCampaign']['CounterIds']['Items']
                ?? $camp['DynamicTextCampaign']['CounterIds']['Items']
                ?? [];
            if (!empty($camp['SmartCampaign']['CounterId'])) {
                $ids[] = $camp['SmartCampaign']['CounterId'];
            }
            foreach ($ids as $cid) {
                $counterIds[(int)$cid] = true;
            }
        }

        // ─── Step 3: get advertiser login for Metrica userLogin param ────────
        // For доверенный представитель, Metrica requires ?userLogin=<owner_login>
        // Try to resolve it: first from saved field, then from Direct /clients API.
        $metrikaUserLogin = $savedLogin;
        if (!$metrikaUserLogin) {
            $clientsResp = $http->withHeaders($baseHdr)
                ->post('https://api.direct.yandex.com/json/v5/clients', [
                    'method' => 'get',
                    'params' => ['FieldNames' => ['Login', 'ClientId']],
                ]);
            $metrikaUserLogin = $clientsResp->json('result.Clients.0.Login') ?? '';
            \Illuminate\Support\Facades\Log::info('Direct /clients for userLogin', [
                'status' => $clientsResp->status(),
                'login'  => $metrikaUserLogin,
            ]);
        }

        // ─── Step 4: fallback — Metrica /counters (non-agency or shared) ─────
        if (empty($counterIds)) {
            $mcResp = $http->withToken($token)
                ->get('https://api-metrika.yandex.net/management/v1/counters', ['per_page' => 100]);

            \Illuminate\Support\Facades\Log::info('Metrica /counters fallback', [
                'status' => $mcResp->status(),
                'body'   => mb_substr($mcResp->body(), 0, 500),
            ]);

            foreach ($mcResp->json('counters') ?? [] as $c) {
                $counterIds[(int)$c['id']] = true;
            }
        }

        if (empty($counterIds)) {
            $this->yandexStatus = 'Счётчики Метрики не найдены в кампаниях Директа.'
                . ' Убедитесь, что в настройках кампании указан счётчик Метрики,'
                . ' или введите ID цели вручную в поле ниже.';
            return;
        }

        // ─── Step 5: fetch goals for every counter ────────────────────────────
        $allGoals = [];
        foreach (array_keys($counterIds) as $cId) {
            // Try with userLogin first (required for доверенный представитель)
            $params = $metrikaUserLogin ? ['userLogin' => $metrikaUserLogin] : [];
            $r = $http->withToken($token)
                ->get("https://api-metrika.yandex.net/management/v1/counter/{$cId}/goals", $params);

            // Fallback without userLogin if needed
            if (!$r->successful() && $metrikaUserLogin) {
                $r = $http->withToken($token)
                    ->get("https://api-metrika.yandex.net/management/v1/counter/{$cId}/goals");
            }

            \Illuminate\Support\Facades\Log::info("Metrica goals counter={$cId}", [
                'userLogin' => $metrikaUserLogin ?: 'none',
                'status'    => $r->status(),
                'body'      => mb_substr($r->body(), 0, 400),
            ]);

            if (!$r->successful()) continue;

            foreach ($r->json('goals') ?? [] as $g) {
                $allGoals[$g['id']] = [
                    'id'      => $g['id'],
                    'name'    => $g['name'] ?? '—',
                    'type'    => $g['type'] ?? '—',
                    'counter' => $cId,
                ];
            }
        }

        if (empty($allGoals)) {
            $counterList = implode(', ', array_keys($counterIds));
            $this->yandexStatus = 'Счётчик найден: ' . $counterList . '. '
                . 'Нет доступа к целям — токен доверенного представителя не имеет прав на Метрику клиента. '
                . 'Введите ID цели вручную: откройте metrika.yandex.ru → счётчик ' . $counterList
                . ' → раздел «Цели» и скопируйте числовой ID нужной цели (например "Заявка").';
            return;
        }

        $this->yandexGoals = array_values($allGoals);
        $this->yandexStatus = 'Загружено ' . count($this->yandexGoals) . ' целей из '
            . count($counterIds) . ' счётчиков. Выберите нужные и сохраните.';
    }

    /**
     * Toggle a Metrica goal ID in the yandexGoalIds field.
     */
    public function toggleGoalId(int $goalId)
    {
        $ids = array_values(array_filter(
            array_map('intval', preg_split('/[,\s]+/', trim($this->yandexGoalIds)))
        ));

        if (in_array($goalId, $ids)) {
            $ids = array_values(array_filter($ids, fn($id) => $id !== $goalId));
        } else {
            $ids[] = $goalId;
        }

        $this->yandexGoalIds = implode(',', $ids);
    }

    public function syncYandex()
    {
        $tenant = $this->tenantManager->getTenant();
        if (!$tenant) {
            $this->yandexStatus = 'Тенант не определён.';
            return;
        }

        $throttle = app(YandexSyncThrottleService::class);
        $isAdmin = auth()->user()?->role === 'admin';
        if (!$isAdmin) {
            $remaining = $throttle->manualRemainingSeconds((int) $tenant->id);
            if ($remaining > 0) {
                $this->yandexStatus = 'Обновление уже запускалось недавно. Повторите через '
                    . $throttle->formatRemaining($remaining) . '.';
                return;
            }
        }

        try {
            $integrations = Integration::where('type', 'yandex')
                ->where('is_active', true)
                ->get();

            foreach ($integrations as $integration) {
                $provider = new \App\Services\Providers\YandexProvider($integration);
                $provider->syncCampaigns();
                $provider->syncStats(now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d'));
            }

            $throttle->markManualRun((int) $tenant->id);
            $this->yandexStatus = 'Синхронизация Яндекс.Директ завершена!';
        } catch (\Throwable $e) {
            $this->yandexStatus = 'Ошибка синхронизации: ' . $e->getMessage();
        }
    }

    public function syncAmo($integrationId)
    {
        $tenantId = $this->tenantManager->getTenantId();
        
        $integration = Integration::where('tenant_id', $tenantId)
            ->where('id', $integrationId)
            ->where('type', 'amocrm')
            ->first();

        if (!$integration) {
            $this->statusMessage = 'Интеграция не найдена.';
            return;
        }

        $integrationName = $integration->credentials['name'] ?? "#$integrationId";

        if (empty($integration->credentials['access_token'])) {
            $this->statusMessage = "AmoCRM '$integrationName': токен не найден. Нажмите 'Авторизовать'";
            return;
        }

        // Count leads before sync to compare after
        $countBefore = \App\Models\Lead::where('integration_id', $integrationId)->count();

        try {
            $provider = app(\App\Services\Integrations\Providers\AmoCrmProvider::class);
            $provider->setIntegration($integration);
            $result = $provider->syncLeads(30);

            $countAfter = \App\Models\Lead::where('integration_id', $integrationId)->count();
            $this->statusMessage = "AmoCRM '$integrationName': синхронизировано. Лидов в БД: $countAfter (было $countBefore).";
            $this->loadAmoIntegrations();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('AmoCRM Sync failed', [
                'tenant_id' => $tenantId,
                'integration_id' => $integrationId,
                'error' => $e->getMessage(),
            ]);
            $this->statusMessage = "Ошибка AmoCRM '$integrationName': " . $e->getMessage();
        }
    }


    public function render()
    {
        $yandex = $this->getYandexIntegration();
        $layout = $this->themeService->getView('layouts.app');
        $userId = auth()->id();
        $tenantId = $this->tenantManager->getTenantId();
        
        return view($this->themeService->getView('components.integrations-manager'), [
            'isYandexConnected' => (bool) ($yandex && ($yandex->credentials['access_token'] ?? false)),
            'isAmoConnected'    => (bool) Integration::where('tenant_id', $tenantId)
                ->where('user_id', $userId)
                ->where('type', 'amocrm')
                ->whereNotNull('credentials->access_token')
                ->exists(),
            'amoLeadsCount'     => \App\Models\Lead::where('user_id', $userId)->count(),
            'amoDealsCount'     => \App\Models\Deal::where('user_id', $userId)->where('status', 'won')->count(),
        ])->layout($layout, ['header' => 'Интеграции']);
    }
}
