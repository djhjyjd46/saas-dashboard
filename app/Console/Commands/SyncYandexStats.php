<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Services\Providers\YandexProvider;
use Carbon\Carbon;

class SyncYandexStats extends Command
{
    protected $signature = 'sync:yandex-stats {--days=90}';
    protected $description = 'Sync statistics from Yandex Direct for all active integrations';

    public function handle(): int
    {
        $days     = max(1, (int) $this->option('days'));
        $dateFrom = Carbon::now()->subDays($days)->format('Y-m-d');
        $dateTo   = Carbon::now()->format('Y-m-d');

        $integrations = Integration::withoutGlobalScopes()
            ->where('type', 'yandex')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn('No active Yandex integrations found.');
            return self::SUCCESS;
        }

        $this->info("Found {$integrations->count()} Yandex integration(s). Period: {$dateFrom} → {$dateTo}");

        foreach ($integrations as $integration) {
            $this->info("Syncing stats for tenant_id={$integration->tenant_id}...");
            try {
                (new YandexProvider($integration))->syncStats($dateFrom, $dateTo);
                $this->info("  Done.");
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $this->info('Yandex stats sync completed.');
        return self::SUCCESS;
    }
}
