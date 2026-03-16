<?php

namespace App\Services\Providers;

use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Integration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexProvider
{
    protected string $apiUrl = 'https://api.direct.yandex.com/json/v5/';

    public function __construct(protected Integration $integration) {}

    protected function accessToken(): string
    {
        return $this->integration->credentials['access_token'] ?? '';
    }

    protected function getHeaders(): array
    {
        return [
            'Authorization'   => 'Bearer ' . $this->accessToken(),
            'Accept-Language' => 'ru',
        ];
    }

    protected function mapCampaignStatus(string $state, string $status, string $paymentStatus = ''): string
    {
        if ($state === 'ARCHIVED')  return 'archived';
        if ($state === 'ENDED')     return 'ended';
        if ($state === 'SUSPENDED') return 'suspended';
        if ($state === 'OFF') {
            if (str_contains($status, 'DRAFT')) return 'draft';
            return 'paused';
        }
        if ($state === 'ON') {
            if ($paymentStatus === 'DISALLOWED') return 'stopped';
            if ($status === 'MODERATION') return 'moderation';
            if ($status === 'REJECTED')   return 'rejected';
            if ($status === 'DRAFT')      return 'draft';
            if ($status === 'ACCEPTED' && $paymentStatus === 'ALLOWED') return 'serving';
            return 'active';
        }
        return 'unknown';
    }

    public function syncCampaigns(): void
    {
        if (empty($this->accessToken())) {
            Log::warning('Yandex syncCampaigns: no access_token', ['integration' => $this->integration->id]);
            return;
        }

        $response = Http::withoutVerifying()
            ->withHeaders($this->getHeaders())
            ->post($this->apiUrl . 'campaigns', [
                'method' => 'get',
                'params' => [
                    'SelectionCriteria' => (object)[],
                    'FieldNames'        => ['Id', 'Name', 'State', 'Status', 'Type', 'StatusPayment'],
                ],
            ]);

        if ($response->failed() || isset($response->json()['error'])) {
            Log::error('Yandex Campaigns API error', ['response' => $response->json()]);
            return;
        }

        $campaigns = $response->json()['result']['Campaigns'] ?? [];

        foreach ($campaigns as $camp) {
            AdCampaign::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id'   => $this->integration->tenant_id,
                    'external_id' => (string) $camp['Id'],
                ],
                [
                    'name'           => $camp['Name'],
                    'source'         => 'yandex',
                    'status'         => $this->mapCampaignStatus(
                        $camp['State'] ?? '',
                        $camp['Status'] ?? '',
                        $camp['StatusPayment'] ?? ''
                    ),
                    'last_synced_at' => now(),
                ]
            );
        }

        // Phase 2: for campaigns in DB not returned by the main API call
        // (archived/suspended/sub-account campaigns may not appear in the default list)
        $syncedExternalIds = array_map('strval', array_column($campaigns, 'Id'));

        $allDbExternalIds = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $this->integration->tenant_id)
            ->pluck('external_id')
            ->filter()
            ->values()
            ->toArray();

        $unsyncedExternalIds = array_values(array_diff($allDbExternalIds, $syncedExternalIds));

        if (!empty($unsyncedExternalIds)) {
            $stillUnresolved = [];

            foreach (array_chunk($unsyncedExternalIds, 10000) as $chunk) {
                $resp2 = Http::withoutVerifying()
                    ->withHeaders($this->getHeaders())
                    ->post($this->apiUrl . 'campaigns', [
                        'method' => 'get',
                        'params' => [
                            'SelectionCriteria' => ['Ids' => array_map('intval', $chunk)],
                            'FieldNames'        => ['Id', 'Name', 'State', 'Status', 'StatusPayment'],
                        ],
                    ]);

                $foundInChunk = [];
                if (!$resp2->failed() && !isset($resp2->json()['error'])) {
                    foreach ($resp2->json()['result']['Campaigns'] ?? [] as $camp) {
                        $foundInChunk[] = (string) $camp['Id'];
                        AdCampaign::withoutGlobalScopes()
                            ->where('tenant_id', $this->integration->tenant_id)
                            ->where('external_id', (string) $camp['Id'])
                            ->update([
                                'name'           => $camp['Name'],
                                'source'         => 'yandex',
                                'status'         => $this->mapCampaignStatus(
                                    $camp['State'] ?? '',
                                    $camp['Status'] ?? '',
                                    $camp['StatusPayment'] ?? ''
                                ),
                                'last_synced_at' => now(),
                            ]);
                    }
                }

                $missing = array_values(array_diff($chunk, $foundInChunk));
                $stillUnresolved = array_merge($stillUnresolved, $missing);
            }

            if (!empty($stillUnresolved)) {
                $this->inferStatusFromStats($stillUnresolved);
            }
        }

        Log::info('Yandex campaigns synced', [
            'tenant_id'  => $this->integration->tenant_id,
            'count'      => count($campaigns),
            'phase2'     => count($unsyncedExternalIds ?? []),
        ]);
    }

    protected function inferStatusFromStats(array $externalIds): void
    {
        $threshold3days  = now()->subDays(3)->toDateString();
        $threshold14days = now()->subDays(14)->toDateString();

        $campModels = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $this->integration->tenant_id)
            ->whereIn('external_id', $externalIds)
            ->get();

        foreach ($campModels as $camp) {
            $recentImpressions = AdStat::where('ad_campaign_id', $camp->id)
                ->where('date', '>=', $threshold3days)
                ->where('impressions', '>', 0)
                ->exists();

            $anyRecentImpressions = AdStat::where('ad_campaign_id', $camp->id)
                ->where('date', '>=', $threshold14days)
                ->where('impressions', '>', 0)
                ->exists();

            $inferredStatus = match (true) {
                $recentImpressions    => 'serving',
                $anyRecentImpressions => 'active',
                default               => 'paused',
            };

            $camp->update([
                'status'         => $inferredStatus,
                'last_synced_at' => now(),
            ]);
        }
    }

    public function syncStats(string $dateFrom, string $dateTo): void
    {
        if (empty($this->accessToken())) {
            Log::warning('Yandex syncStats: no access_token', ['integration' => $this->integration->id]);
            return;
        }

        $reportName = 'Stats_t' . $this->integration->tenant_id . '_' . $dateFrom . '_' . $dateTo;

        $headers = array_merge($this->getHeaders(), [
            'returnMoneyInMicros' => 'false',
            'skipReportHeader'    => 'true',
            'skipColumnHeader'    => 'true',
            'skipReportSummary'   => 'true',
        ]);

        $payload = [
            'params' => [
                'SelectionCriteria' => ['DateFrom' => $dateFrom, 'DateTo' => $dateTo],
                'FieldNames'        => ['Date', 'CampaignId', 'CampaignName', 'Impressions', 'Clicks', 'Cost'],
                'ReportName'        => $reportName,
                'ReportType'        => 'CAMPAIGN_PERFORMANCE_REPORT',
                'DateRangeType'     => 'CUSTOM_DATE',
                'Format'            => 'TSV',
                'IncludeVAT'        => 'YES',
                'IncludeDiscount'   => 'YES',
            ],
        ];

        // Yandex Reports API: 200 = ready, 201 = queued, 202 = processing — retry with same report name
        $maxAttempts = 15;
        $response = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->withHeaders($headers)
                ->withBody(json_encode($payload), 'application/json')
                ->post($this->apiUrl . 'reports');

            $status = $response->status();

            if ($status === 200) {
                break;
            }

            if ($status === 201 || $status === 202) {
                $retryIn = (int) ($response->header('retryIn') ?: ($status === 201 ? 10 : 5));
                Log::info("Yandex report queued (HTTP {$status}), retry in {$retryIn}s", [
                    'attempt' => $attempt,
                    'tenant_id' => $this->integration->tenant_id,
                ]);
                sleep($retryIn);
                continue;
            }

            Log::error('Yandex Reports API error', ['status' => $status, 'body' => $response->body()]);
            return;
        }

        if (!$response || $response->status() !== 200) {
            Log::error('Yandex Reports: max attempts reached', ['tenant_id' => $this->integration->tenant_id]);
            return;
        }

        $lines = explode("\n", trim($response->body()));
        $synced = 0;

        foreach ($lines as $line) {
            $cols = explode("\t", trim($line));
            if (count($cols) < 6) continue;

            [$date, $campaignId, $campaignName, $impressions, $clicks, $cost] = $cols;

            $campaign = AdCampaign::withoutGlobalScopes()->firstOrCreate(
                [
                    'tenant_id'   => $this->integration->tenant_id,
                    'external_id' => (string) $campaignId,
                ],
                [
                    'name'   => $campaignName,
                    'source' => 'yandex',
                ]
            );

            AdStat::updateOrCreate(
                ['ad_campaign_id' => $campaign->id, 'date' => $date],
                [
                    'tenant_id'   => $this->integration->tenant_id,
                    'impressions' => (int) $impressions,
                    'clicks'      => (int) $clicks,
                    'spend'       => (float) $cost,
                ]
            );

            $synced++;
        }

        Log::info('Yandex stats synced', [
            'tenant_id' => $this->integration->tenant_id,
            'rows'      => $synced,
        ]);
    }
}
