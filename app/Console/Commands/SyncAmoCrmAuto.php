<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Services\Integrations\Providers\AmoCrmProvider;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAmoCrmAuto extends Command
{
    protected $signature = 'sync:amocrm-auto';
    protected $description = 'Automatic AmoCRM leads sync for all active integrations';

    public function handle(): int
    {
        $integrations = Integration::withoutGlobalScopes()
            ->where('type', 'amocrm')
            ->where('is_active', true)
            ->get();

        if ($integrations->isEmpty()) {
            $this->info('No active AmoCRM integrations found.');
            return self::SUCCESS;
        }

        foreach ($integrations as $integration) {
            $this->info("Syncing AmoCRM for tenant_id={$integration->tenant_id}");
            try {
                (new AmoCrmProvider())
                    ->setIntegration($integration)
                    ->syncLeads(30);
                $this->info("  Success.");
            } catch (\Throwable $e) {
                $this->error("  Failed: {$e->getMessage()}");
                Log::error("Scheduled AmoCRM Sync Failed", [
                    'tenant_id' => $integration->tenant_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return self::SUCCESS;
    }
}
