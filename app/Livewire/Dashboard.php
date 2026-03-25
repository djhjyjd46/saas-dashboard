<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Lead;
use App\Models\Deal;
use App\Services\Sync\YandexSyncThrottleService;
use App\Services\Tenancy\TenantManager;
use App\Services\ThemeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $activeBrand = 'VUZ';
    public $activePeriod = '30 дней';
    public $customStart;
    public $customEnd;
    public $expandedCategories = [];
    public $selectedCampaignId = null;
    public $tableSortBy = 'spend';
    public $tableSortDir = 'desc';

    protected $tenantManager;
    protected $themeService;

    public function boot(TenantManager $tenantManager, ThemeService $themeService)
    {
        $this->tenantManager = $tenantManager;
        $this->themeService = $themeService;
    }

    public function setBrand($brand)
    {
        $this->activeBrand = $brand;
        $this->expandedCategories = [];
    }

    public function setPeriod($period)
    {
        $this->activePeriod = $period;
        if ($period !== 'Произвольный') {
            $this->customStart = null;
            $this->customEnd = null;
        }
    }

    public function setCustomRange($start, $end)
    {
        $this->customStart = $start;
        $this->customEnd = $end;
        $this->activePeriod = 'Произвольный';
    }

    public function toggleCategory($id)
    {
        if (in_array($id, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$id]);
        } else {
            $this->expandedCategories[] = $id;
        }
    }

    public function openCampaign($id)
    {
        $this->selectedCampaignId = $id;
    }

    public function closeCampaign()
    {
        $this->selectedCampaignId = null;
    }

    public function sortTable(string $field): void
    {
        if ($this->tableSortBy === $field) {
            $this->tableSortDir = $this->tableSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->tableSortBy = $field;
            $this->tableSortDir = $field === 'name' ? 'asc' : 'desc';
        }
    }

    public function syncData()
    {
        $tenantId = $this->tenantManager->getTenantId();
        $throttle = app(YandexSyncThrottleService::class);

        $remaining = $throttle->manualRemainingSeconds((int) $tenantId);
        if ($remaining > 0) {
            $this->dispatch('sync-cooldown', message: 'Ближайшее обновление доступно через ' . $throttle->formatRemaining($remaining));
            return;
        }

        try {
            $yandexIntegrations = \App\Models\Integration::where('type', 'yandex')
                ->where('is_active', true)
                ->get();

            foreach ($yandexIntegrations as $integration) {
                $provider = new \App\Services\Providers\YandexProvider($integration);
                $provider->syncCampaigns();
                $dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');
                $dateTo = Carbon::now()->format('Y-m-d');
                $provider->syncStats($dateFrom, $dateTo);
            }

            $amoIntegrations = \App\Models\Integration::where('type', 'amocrm')
                ->where('is_active', true)
                ->get();

            foreach ($amoIntegrations as $integration) {
                (new \App\Services\Integrations\Providers\AmoCrmProvider())
                    ->setIntegration($integration)
                    ->syncLeads(30);
            }

            $throttle->markManualRun((int) $tenantId);
            session()->flash('sync_success', 'Данные успешно обновлены из всех источников.');
        } catch (\Exception $e) {
            session()->flash('sync_error', 'Ошибка при обновлении: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error("Dashboard Sync Failed: " . $e->getMessage());
        }
    }

    private function getDates()
    {
        $now = Carbon::now();
        if ($this->activePeriod === 'Произвольный' && $this->customStart && $this->customEnd) {
            return [Carbon::parse($this->customStart)->startOfDay(), Carbon::parse($this->customEnd)->endOfDay()];
        }

        return match ($this->activePeriod) {
            'Сегодня' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'Вчера' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            '7 дней' => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
            '30 дней' => [$now->copy()->subDays(30)->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(7)->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    private function calculateMetrics($spend, $leads, $qualLeads, $deals, $revenue)
    {
        return [
            'spend' => (float)$spend,
            'leads' => (int)$leads,
            'qual_leads' => (int)$qualLeads,
            'deals' => (int)$deals,
            'revenue' => (float)$revenue,
            'cpl' => $leads > 0 ? $spend / $leads : 0,
            'cr1' => $leads > 0 ? ($qualLeads / $leads) * 100 : 0,
            'cpl2' => $qualLeads > 0 ? $spend / $qualLeads : 0,
            'cr2' => $qualLeads > 0 ? ($deals / $qualLeads) * 100 : 0,
            'cr3' => $leads > 0 ? ($deals / $leads) * 100 : 0,
            'cps' => $deals > 0 ? $spend / $deals : 0,
        ];
    }

    private function getSparklineData(string $type, Carbon $start, Carbon $end): string
    {
        $userId = auth()->id();
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $userSettings['allowed_external_ids'] ?? [];

        $points = [];
        if ($days === 1) {
            for ($h = 0; $h < 24; $h++) {
                $from = $start->copy()->startOfDay()->addHours($h);
                $to   = $from->copy()->addHour();
                $points[] = match ($type) {
                    'spend'      => (float) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $start->format('Y-m-d'))->sum('spend') / 24,
                    'leads'      => (function () use ($from, $to, $allowedIds, $start, $userId) {
                        $crmLeads = Lead::withoutGlobalScopes()->where('user_id', $userId)->whereBetween('created_at_source', [$from, $to])
                            ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                                $q->where(function ($qq) use ($allowedIds) {
                                    $qq->whereIn('meta_data->campaign_id', $allowedIds)
                                        ->orWhereIn('meta_data->utm_campaign', $allowedIds);
                                });
                            })->count();

                        if ($crmLeads > 0) return $crmLeads;
                        return (int) round((float) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $start->format('Y-m-d'))->sum('conversions') / 24);
                    })(),
                    'qual_leads' => Deal::withoutGlobalScopes()->where('user_id', $userId)->where('status', 'won')->whereBetween('closed_at', [$from, $to])->count(),
                    'revenue'    => (float) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $start->format('Y-m-d'))->sum('conversions') / 24,
                    default      => 0,
                };
            }
        } else {
            for ($i = 0; $i < $days; $i++) {
                $day = $start->copy()->addDays($i)->format('Y-m-d');
                $points[] = match ($type) {
                    'spend'      => (float) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $day)->sum('spend'),
                    'leads'      => (function () use ($day, $allowedIds, $userId) {
                        $crmLeads = Lead::withoutGlobalScopes()->where('user_id', $userId)->whereDate('created_at_source', $day)
                            ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                                $q->where(function ($qq) use ($allowedIds) {
                                    $qq->whereIn('meta_data->campaign_id', $allowedIds)
                                        ->orWhereIn('meta_data->utm_campaign', $allowedIds);
                                });
                            })->count();
                        if ($crmLeads > 0) return $crmLeads;
                        return (int) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $day)->sum('conversions');
                    })(),
                    'qual_leads' => Deal::withoutGlobalScopes()->where('user_id', $userId)->where('status', 'won')->whereDate('closed_at', $day)->count(),
                    'revenue'    => (float) AdStat::withoutGlobalScopes()->where('user_id', $userId)->where('date', $day)->sum('conversions'),
                    default      => 0,
                };
            }
        }

        if (empty($points) || max($points) == 0) return '0,15 100,15';

        $max = max($points);
        $svg = '';
        foreach ($points as $i => $val) {
            $x = ($i / max(count($points) - 1, 1)) * 100;
            $y = 20 - (($val / $max) * 20 * 0.8) - 2;
            $svg .= "$x,$y ";
        }
        return trim($svg);
    }

    public function render()
    {
        $tenant = $this->tenantManager->getTenant();
        if (!$tenant) return redirect()->route('login');
        $tenantId = $tenant->id;

        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $userSettings['allowed_external_ids'] ?? [];

        [$start, $end] = $this->getDates();

        $categoryMappingUiRaw = $userSettings['category_mapping_ui'] ?? [];
        $applyFilter = empty($categoryMappingUiRaw) && !empty($allowedIds);

        $allCampaigns = AdCampaign::query()
            ->when($applyFilter, function ($q) use ($allowedIds) {
                $q->whereIn('external_id', $allowedIds);
            })->get();

        $attributionLookup = [];
        $campaignDataMap = [];

        foreach ($allCampaigns as $camp) {
            $attributionLookup[(string)$camp->external_id] = (string)$camp->external_id;
            if ($camp->utm_campaign) {
                $attributionLookup[(string)$camp->utm_campaign] = (string)$camp->external_id;
            }

            $stats = AdStat::where('ad_campaign_id', $camp->id)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->selectRaw('SUM(spend) as spend, SUM(clicks) as clicks, SUM(impressions) as views, SUM(conversions) as yandex_conversions')
                ->first();

            $spend = (float)($stats->spend ?? 0);
            $clicks = (int)($stats->clicks ?? 0);
            $views = (int)($stats->views ?? 0);
            $y_conv = (int)($stats->yandex_conversions ?? 0);

            $campaignDataMap[$camp->external_id] = [
                'id' => $camp->id,
                'external_id' => $camp->external_id,
                'name' => $camp->name,
                'utm_campaign' => (string)$camp->utm_campaign,
                'spend' => $spend,
                'clicks' => $clicks,
                'views' => $views,
                'ctr' => $views > 0 ? ($clicks / $views) * 100 : 0,
                'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                'leads' => 0,
                'qual_leads' => 0,
                'deals' => 0,
                'yandex_leads' => $y_conv,
                'revenue' => $y_conv,
            ];
        }

        $userId = auth()->id();

        $totalCrmLeads = (int) Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereBetween('created_at_source', [$start, $end])
            ->count();
        $totalCrmDeals = (int) Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'won')
            ->whereBetween('closed_at', [$start, $end])
            ->count();
        
        // Debug logging for raw counts
        $rawCountDeals = (int) Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->where('status', 'won')
            ->count();
        \Illuminate\Support\Facades\Log::debug("CRM Diagnostic", [
            'user' => $userId,
            'tenant' => $tenantId,
            'start' => $start->toDateTimeString(),
            'end' => $end->toDateTimeString(),
            'total_won_deals_in_db' => $rawCountDeals,
            'deals_in_range' => $totalCrmDeals,
            'leads_in_range' => $totalCrmLeads
        ]);

        $totalCrmQualLeads = $totalCrmDeals;

        $baseLeads = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $userId)
            ->whereBetween('created_at_source', [$start, $end]);
        if (!empty($allowedIds)) {
            $allowedUtms = $allCampaigns->pluck('utm_campaign')->filter()->toArray();
            $allAllowedRefs = array_unique(array_merge($allowedIds, $allowedUtms));
            $baseLeads->where(function ($q) use ($allAllowedRefs) {
                $q->whereIn('meta_data->campaign_id', $allAllowedRefs)
                  ->orWhereIn('meta_data->utm_campaign', $allAllowedRefs);
            });
        }
        $leadsInRange = $baseLeads->get();

        foreach ($leadsInRange as $lead) {
            $extId = $this->attributeEntity($lead, $attributionLookup, $campaignDataMap);
            if ($extId && isset($campaignDataMap[$extId])) {
                $campaignDataMap[$extId]['leads']++;
            }
        }

        $baseDeals = Deal::withoutGlobalScopes()->where('tenant_id', $tenantId)->with('lead')->whereBetween('closed_at', [$start, $end]);
        if (!empty($allowedIds)) {
            $baseDeals->whereHas('lead', function($q) use ($allAllowedRefs) {
                $q->whereIn('meta_data->campaign_id', $allAllowedRefs)
                  ->orWhereIn('meta_data->utm_campaign', $allAllowedRefs);
            });
        }
        $dealsInRange = $baseDeals->get();

        foreach ($dealsInRange as $deal) {
            if (!$deal->lead) continue;
            $extId = $this->attributeEntity($deal->lead, $attributionLookup, $campaignDataMap);
            if ($extId && isset($campaignDataMap[$extId])) {
                $campaignDataMap[$extId]['qual_leads']++;
                if ($deal->status === 'won') {
                    $campaignDataMap[$extId]['deals']++;
                }
            }
        }

        foreach ($campaignDataMap as $extId => $campaignMetrics) {
            if (($campaignMetrics['leads'] ?? 0) === 0 && ($campaignMetrics['yandex_leads'] ?? 0) > 0) {
                $campaignDataMap[$extId]['leads'] = (int) $campaignMetrics['yandex_leads'];
            }
        }

        $categoryMappingUi = $userSettings['category_mapping_ui'] ?? [];
        if (!empty($categoryMappingUi)) {
            $campaignIdsSet = [];
            foreach ($campaignDataMap as $extId => $cData) {
                $campaignIdsSet[(string) $extId] = true;
            }

            $leadRefs = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', auth()->id())
                ->whereNotNull('meta_data')
                ->get(['meta_data'])
                ->map(function ($lead) {
                    $meta = is_array($lead->meta_data) ? $lead->meta_data : [];
                    return trim((string) ($meta['utm_campaign'] ?? ($meta['campaign_id'] ?? '')));
                })
                ->filter(fn(string $ref) => $ref !== '')
                ->values();

            $categoryMapping = [];
            foreach ($categoryMappingUi as $catName => $idsStr) {
                $tokens = array_values(array_filter(array_map('trim', explode(',', (string)$idsStr))));
                $catIds = [];
                foreach ($tokens as $token) {
                    $isRegexMarker = str_starts_with($token, '_');
                    if (isset($campaignDataMap[$token])) {
                        $catIds[] = (string)$token;
                        continue;
                    }

                    $marker = $isRegexMarker ? mb_substr($token, 1) : $token;
                    $marker = mb_strtolower($marker);
                    if ($marker === '') continue;

                    $markerNormalized = $this->normalizeMarkerText($marker);
                    if ($markerNormalized === '') {
                        if (!$isRegexMarker) $catIds[] = (string)$token;
                        continue;
                    }

                    foreach ($campaignDataMap as $extId => $cData) {
                        $nameNormalized = $this->normalizeMarkerText((string)($cData['name'] ?? ''));
                        $utmNormalized  = $this->normalizeMarkerText((string)($cData['utm_campaign'] ?? ''));
                        if (str_contains($nameNormalized, $markerNormalized) || str_contains($utmNormalized, $markerNormalized)) {
                            $catIds[] = (string)$extId;
                        }
                    }

                    foreach ($leadRefs as $ref) {
                        if (!str_contains($this->normalizeMarkerText($ref), $markerNormalized)) continue;

                        $candidates = [$ref];
                        $parts = preg_split('/[_\-\s]+/u', $ref);
                        if (!empty($parts[0])) $candidates[] = $parts[0];

                        foreach (array_unique($candidates) as $candidate) {
                            $candidate = trim((string) $candidate, "{} ");
                            if ($candidate !== '' && isset($campaignIdsSet[$candidate])) $catIds[] = (string)$candidate;
                        }
                    }
                }
                $categoryMapping[$catName] = array_values(array_unique($catIds));
            }
        } else {
            $categoryMapping = $userSettings['category_mapping'] ?? [];
        }

        $groups = [];
        $mappedIds = [];
        foreach ($categoryMapping as $categoryName => $ids) {
            $catUpper = mb_strtoupper($categoryName);
            $isCollegeTagged = str_contains($catUpper, 'COLLEGE') || str_contains($catUpper, 'КОЛЛЕДЖ');
            $isVuzTagged = str_contains($catUpper, 'VUZ') || str_contains($catUpper, 'ВУЗ');

            if ($this->activeBrand === 'College' && $isVuzTagged && !$isCollegeTagged) continue;
            if ($this->activeBrand === 'VUZ' && $isCollegeTagged && !$isVuzTagged) continue;

            $groupCampaigns = [];
            $gSpend = $gLeads = $gQual = $gDeals = $gRev = 0;

            foreach ($ids as $extId) {
                if (isset($campaignDataMap[$extId])) {
                    $c = $campaignDataMap[$extId];
                    $groupCampaigns[] = $c;
                    $gSpend += $c['spend']; $gLeads += $c['leads']; $gQual += $c['qual_leads']; $gDeals += $c['deals']; $gRev += $c['revenue'];
                    $mappedIds[] = $extId;
                }
            }

            $groups[] = [
                'name' => $categoryName,
                'campaigns' => $groupCampaigns,
                'metrics' => $this->calculateMetrics($gSpend, $gLeads, $gQual, $gDeals, $gRev),
                'id' => md5($categoryName),
            ];
        }

        $otherCampaigns = [];
        $oSpend = $oLeads = $oQual = $oDeals = $oRev = 0;
        foreach ($campaignDataMap as $extId => $c) {
            if (!in_array($extId, $mappedIds)) {
                $otherCampaigns[] = $c;
                $oSpend += $c['spend']; $oLeads += $c['leads']; $oQual += $c['qual_leads']; $oDeals += $c['deals']; $oRev += $c['revenue'];
            }
        }
        if (!empty($otherCampaigns) && $user->role === 'admin') {
            $groups[] = [
                'name' => 'Прочее',
                'campaigns' => $otherCampaigns,
                'metrics' => $this->calculateMetrics($oSpend, $oLeads, $oQual, $oDeals, $oRev),
                'id' => 'other',
            ];
        }

        if (!empty($this->expandedCategories)) {
            foreach ($groups as &$group) {
                if (in_array($group['id'], $this->expandedCategories, true)) {
                    usort($group['campaigns'], function ($a, $b) {
                        return $this->compareSortValues($this->campaignSortValue($a, $this->tableSortBy), $this->campaignSortValue($b, $this->tableSortBy));
                    });
                }
            }
        } else {
            usort($groups, function ($a, $b) {
                return $this->compareSortValues($this->groupSortValue($a, $this->tableSortBy), $this->groupSortValue($b, $this->tableSortBy));
            });
        }

        $overallStats = AdStat::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->where('user_id', auth()->id())
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('SUM(spend) as spend, SUM(conversions) as conversions, SUM(revenue) as revenue_sum')
            ->first();

        $totalSpend = (float)($overallStats->spend ?? 0);
        $totalRevenueValue = (float)($overallStats->revenue_sum ?? ($overallStats->conversions ?? 0));
        $totalLeads = $totalCrmLeads > 0 ? $totalCrmLeads : (int)($overallStats->conversions ?? 0);
        $totalQualLeads = $totalCrmQualLeads;
        $totalDeals = $totalCrmDeals;

        $overall = $this->calculateMetrics($totalSpend, $totalLeads, $totalQualLeads, $totalDeals, $totalRevenueValue);

        \Illuminate\Support\Facades\Log::debug('Dashboard metrics v2', [
            'tenant_id' => $tenant->id,
            'user' => $user->email,
            'period' => $this->activePeriod,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'totalSpend' => $totalSpend,
            'totalLeads' => $totalLeads,
            'totalQualLeads' => $totalQualLeads,
            'totalDeals' => $totalDeals,
            'allowedIds' => $allowedIds,
        ]);

        return view($this->themeService->getView('dashboard'), [
            'stats'               => $overall,
            'groups'              => $groups,
            'currentCampaign'     => $this->selectedCampaignId ? AdCampaign::find($this->selectedCampaignId) : null,
            'dailyStats'          => $this->selectedCampaignId ? AdStat::where('ad_campaign_id', $this->selectedCampaignId)->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])->orderBy('date', 'desc')->get() : [],
            'activeBrand'         => $this->activeBrand,
            'activePeriod'        => $this->activePeriod,
            'spendSparkline'      => $this->getSparklineData('spend',      $start, $end),
            'revenueSparkline'    => $this->getSparklineData('revenue',    $start, $end),
            'leadsSparkline'      => $this->getSparklineData('leads',      $start, $end),
            'qualLeadsSparkline'  => $this->getSparklineData('qual_leads', $start, $end),
        ])->layout($this->themeService->getView('layouts.app'), ['header' => 'Сводка']);
    }

    private function attributeEntity(Lead $lead, array $attributionLookup, array $campaignDataMap): ?string
    {
        $rawCampaignRef = $lead->meta_data['utm_campaign'] ?? ($lead->meta_data['campaign_id'] ?? null);
        if ($rawCampaignRef === null) return null;
        $rawCampaignRef = (string) $rawCampaignRef;
        if (isset($attributionLookup[$rawCampaignRef])) return $attributionLookup[$rawCampaignRef];

        $parts = preg_split('/[_\-\s]+/u', $rawCampaignRef);
        foreach ($parts as $part) {
            $partClean = trim((string)$part, "{} ");
            if ($partClean !== '' && isset($attributionLookup[$partClean])) return $attributionLookup[$partClean];
        }

        $rawNormalized = $this->normalizeMarkerText($rawCampaignRef);
        if ($rawNormalized !== '') {
            foreach ($campaignDataMap as $extId => $cData) {
                $nameNormalized = $this->normalizeMarkerText((string)($cData['name'] ?? ''));
                if ($nameNormalized !== '' && (str_contains($nameNormalized, $rawNormalized) || str_contains($rawNormalized, $nameNormalized))) return (string)$extId;
            }
        }
        return null;
    }

    private function compareSortValues($aVal, $bVal): int
    {
        $result = (is_string($aVal) || is_string($bVal)) ? strcasecmp((string) $aVal, (string) $bVal) : ($aVal <=> $bVal);
        return $this->tableSortDir === 'asc' ? $result : -$result;
    }

    private function groupSortValue(array $group, string $field)
    {
        return $field === 'name' ? ($group['name'] ?? '') : ($group['metrics'][$field] ?? 0);
    }

    private function campaignSortValue(array $campaign, string $field)
    {
        if ($field === 'name') return $campaign['name'] ?? '';
        $spend = (float)($campaign['spend'] ?? 0);
        $leads = (int)($campaign['leads'] ?? 0);
        $qual  = (int)($campaign['qual_leads'] ?? 0);
        $deals = (int)($campaign['deals'] ?? 0);

        return match ($field) {
            'spend' => $spend,
            'leads' => $leads,
            'qual_leads' => $qual,
            'deals' => $deals,
            'revenue' => (float)($campaign['revenue'] ?? 0),
            'cpl' => $leads > 0 ? ($spend / $leads) : 0,
            'cr1' => $leads > 0 ? (($qual / $leads) * 100) : 0,
            'cpl2' => $qual > 0 ? ($spend / $qual) : 0,
            'cr2' => $qual > 0 ? (($deals / $qual) * 100) : 0,
            'cr3' => $leads > 0 ? (($deals / $leads) * 100) : 0,
            'cps' => $deals > 0 ? ($spend / $deals) : 0,
            default => 0,
        };
    }

    private function normalizeMarkerText(string $value): string
    {
        return preg_replace('/[\s_\-]+/u', '', mb_strtolower($value)) ?? '';
    }
}
