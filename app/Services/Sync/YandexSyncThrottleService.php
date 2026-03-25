<?php

namespace App\Services\Sync;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class YandexSyncThrottleService
{
    public const MANUAL_COOLDOWN_SECONDS = 300; // 5 min
    public const AUTO_INTERVAL_SECONDS = 900;   // 15 min

    private function manualUntilKey(int $tenantId): string
    {
        return "sync:yandex:tenant:{$tenantId}:manual_until";
    }

    private function autoNextAtKey(int $tenantId): string
    {
        return "sync:yandex:tenant:{$tenantId}:auto_next_at";
    }

    public function manualRemainingSeconds(int $tenantId): int
    {
        $untilTs = (int) Cache::get($this->manualUntilKey($tenantId), 0);
        return max(0, $untilTs - time());
    }

    public function canRunManual(int $tenantId): bool
    {
        return $this->manualRemainingSeconds($tenantId) === 0;
    }

    public function isAutoDue(int $tenantId): bool
    {
        $nextTs = (int) Cache::get($this->autoNextAtKey($tenantId), 0);
        return $nextTs <= time();
    }

    public function markManualRun(int $tenantId): void
    {
        $manualUntil = time() + self::MANUAL_COOLDOWN_SECONDS;
        $autoNextAt  = time() + self::AUTO_INTERVAL_SECONDS;

        Cache::put($this->manualUntilKey($tenantId), $manualUntil, Carbon::now()->addDay());
        Cache::put($this->autoNextAtKey($tenantId), $autoNextAt, Carbon::now()->addDay());
    }

    public function markAutoRun(int $tenantId): void
    {
        $autoNextAt = time() + self::AUTO_INTERVAL_SECONDS;
        Cache::put($this->autoNextAtKey($tenantId), $autoNextAt, Carbon::now()->addDay());
    }

    public function formatRemaining(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        if ($minutes > 0) {
            return $minutes . ' мин ' . $secs . ' сек';
        }
        return $secs . ' сек';
    }
}
