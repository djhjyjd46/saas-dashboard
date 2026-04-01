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

    protected function clientLogin(): string
    {
        return $this->integration->credentials['client_login'] ?? '';
    }

    protected function getHeaders(): array
    {
        $headers = [
            'Authorization'   => 'Bearer ' . $this->accessToken(),
            'Accept-Language' => 'ru',
        ];
        if ($login = $this->clientLogin()) {
            $headers['Client-Login'] = $login;
        }
        return $headers;
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
            throw new \RuntimeException('Yandex Direct: отсутствует access_token. Переподключите интеграцию.');
        }

        $response = Http::withoutVerifying()
            ->withHeaders($this->getHeaders())
            ->post($this->apiUrl . 'campaigns', [
                'method' => 'get',
                'params' => [
                    'SelectionCriteria' => (object)[],
                    'FieldNames'        => ['Id', 'Name', 'State', 'Status', 'Type', 'StatusPayment'],
                    'TextCampaignFieldNames' => ['TrackingParams'],
                    'DynamicTextCampaignFieldNames' => ['TrackingParams'],
                    'SmartCampaignFieldNames' => ['TrackingParams'],
                ],
            ]);

        if ($response->failed() || isset($response->json()['error'])) {
            $err = $response->json()['error'] ?? [];
            $msg = $err['error_detail'] ?? ($err['error_string'] ?? ($err['message'] ?? ('HTTP ' . $response->status())));
            Log::error('Yandex Campaigns API error', ['response' => $response->json()]);
            throw new \RuntimeException('Yandex Campaigns API: ' . $msg);
        }

        $campaigns = $response->json()['result']['Campaigns'] ?? [];

        foreach ($campaigns as $camp) {
            $externalId = (string) $camp['Id'];
            $trackingParams = $this->extractTrackingParams($camp);
            $utmCampaign = $this->extractUtmCampaignFromTrackingParams($trackingParams) ?: $externalId;

            AdCampaign::withoutGlobalScopes()->updateOrCreate(
                [
                    'tenant_id'   => $this->integration->tenant_id,
                    'user_id'     => $this->integration->user_id,
                    'external_id' => $externalId,
                ],
                [
                    'name'           => $camp['Name'],
                    'source'         => 'yandex',
                    'utm_campaign'   => $utmCampaign,
                    'tracking_params' => $trackingParams,
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
                            'TextCampaignFieldNames' => ['TrackingParams'],
                            'DynamicTextCampaignFieldNames' => ['TrackingParams'],
                            'SmartCampaignFieldNames' => ['TrackingParams'],
                        ],
                    ]);

                $foundInChunk = [];
                if (!$resp2->failed() && !isset($resp2->json()['error'])) {
                    foreach ($resp2->json()['result']['Campaigns'] ?? [] as $camp) {
                        $externalId = (string) $camp['Id'];
                        $foundInChunk[] = $externalId;
                        $trackingParams = $this->extractTrackingParams($camp);
                        $utmCampaign = $this->extractUtmCampaignFromTrackingParams($trackingParams) ?: $externalId;

                        AdCampaign::withoutGlobalScopes()
                            ->where('tenant_id', $this->integration->tenant_id)
                            ->where('user_id', $this->integration->user_id)
                            ->where('external_id', $externalId)
                            ->update([
                                'name'           => $camp['Name'],
                                'source'         => 'yandex',
                                'utm_campaign'   => $utmCampaign,
                                'tracking_params' => $trackingParams,
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

    private function extractTrackingParams(array $campaign): ?string
    {
        $trackingParams = $campaign['TextCampaign']['TrackingParams']
            ?? $campaign['DynamicTextCampaign']['TrackingParams']
            ?? $campaign['SmartCampaign']['TrackingParams']
            ?? $campaign['UnifiedCampaign']['TrackingParams']
            ?? $campaign['UnifiedPerformanceCampaign']['TrackingParams']
            ?? $campaign['CpmBannerCampaign']['TrackingParams']
            ?? $campaign['MobileAppCampaign']['TrackingParams']
            ?? $campaign['TrackingParams']
            ?? null;

        if ($trackingParams === null) {
            return null;
        }

        $trackingParams = trim((string) $trackingParams);
        return $trackingParams !== '' ? $trackingParams : null;
    }

    private function extractUtmCampaignFromTrackingParams(?string $trackingParams): ?string
    {
        if ($trackingParams === null || $trackingParams === '') {
            return null;
        }

        $query = $trackingParams;
        if (str_contains($query, '?')) {
            $parsedQuery = parse_url($query, PHP_URL_QUERY);
            if (is_string($parsedQuery) && $parsedQuery !== '') {
                $query = $parsedQuery;
            } else {
                $parts = explode('?', $query, 2);
                $query = $parts[1] ?? $query;
            }
        }

        $query = ltrim($query, '?');
        parse_str($query, $params);

        $utmCampaign = trim((string) ($params['utm_campaign'] ?? ''));
        return $utmCampaign !== '' ? $utmCampaign : null;
    }

    protected function inferStatusFromStats(array $externalIds): void
    {
        $threshold3days  = now()->subDays(3)->toDateString();
        $threshold14days = now()->subDays(14)->toDateString();

        $campModels = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $this->integration->tenant_id)
            ->where('user_id', $this->integration->user_id)
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
            throw new \RuntimeException('Yandex Direct: отсутствует access_token. Переподключите интеграцию.');
        }

        $goalIds    = array_values(array_map('intval', $this->integration->credentials['goal_ids'] ?? []));
        $counterId  = (int)($this->integration->credentials['metrika_counter_id'] ?? 0);
        $headers    = array_merge($this->getHeaders(), [
            'returnMoneyInMicros' => 'false',
            'skipReportHeader'    => 'true',
            'skipColumnHeader'    => 'true',
            'skipReportSummary'   => 'true',
        ]);

        $rows = $this->fetchReport(
            'Perf_' . uniqid(),
            ['Date', 'CampaignId', 'CampaignName', 'Impressions', 'Clicks', 'Cost', 'Conversions', 'Revenue'],
            'CAMPAIGN_PERFORMANCE_REPORT',
            $dateFrom, $dateTo, $headers,
            $goalIds
        );

        if ($rows === null) {
            throw new \RuntimeException('Yandex Direct Reports: не удалось получить отчёт. Проверьте токен и лимиты API.');
        }

        // Fetch per-goal conversions from Metrica Stat API if configured
        $metrikaConv = [];
        if ($counterId && !empty($goalIds)) {
            $metrikaConv = $this->fetchMetricaConversions($dateFrom, $dateTo, $counterId, $goalIds);
            Log::info('Metrica conversions fetched', [
                'tenant_id'  => $this->integration->tenant_id,
                'counter_id' => $counterId,
                'goal_id'    => $goalIds[0],
                'rows'       => count($metrikaConv),
            ]);
        }

        $synced = 0;
        $conversionsTotal = 0;
        $conversionsMissing = 0;

        foreach ($rows as $cols) {
            if (count($cols) < 8) continue;
            [$date, $campaignId, $campaignName, $impressions, $clicks, $cost, $rawConversions, $rawRevenue] = $cols;

            $metrikaData = $metrikaConv[$campaignId . '|' . $date] ?? null;
            if ($metrikaData) {
                $conversions = $metrikaData['conversions'] ?? 0;
            } elseif ($rawConversions === '--') {
                $conversions = 0;
                $conversionsMissing++;
            } else {
                $conversions = (int) $rawConversions;
            }

            $revenue = $rawRevenue === '--' ? 0.0 : (float) $rawRevenue;

            $campaign = AdCampaign::withoutGlobalScopes()
                ->where('tenant_id', $this->integration->tenant_id)
                ->where('user_id', $this->integration->user_id)
                ->where('external_id', (string) $campaignId)
                ->first();

            if (!$campaign) {
                $campaign = AdCampaign::withoutGlobalScopes()->create([
                    'tenant_id' => $this->integration->tenant_id,
                    'user_id' => $this->integration->user_id,
                    'external_id' => (string) $campaignId,
                    'name' => $campaignName,
                    'source' => 'yandex',
                    'utm_campaign' => $metrikaData['utm_campaign'] ?? null,
                ]);
            } elseif ($campaign->utm_campaign === null && isset($metrikaData['utm_campaign'])) {
                $campaign->update(['utm_campaign' => $metrikaData['utm_campaign']]);
            }
            $campaign->update(['last_synced_at' => now()]);

            AdStat::withoutGlobalScopes()->updateOrCreate(
                ['ad_campaign_id' => $campaign->id, 'date' => $date],
                [
                    'tenant_id'   => $this->integration->tenant_id,
                    'user_id'     => $this->integration->user_id,
                    'impressions' => (int) $impressions,
                    'clicks'      => (int) $clicks,
                    'spend'       => (float) $cost,
                    'conversions' => $conversions,
                    'revenue'     => $revenue,
                ]
            );

            $conversionsTotal += $conversions;
            $synced++;
        }

        Log::info('Yandex stats synced', [
            'tenant_id'         => $this->integration->tenant_id,
            'rows'              => $synced,
            'conversions_total' => $conversionsTotal,
            'missing_conv_rows' => $conversionsMissing,
        ]);

        // One-off discovery for campaigns missing UTMs (deep scan Metrica history 90 days)
        $this->discoverHistoricalUtms();
    }

    /**
     * Fetch per-goal conversions from Yandex Metrika Stat API.
     * Dimensions: date + Direct campaignID + Attributed UTMs. Metric: goal reaches.
     * Returns ['campaignId|YYYY-MM-DD' => ['conversions' => X, 'utm_campaign' => Y, ...]].
     */
    private function fetchMetricaConversions(
        string $dateFrom,
        string $dateTo,
        int    $counterId,
        array  $goalIds
    ): array {
        if (empty($goalIds)) return [];

        $metrics = [];
        foreach ($goalIds as $gid) {
            $metrics[] = "ym:s:goal{$gid}reaches";
        }
        $metricsStr = implode(',', $metrics);
        
        $result = [];
        $offset = 1;
        $limit  = 10000;

        do {
            $resp = Http::withoutVerifying()
                ->withToken($this->accessToken())
                ->get('https://api-metrika.yandex.net/stat/v1/data', [
                    'ids'        => $counterId,
                    'dimensions' => 'ym:s:date,ym:s:directCampaignID,ym:s:lastsignUTMCampaign',
                    'metrics'    => $metricsStr,
                    'date1'      => $dateFrom,
                    'date2'      => $dateTo,
                    'accuracy'   => 'full',
                    'limit'      => $limit,
                    'offset'     => $offset,
                ]);

            if (!$resp->successful()) {
                Log::error('Metrica Stat API error', [
                    'status' => $resp->status(),
                    'body'   => $resp->body(),
                ]);
                return [];
            }

            $data = $resp->json();
            foreach ($data['data'] ?? [] as $row) {
                $date       = $row['dimensions'][0]['name'] ?? null;
                $campaignId = $row['dimensions'][1]['id']   ?? null;
                $utmParam   = $row['dimensions'][2]['name'] ?? null;
                
                $totalConv = 0;
                foreach ($row['metrics'] as $mVal) {
                    $totalConv += (int) round($mVal ?? 0);
                }

                if ($date && $campaignId) {
                    $key = $campaignId . '|' . $date;
                    if (!isset($result[$key])) {
                        $result[$key] = ['conversions' => 0, 'utm_campaign' => null];
                    }
                    $result[$key]['conversions'] += $totalConv;
                    if ($utmParam && $utmParam !== '' && $utmParam !== 'none' && $utmParam !== '(none)') {
                        $result[$key]['utm_campaign'] = $utmParam;
                    }
                }
            }

            $total   = $data['total_rows'] ?? 0;
            $offset += $limit;
        } while ($offset <= $total);

        return $result;
    }

    /**
     * Deep Scan Metrica history (90 days) to find Campaign -> UTM mapping.
     */
    private function discoverHistoricalUtms(): void
    {
        // counter_id is stored in credentials['metrika_counter_id']
        $counterId = (int)($this->integration->credentials['metrika_counter_id'] ?? 0);
        if (!$counterId) {
            Log::info('discoverHistoricalUtms: metrika_counter_id not configured, skipping.');
            return;
        }

        $dateFrom = now()->subDays(90)->toDateString();
        $dateTo   = now()->toDateString();
        $limit    = 10000;

        $resp = Http::withoutVerifying()
            ->withToken($this->accessToken())
            ->get('https://api-metrika.yandex.net/stat/v1/data', [
                'ids'        => $counterId,
                'dimensions' => 'ym:s:directCampaignID,ym:s:utmCampaign',
                'metrics'    => 'ym:s:visits',
                'date1'      => $dateFrom,
                'date2'      => $dateTo,
                'accuracy'   => 'full',
                'limit'      => $limit,
            ]);

        Log::info('discoverHistoricalUtms response', [
            'status'     => $resp->status(),
            'data_count' => count($resp->json()['data'] ?? []),
        ]);

        if ($resp->successful()) {
            $updated = 0;
            foreach ($resp->json()['data'] ?? [] as $row) {
                $extId = $row['dimensions'][0]['id']   ?? null;
                $utm   = $row['dimensions'][1]['name'] ?? null;
                if ($extId && $utm && $utm !== '' && $utm !== 'none' && $utm !== '(none)') {
                    // Update campaigns that have no UTM, or whose UTM is just their own ID (garbage)
                    $rows = AdCampaign::withoutGlobalScopes()
                        ->where('tenant_id', $this->integration->tenant_id)
                        ->where('user_id', $this->integration->user_id)
                        ->where('external_id', (string) $extId)
                        ->where(function ($q) use ($extId) {
                            $q->whereNull('utm_campaign')
                              ->orWhere('utm_campaign', '')
                              ->orWhere('utm_campaign', (string) $extId);
                        })
                        ->update(['utm_campaign' => $utm]);
                    $updated += $rows;
                }
            }
            Log::info('discoverHistoricalUtms done', ['updated' => $updated]);
        } else {
            Log::error('discoverHistoricalUtms Metrica error', [
                'status' => $resp->status(),
                'body'   => mb_substr($resp->body(), 0, 500),
            ]);
        }
    }

    /**
     * Fetch a Yandex Direct report and return parsed rows (array of string[]).
     * Returns null on API error (already logged).
     */
    private function fetchReport(
        string $reportName,
        array  $fieldNames,
        string $reportType,
        string $dateFrom,
        string $dateTo,
        array  $headers,
        array  $goalIds = []
    ): ?array {
        $payload = [
            'params' => [
                'SelectionCriteria' => ['DateFrom' => $dateFrom, 'DateTo' => $dateTo],
                'FieldNames'        => $fieldNames,
                'ReportName'        => $reportName,
                'ReportType'        => $reportType,
                'DateRangeType'     => 'CUSTOM_DATE',
                'Format'            => 'TSV',
                'IncludeVAT'        => 'YES',
                'IncludeDiscount'   => 'YES',
            ],
        ];

        if (!empty($goalIds)) {
            $payload['params']['Goals'] = $goalIds;
        }

        $maxAttempts = 15;
        $response    = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $response = Http::withoutVerifying()
                ->timeout(60)
                ->withHeaders($headers)
                ->withBody(json_encode($payload), 'application/json')
                ->post($this->apiUrl . 'reports');

            $status = $response->status();

            if ($status === 200) break;

            if ($status === 201 || $status === 202) {
                $retryIn = (int) ($response->header('retryIn') ?: ($status === 201 ? 10 : 5));
                Log::info("Yandex report [{$reportName}] queued (HTTP {$status}), retry in {$retryIn}s");
                sleep($retryIn);
                continue;
            }

            Log::error("Yandex Reports API error [{$reportName}]", ['status' => $status, 'body' => $response->body()]);
            return null;
        }

        if (!$response || $response->status() !== 200) {
            Log::error("Yandex Reports: max attempts reached [{$reportName}]");
            return null;
        }

        $rows = [];
        $lineNum = 0;
        foreach (explode("\n", trim($response->body())) as $line) {
            $cols = explode("\t", trim($line));
            if (count($cols) >= 2) {
                if ($lineNum < 3) {
                    Log::debug("Yandex Report [{$reportName}] line {$lineNum}: " . json_encode($cols));
                }
                $rows[] = $cols;
                $lineNum++;
            }
        }

        return $rows;
    }
}
