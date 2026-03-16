<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Integration;
use App\Services\Providers\YandexProvider;

class SyncYandexCampaigns extends Command
{
    protected $signature = 'sync:yandex-campaigns';
    protected $description = 'Sync campaigns from Yandex Direct for all active integrations';

    public function handle(): int
    {
        $integrations = Integration::withoutGlobalScopes()
            ->where('type', 'yandex')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->warn('No active Yandex integrations found.');
            return self::SUCCESS;
        }

        $this->info("Found {$integrations->count()} Yandex integration(s).");

        foreach ($integrations as $integration) {
            $this->info("Syncing tenant_id={$integration->tenant_id}...");
            try {
                (new YandexProvider($integration))->syncCampaigns();
                $this->info("  Done.");
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
            }
        }

        $this->info('Yandex campaigns sync completed.');
        return self::SUCCESS;
    }
}
