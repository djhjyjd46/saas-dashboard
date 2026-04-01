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

    public function syncLeads(int $days = 30): void
    {
        $credentials = $this->integration->credentials;
        $domain = $credentials['domain'] ?? ($credentials['base_domain'] ?? config('services.amocrm.base_domain'));
        $baseUrl = "https://{$domain}/api/v4";
        $token = $credentials['access_token'];

        $since = now()->subDays($days)->timestamp;
        $page = 1;
        $limit = 250;
        $tokenRefreshed = false;

        while (true) {
            $response = Http::withToken($token)->get("$baseUrl/leads", [
                'filter[created_at][from]' => $since,
                'limit' => $limit,
                'page' => $page,
                'with' => 'custom_fields_values',
            ]);

            // If 401 and we haven't tried refreshing yet — refresh and retry
            if ($response->status() === 401 && !$tokenRefreshed) {
                Log::warning("AmoCRM 401 for integration {$this->integration->id}, attempting token refresh...");
                $newToken = $this->refreshToken($domain, $credentials);
                if ($newToken) {
                    $token = $newToken;
                    $tokenRefreshed = true;
                    continue; // retry this page with new token
                } else {
                    Log::error("AmoCRM token refresh failed for integration {$this->integration->id}");
                    break;
                }
            }

            if ($response->failed()) {
                Log::error("AmoCRM Leads Sync Failed for tenant {$this->integration->tenant_id}", [
                    'integration_id' => $this->integration->id,
                    'domain' => $domain,
                    'page' => $page,
                    'http_status' => $response->status(),
                    'body' => substr($response->body(), 0, 1000),
                ]);
                break;
            }

            $leads = $response->json()['_embedded']['leads'] ?? [];
            Log::info("AmoCRM sync page={$page}", [
                'integration_id' => $this->integration->id,
                'domain' => $domain,
                'leads_received' => count($leads),
            ]);

            if (empty($leads)) {
                break;
            }

            foreach ($leads as $amoLead) {
                $customFields = $amoLead['custom_fields_values'] ?? [];
                $utmData = $this->parseUtm($customFields);
                $phone = $this->parsePhone($customFields);
                $metaData = array_merge($amoLead, $utmData, ['phone_parsed' => $phone]);

                $lead = Lead::withoutGlobalScopes()->updateOrCreate(
                    [
                        'tenant_id'      => $this->integration->tenant_id,
                        'user_id'        => $this->integration->user_id,
                        'integration_id' => $this->integration->id,
                        'external_id'    => $amoLead['id'],
                    ],
                    [
                        'lead_name'         => $amoLead['name'] ?? null,
                        'phone'             => $phone,
                        'status'            => $amoLead['status_id'],
                        'created_at_source' => Carbon::createFromTimestamp($amoLead['created_at']),
                        'meta_data'         => $metaData,
                    ]
                );

                if ($amoLead['status_id'] == 142) {
                    Deal::withoutGlobalScopes()->updateOrCreate(
                        [
                            'tenant_id'      => $this->integration->tenant_id,
                            'user_id'        => $this->integration->user_id,
                            'integration_id' => $this->integration->id,
                            'lead_id'        => $lead->id,
                        ],
                        [
                            'status'    => 'won',
                            'revenue'   => (float) $amoLead['price'],
                            'closed_at' => Carbon::createFromTimestamp($amoLead['updated_at']),
                        ]
                    );
                }
            }

            if (count($leads) < $limit) {
                break;
            }
            $page++;
        }

        $this->syncQualificationDates($days);
    }

    public function syncLeadHistory(Lead $lead): void
    {
        $credentials = $this->integration->credentials;
        $domain = $credentials['domain'] ?? ($credentials['base_domain'] ?? config('services.amocrm.base_domain'));
        $baseUrl = "https://{$domain}/api/v4";
        $token = $credentials['access_token'];

        $response = Http::withToken($token)->get("$baseUrl/events", [
            'filter[type]' => 'lead_status_changed',
            'filter[entity_id]' => (int)$lead->external_id,
        ]);

        if ($response->failed()) {
            return;
        }

        $events = $response->json()['_embedded']['events'] ?? [];
        foreach ($events as $event) {
            $newStatus = $event['value_after'][0]['lead_status']['id'] ?? null;
            if (!$newStatus) continue;

            \App\Models\LeadStatusHistory::firstOrCreate(
                [
                    'user_id' => $lead->user_id,
                    'lead_id' => $lead->id,
                    'status_id' => (string) $newStatus,
                    'changed_at' => \Illuminate\Support\Carbon::createFromTimestamp($event['created_at']),
                ],
                [
                    'tenant_id' => $lead->tenant_id,
                    'pipeline_id' => (string) ($event['value_after'][0]['lead_status']['pipeline_id'] ?? ''),
                ]
            );
        }
    }

    public function syncQualificationDates(int $days = 7): void
    {
        $credentials = $this->integration->credentials;
        $domain = $credentials['domain'] ?? ($credentials['base_domain'] ?? config('services.amocrm.base_domain'));
        $baseUrl = "https://{$domain}/api/v4";
        $token = $credentials['access_token'];

        $since = now()->subDays($days)->timestamp;
        
        // ID статуса "Квалификация пройдена" в основной воронке
        $qualStatusId = 73458306; 

        $response = Http::withToken($token)->get("$baseUrl/events", [
            'filter[type]' => 'lead_status_changed',
            'filter[created_at][from]' => $since,
            'limit' => 250,
        ]);

        if ($response->failed()) {
            return;
        }

        $events = $response->json()['_embedded']['events'] ?? [];
        foreach ($events as $event) {
            $newStatus = $event['value_after'][0]['lead_status']['id'] ?? null;
            $leadId = $event['entity_id'];
            
            // Find our lead
            $lead = Lead::withoutGlobalScopes()
                ->where('integration_id', $this->integration->id)
                ->where('external_id', (string) $leadId)
                ->first();

            if ($lead) {
                // 1. Update qualification date if it matches specific status
                if ($newStatus == $qualStatusId && !$lead->qualified_at) {
                    $lead->update(['qualified_at' => Carbon::createFromTimestamp($event['created_at'])]);
                }

                // 2. Track history transition
                \App\Models\LeadStatusHistory::firstOrCreate(
                    [
                        'lead_id' => $lead->id,
                        'status_id' => (string) $newStatus,
                        'changed_at' => Carbon::createFromTimestamp($event['created_at']),
                    ],
                    [
                        'tenant_id' => $lead->tenant_id,
                        'pipeline_id' => (string) ($event['value_after'][0]['lead_status']['pipeline_id'] ?? ''),
                    ]
                );
            }
        }
    }

    /**
     * Sync AmoCRM pipeline statuses (names and colors)
     */
    public function syncStatuses(): void
    {
        $credentials = $this->integration->credentials;
        $domain = $credentials['domain'] ?? ($credentials['base_domain'] ?? config('services.amocrm.base_domain'));
        $baseUrl = "https://{$domain}/api/v4";
        $token = $credentials['access_token'];

        $response = Http::withToken($token)->get("$baseUrl/leads/pipelines");
        
        // If 401, try to refresh once
        if ($response->status() === 401) {
             $newToken = $this->refreshToken($domain, $credentials);
             if ($newToken) {
                 $response = Http::withToken($newToken)->get("$baseUrl/leads/pipelines");
             }
        }

        if ($response->failed()) {
            return;
        }

        $pipelines = $response->json()['_embedded']['pipelines'] ?? [];
        foreach ($pipelines as $pipe) {
            $pipeId = (string) $pipe['id'];
            $statuses = $pipe['_embedded']['statuses'] ?? [];
            foreach ($statuses as $s) {
                \App\Models\CrmStatus::updateOrCreate([
                    'external_id' => (string) $s['id'],
                    'tenant_id' => $this->integration->tenant_id,
                    'pipeline_id' => $pipeId,
                ], [
                    'name' => $s['name'],
                    'color' => $s['color'] ?? null,
                ]);
            }
        }
    }

    private function parsePhone(array $fields): ?string
    {
        foreach ($fields as $field) {
            $code = strtoupper($field['field_code'] ?? '');
            $name = $field['field_name'] ?? '';
            
            // Try standard code, then keywords in English and Russian
            if ($code === 'PHONE' || stripos($name, 'phone') !== false || stripos($name, 'телефон') !== false) {
                return $field['values'][0]['value'] ?? null;
            }
        }
        return null;
    }

    /**
     * Refresh the AmoCRM OAuth access token using the stored refresh_token.
     * Saves the new tokens to the Integration model and returns the new access_token.
     */
    private function refreshToken(string $domain, array $credentials): ?string
    {
        $clientId     = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;
        $refreshToken = $credentials['refresh_token'] ?? null;

        if (!$clientId || !$clientSecret || !$refreshToken) {
            Log::error("AmoCRM token refresh: missing client_id/client_secret/refresh_token", [
                'integration_id' => $this->integration->id,
            ]);
            return null;
        }

        $response = Http::post("https://{$domain}/oauth2/access_token", [
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'refresh_token',
            'refresh_token' => $refreshToken,
            'redirect_uri'  => config('app.url') . '/integrations/amocrm/callback',
        ]);

        if ($response->failed()) {
            Log::error("AmoCRM token refresh HTTP failed", [
                'integration_id' => $this->integration->id,
                'status'         => $response->status(),
                'body'           => substr($response->body(), 0, 500),
            ]);
            return null;
        }

        $data = $response->json();
        $newAccessToken  = $data['access_token'] ?? null;
        $newRefreshToken = $data['refresh_token'] ?? null;

        if (!$newAccessToken) {
            Log::error("AmoCRM token refresh: no access_token in response", [
                'integration_id' => $this->integration->id,
                'body'           => substr($response->body(), 0, 500),
            ]);
            return null;
        }

        // Persist new tokens
        $creds = $this->integration->credentials;
        $creds['access_token']  = $newAccessToken;
        if ($newRefreshToken) {
            $creds['refresh_token'] = $newRefreshToken;
        }
        $this->integration->update(['credentials' => $creds]);

        Log::info("AmoCRM token refreshed successfully", ['integration_id' => $this->integration->id]);

        return $newAccessToken;
    }

    public function syncDeals(int $days = 7): void
    {
        // For amoCRM, deals are often just Leads with specific statuses
        // This is handled in syncLeads for simplicity in this version.
    }

    /**
     * Parse UTM parameters from amoCRM custom_fields_values array.
     * Returns top-level keys: utm_source, utm_medium, utm_campaign, utm_content, utm_term
     * AND campaign_id (alias for utm_campaign) for Yandex Direct attribution.
     */
    private function parseUtm(array $fields): array
    {
        $utm = [];
        foreach ($fields as $field) {
            $name  = mb_strtolower($field['field_name'] ?? '');
            $code  = strtolower($field['field_code'] ?? '');
            $value = $field['values'][0]['value'] ?? null;
            if ($value === null) continue;

            // Mapping for utm_source
            if (str_contains($name, 'utm_source') || $code === 'utm_source' || str_contains($name, 'источник')) {
                $utm['utm_source'] = $value;
            }
            
            // Mapping for utm_medium
            if (str_contains($name, 'utm_medium') || $code === 'utm_medium' || str_contains($name, 'тип трафика')) {
                $utm['utm_medium'] = $value;
            }

            // Mapping for utm_campaign / campaign_id
            if (
                str_contains($name, 'utm_campaign') || 
                $code === 'utm_campaign' || 
                str_contains($name, 'campaign id') || 
                str_contains($name, 'id кампании') ||
                str_contains($name, 'кампания')
            ) {
                $utm['utm_campaign'] = $value;
                $utm['campaign_id'] = $value;
            }

            if (str_contains($name, 'utm_content') || $code === 'utm_content') $utm['utm_content'] = $value;
            if (str_contains($name, 'utm_term') || $code === 'utm_term') $utm['utm_term'] = $value;

            // Mapping for Client IDs (Metrika, GA, etc.)
            if (str_contains($name, 'metrika') || str_contains($name, 'метрика') || str_contains($name, 'ym_client_id')) {
                $utm['metrika_id'] = $value;
            }
            if (str_contains($name, 'client_id') || str_contains($name, 'clientid') || str_contains($name, 'id клиента')) {
                $utm['client_id'] = $value;
            }
            if (str_contains($name, 'google_id') || $code === 'gacid' || str_contains($name, 'ga id')) {
                $utm['ga_id'] = $value;
            }
            if (str_contains($name, 'yclid')) {
                $utm['yclid'] = $value;
            }
        }
        return $utm;
    }
}
