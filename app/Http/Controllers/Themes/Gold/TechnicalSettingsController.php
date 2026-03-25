<?php

namespace App\Http\Controllers\Themes\Gold;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\Lead;
use App\Services\Tenancy\TenantManager;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class TechnicalSettingsController extends Controller
{
    public function index(TenantManager $tenantManager, ThemeService $themeService)
    {
        $user = auth()->user();

        // Strictly Gold theme only
        if (!$user || $user->theme !== 'gold') {
            abort(404);
        }

        $tenant = $tenantManager->getTenant();
        $uiMapping = $user->campaignSettings()['category_mapping_ui'] ?? [];

        $view = $themeService->getView('technical-settings');
        $layout = $themeService->getView('layouts.app');

        return view($view, [
            'ui_mapping' => $uiMapping,
            'layout' => $layout
        ]);
    }

    public function store(Request $request, TenantManager $tenantManager)
    {
        $user = auth()->user();
        if (!$user || $user->theme !== 'gold') {
            abort(404);
        }

        $tenant = $tenantManager->getTenant();
        $settings = is_array($user->settings) ? $user->settings : [];

        $names = $request->input('category_names', []);
        $campaignsLists = $request->input('category_campaigns', []);

        $mapping = [];
        $externalIds = [];
        $uiMapping = [];

        foreach ($names as $index => $name) {
            $name = trim($name);
            $idsStr = trim($campaignsLists[$index] ?? '');

            if ($name === '' || $idsStr === '') continue;

            // Keep the UI row exactly as entered, even if marker expansion finds no IDs.
            $uiMapping[$name] = $idsStr;

            $ids = $this->expandCampaignTokensToIds($tenant->id, $idsStr);

            $mapping[$name] = $ids;

            if (!empty($ids)) {
                $externalIds = array_merge($externalIds, $ids);
            }
        }

        $settings['category_mapping_ui'] = $uiMapping;
        $settings['category_mapping'] = $mapping;
        $settings['allowed_external_ids'] = array_unique($externalIds);

        $user->update(['settings' => $settings]);

        return back()->with('message', 'Настройки успешно сохранены. ' . count(array_unique($externalIds)) . ' кампаний настроено.');
    }

    /**
     * Parses IDs and marker tokens from UI input.
     * Marker token format: _something (matches campaign name substring, case-insensitive).
     */
    private function expandCampaignTokensToIds(int $tenantId, string $input): array
    {
        $tokens = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $input))));
        $externalIds = [];
        $markers = [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, '_') && mb_strlen($token) > 1) {
                $marker = mb_strtolower(mb_substr($token, 1));
                if ($marker !== '') {
                    $markers[] = $marker;
                }
                continue;
            }

            $externalIds[] = $token;
        }

        if (!empty($markers)) {
            $campaigns = AdCampaign::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->get(['external_id', 'name']);
            $campaignIdsSet = [];

            foreach ($campaigns as $campaign) {
                $campaignIdsSet[(string) $campaign->external_id] = true;
            }

            foreach ($campaigns as $campaign) {
                $name = $this->normalizeMarkerText((string) $campaign->name);

                foreach ($markers as $marker) {
                    $markerNormalized = $this->normalizeMarkerText($marker);
                    if ($markerNormalized !== '' && str_contains($name, $markerNormalized)) {
                        $externalIds[] = (string) $campaign->external_id;
                        break;
                    }
                }
            }

            // Also map markers by UTM values from leads, e.g. "12345_glavnaya_vuz".
            $leads = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('meta_data')
                ->get(['meta_data']);

            foreach ($leads as $lead) {
                $meta = is_array($lead->meta_data) ? $lead->meta_data : [];
                $ref = trim((string) ($meta['utm_campaign'] ?? ($meta['campaign_id'] ?? '')));
                if ($ref === '') {
                    continue;
                }

                $refNormalized = $this->normalizeMarkerText($ref);
                if ($refNormalized === '') {
                    continue;
                }

                foreach ($markers as $marker) {
                    $markerNormalized = $this->normalizeMarkerText($marker);
                    if ($markerNormalized === '' || !str_contains($refNormalized, $markerNormalized)) {
                        continue;
                    }

                    $candidates = [$ref];
                    $parts = preg_split('/[_\-\s]+/u', $ref);
                    $prefix = (string) ($parts[0] ?? '');
                    if ($prefix !== '') {
                        $candidates[] = $prefix;
                    }

                    foreach (array_unique($candidates) as $candidate) {
                        $candidate = trim((string) $candidate, "{} ");
                        if ($candidate !== '' && isset($campaignIdsSet[$candidate])) {
                            $externalIds[] = $candidate;
                        }
                    }

                    break;
                }
            }
        }

        return array_values(array_unique(array_filter($externalIds)));
    }

    private function normalizeMarkerText(string $value): string
    {
        $value = mb_strtolower($value);
        return preg_replace('/[\s_\-]+/u', '', $value) ?? '';
    }
}
