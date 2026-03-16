<?php

namespace App\Services\Integrations\Providers;

use App\Models\Integration;
use App\Models\Lead;
use App\Models\Deal;
use App\Services\Integrations\Contracts\CrmProviderInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AmoCrmProvider implements CrmProviderInterface
{
    protected Integration $integration;

    public function setIntegration(Integration $integration): self
    {
        $this->integration = $integration;
        return $this;
    }

    public function syncLeads(int $days = 7): void
    {
        $credentials = $this->integration->credentials;
        $baseUrl = "https://{$credentials['base_domain']}/api/v4";
        $token = $credentials['access_token'];

        $since = now()->subDays($days)->timestamp;

        $response = Http::withToken($token)->get("$baseUrl/leads", [
            'filter[created_at][from]' => $since,
            'limit' => 250,
        ]);

        if ($response->failed()) {
            Log::error("AmoCRM Leads Sync Failed for tenant {$this->integration->tenant_id}", [
                'body' => $response->body()
            ]);
            return;
        }

        $leads = $response->json()['_embedded']['leads'] ?? [];

        foreach ($leads as $amoLead) {
            $lead = Lead::updateOrCreate(
                [
                    'tenant_id' => $this->integration->tenant_id,
                    'external_id' => $amoLead['id']
                ],
                [
                    'status' => $amoLead['status_id'],
                    'created_at_source' => Carbon::createFromTimestamp($amoLead['created_at']),
                    'meta_data' => $amoLead,
                ]
            );

            // If lead is closed (won), create a deal
            if ($amoLead['status_id'] == 142) { // Example 'Won' status ID
                Deal::updateOrCreate(
                    [
                        'tenant_id' => $this->integration->tenant_id,
                        'lead_id' => $lead->id
                    ],
                    [
                        'status' => 'won',
                        'revenue' => (float)$amoLead['price'],
                        'closed_at' => Carbon::createFromTimestamp($amoLead['updated_at']),
                    ]
                );
            }
        }
    }

    public function syncDeals(int $days = 7): void
    {
        // For amoCRM, deals are often just Leads with specific statuses
        // This is handled in syncLeads for simplicity in this version.
    }
}
