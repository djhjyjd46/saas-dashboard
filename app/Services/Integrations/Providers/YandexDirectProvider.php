<?php

namespace App\Services\Integrations\Providers;

use App\Models\Integration;
use App\Models\AdCampaign;
use App\Models\AdStat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YandexDirectProvider
{
    protected Integration $integration;
    protected string $url = "https://api.direct.yandex.com/json/v5/";

    public function setIntegration(Integration $integration): self
    {
        $this->integration = $integration;
        return $this;
    }

    public function syncCampaigns(): void
    {
        $token = $this->integration->credentials['token'];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept-Language' => 'ru'
        ])->post($this->url . 'campaigns', [
            'method' => 'get',
            'params' => [
                'SelectionCriteria' => [],
                'FieldNames' => ['Id', 'Name']
            ]
        ]);

        if ($response->failed() || isset($response->json()['error'])) {
            Log::error('Yandex API Error (Campaigns)', ['response' => $response->json()]);
            return;
        }

        $campaigns = $response->json()['result']['Campaigns'] ?? [];

        foreach ($campaigns as $camp) {
            AdCampaign::updateOrCreate(
                [
                    'tenant_id' => $this->integration->tenant_id,
                    'external_id' => (string)$camp['Id']
                ],
                [
                    'name' => $camp['Name'],
                    'source' => 'yandex'
                ]
            );
        }
    }

    public function syncStats(int $days = 7): void
    {
        $token = $this->integration->credentials['token'];
        $campaignIds = AdCampaign::where('tenant_id', $this->integration->tenant_id)
            ->where('source', 'yandex')
            ->pluck('external_id')
            ->toArray();

        if (empty($campaignIds)) return;

        $reportsUrl = "https://api.direct.yandex.com/v5/reports";
        $dateFrom = now()->subDays($days)->format('Y-m-d');
        $dateTo = now()->format('Y-m-d');

        $reportDef = [
            "params" => [
                "SelectionCriteria" => [
                    "Filter" => [
                        ["Field" => "CampaignId", "Operator" => "IN", "Values" => $campaignIds]
                    ],
                    "DateFrom" => $dateFrom,
                    "DateTo" => $dateTo,
                ],
                "FieldNames" => ["Date", "CampaignId", "Impressions", "Clicks", "Cost"],
                "ReportName" => "SaaSReport_" . uniqid(),
                "ReportType" => "CAMPAIGN_PERFORMANCE_REPORT",
                "DateRangeType" => "CUSTOM_DATE",
                "Format" => "TSV",
                "IncludeVAT" => "YES",
                "IncludeDiscount" => "YES"
            ]
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token",
            'Accept-Language' => 'ru',
            'returnMoneyInMicros' => 'false',
        ])->post($reportsUrl, $reportDef);

        if ($response->failed()) {
            Log::error('Yandex API Error (Stats)', ['body' => $response->body()]);
            return;
        }

        $lines = explode("\n", trim($response->body()));
        if (count($lines) <= 2) return;

        for ($i = 2; $i < count($lines); $i++) {
            $cols = explode("\t", trim($lines[$i]));
            if (count($cols) == 5 && is_numeric($cols[3])) {
                $campaign = AdCampaign::where('tenant_id', $this->integration->tenant_id)
                    ->where('external_id', $cols[1])
                    ->first();

                if ($campaign) {
                    AdStat::updateOrCreate(
                        [
                            'tenant_id' => $this->integration->tenant_id,
                            'ad_campaign_id' => $campaign->id,
                            'date' => $cols[0]
                        ],
                        [
                            'impressions' => (int)$cols[2],
                            'clicks' => (int)$cols[3],
                            'spend' => (float)$cols[4],
                        ]
                    );
                }
            }
        }
    }
}
