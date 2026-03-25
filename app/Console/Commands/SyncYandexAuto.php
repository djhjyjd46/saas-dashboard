<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Providers\YandexProvider;
use App\Services\Sync\YandexSyncThrottleService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncYandexAuto extends Command
{
    protected $signature = 'sync:yandex-auto {--force}';
    protected $description = 'Automatic throttled Yandex sync for all tenants (15 min interval per tenant)';

    public function handle(YandexSyncThrottleService $throttle): int
    {
        $force = $this->option('force');
        $integrations = Integration::withoutGlobalScopes()
            ->where('type', 'yandex')
            ->where('is_active', true)
            ->get()
            ->groupBy('tenant_id');

        if ($integrations->isEmpty()) {
            $this->info('No active Yandex integrations found.');
            return self::SUCCESS;
        }

        $dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $dateTo = Carbon::now()->format('Y-m-d');

        foreach ($integrations as $tenantId => $tenantIntegrations) {
            $tenantId = (int) $tenantId;
            if (!$force && !$throttle->isAutoDue($tenantId)) {
                continue;
            }

            if ($force) {
                $this->info("Forcing auto sync for tenant_id={$tenantId}");
            }

            $this->info("Auto sync tenant_id={$tenantId}");

            foreach ($tenantIntegrations as $integration) {
                try {
                    $provider = new YandexProvider($integration);
                    $this->info("  - Syncing campaigns...");
                    $provider->syncCampaigns();
                    $this->info("  - Syncing stats ({$dateFrom} to {$dateTo})...");
                    $provider->syncStats($dateFrom, $dateTo);
                } catch (\Throwable $e) {
                    $this->error("    Failed tenant_id={$tenantId}: {$e->getMessage()}");
                }
            }

            $this->info("  Sync for tenant_id={$tenantId} completed.");
            $throttle->markAutoRun($tenantId);
        }

        return self::SUCCESS;
    }
}
