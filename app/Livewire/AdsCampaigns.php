<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Lead;
use App\Services\ThemeService;
use App\Services\Tenancy\TenantManager;
use Carbon\Carbon;

class AdsCampaigns extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    public $search = '';
    public $sortBy = 'spend';
    public $sortDir = 'desc';

    public $selectedCampaignId = null;
    public $expandedCategories = [];

    protected $listeners = ['dateRangeUpdated' => 'updateRange'];

    protected ThemeService $themeService;
    protected TenantManager $tenantManager;

    public function boot(ThemeService $themeService, TenantManager $tenantManager)
    {
        $this->themeService = $themeService;
        $this->tenantManager = $tenantManager;
    }

    public function mount()
    {
        if (!$this->startDate) $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        if (!$this->endDate)   $this->endDate   = Carbon::now()->format('Y-m-d');
    }

    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate   = $end;
    }

    public function openCampaign($id)
    {
        $this->selectedCampaignId = $id;
    }

    public function closeCampaign()
    {
        $this->selectedCampaignId = null;
    }

    public function toggleCategory($id)
    {
        if (in_array($id, $this->expandedCategories)) {
            $this->expandedCategories = array_diff($this->expandedCategories, [$id]);
        } else {
            $this->expandedCategories[] = $id;
        }
    }

    public function sort($column)
    {
        if ($this->sortBy === $column) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $column;
            $this->sortDir = 'desc';
        }
    }

    public function render()
    {
        $start = $this->startDate;
        $end   = $this->endDate;

        $tenant = $this->tenantManager->getTenant();
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $isAdmin ? [] : ($userSettings['allowed_external_ids'] ?? []);

        // Get campaigns only for current tenant.
        $campaignsQuery = AdCampaign::withoutGlobalScopes()->with(['adStats' => function ($q) use ($start, $end) {
            $q->withoutGlobalScopes()->whereBetween('date', [$start, $end]);
        }])->where('tenant_id', $tenant->id);

        if (!$isAdmin) {
            // Clients must be strictly restricted to their allowed IDs
            // If they have no allowed IDs, we use an empty array to result in no matches
            $campaignsQuery->whereIn('external_id', $allowedIds);
        }

        $campaigns = $campaignsQuery
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->get()
            ->map(function ($camp) {
                $camp->period_spend       = $camp->adStats->sum('spend');
                $camp->period_clicks      = $camp->adStats->sum('clicks');
                $camp->period_impressions = $camp->adStats->sum('impressions');
                $camp->period_conversions = $camp->adStats->sum('conversions');
                $camp->period_revenue     = $camp->adStats->sum('revenue');
                $camp->period_cpc         = $camp->period_clicks > 0
                    ? $camp->period_spend / $camp->period_clicks
                    : 0;
                $camp->period_ctr         = $camp->period_impressions > 0
                    ? ($camp->period_clicks / $camp->period_impressions) * 100
                    : 0;
                return $camp;
            });

        // Base sort (used when no category mapping). For mapped mode,
        // final sort is applied later depending on expanded/collapsed state.
        $stringFields = ['name', 'status'];
        $sortKey = in_array($this->sortBy, $stringFields) ? $this->sortBy : 'period_' . $this->sortBy;
        $campaigns = $this->sortDir === 'desc'
            ? $campaigns->sortByDesc($sortKey)
            : $campaigns->sortBy($sortKey);

        $totals = [
            'spend'       => $campaigns->sum('period_spend'),
            'clicks'      => $campaigns->sum('period_clicks'),
            'impressions' => $campaigns->sum('period_impressions'),
            'conversions' => $campaigns->sum('period_conversions'),
            'revenue'     => $campaigns->sum('period_revenue'),
        ];
        $totals['cpc'] = $totals['clicks'] > 0 ? $totals['spend'] / $totals['clicks'] : 0;
        $totals['ctr'] = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;

        // Grouping logic — re-expand markers dynamically so new campaigns are always grouped correctly
        $categoryMappingUi = $userSettings['category_mapping_ui'] ?? [];
        if (!empty($categoryMappingUi)) {
            $campaignIdsSet = [];
            foreach ($campaigns as $c) {
                $campaignIdsSet[(string) $c->external_id] = true;
            }

            $leads = Lead::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->whereNotNull('meta_data')
                ->get(['meta_data']);

            $utmToCampIds = [];
            $leadRefs = [];
            foreach ($leads as $lead) {
                $meta = is_array($lead->meta_data) ? $lead->meta_data : [];
                $utm = trim((string)($meta['utm_campaign'] ?? ''));
                $cid = trim((string)($meta['campaign_id'] ?? ''));
                
                if ($utm !== '') {
                    $leadRefs[] = $utm;
                    $utmToCampIds[$utm][] = $cid;
                }
                if ($cid !== '') $leadRefs[] = $cid;
            }

            $categoryMapping = [];
            foreach ($categoryMappingUi as $catName => $idsStr) {
                $tokens = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string)$idsStr))));
                $catIds = [];
                foreach ($tokens as $token) {
                    // 1. Exact match via leads mapping (Atomic Match)
                    if (isset($utmToCampIds[$token])) {
                        foreach ($utmToCampIds[$token] as $mappedCid) {
                            $catIds[] = $mappedCid;
                        }
                    }

                    // 2. Marker or ID matching logic
                    if (str_starts_with($token, '_') && mb_strlen($token) > 1) {
                        $marker = mb_substr($token, 1);

                        // Original marker logic: match campaign names (case insensitive)
                        foreach ($campaigns as $c) {
                            if (str_contains(mb_strtolower((string)$c->name), mb_strtolower($marker))) {
                                $catIds[] = (string)$c->external_id;
                            }
                        }

                        // Marker match against lead refs as substring
                        foreach (array_unique($leadRefs) as $ref) {
                            if (str_contains($ref, $marker)) {
                                if (isset($utmToCampIds[$ref])) {
                                    foreach ($utmToCampIds[$ref] as $mappedCid) {
                                        $catIds[] = $mappedCid;
                                    }
                                }
                            }
                        }
                    } else {
                        // Direct match as ID
                        if (isset($campaignIdsSet[$token])) {
                            $catIds[] = $token;
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
            $groupCampaigns = $campaigns->filter(fn($c) => in_array((string)$c->external_id, array_map('strval', $ids)));
            $groups[] = [
                'id' => md5($categoryName),
                'name' => $categoryName,
                'campaigns' => $groupCampaigns,
                'totals'    => [
                    'spend'       => $groupCampaigns->sum('period_spend'),
                    'clicks'      => $groupCampaigns->sum('period_clicks'),
                    'impressions' => $groupCampaigns->sum('period_impressions'),
                    'conversions' => $groupCampaigns->sum('period_conversions'),
                    'revenue'     => $groupCampaigns->sum('period_revenue'),
                ]
            ];
            foreach ($groupCampaigns as $c) $mappedIds[] = (string)$c->external_id;
        }

        // "Other" Group logic — if searching, we might prefer flat list?
        // Let's always group if categories are set, putting unmapped into "Other"
        $otherCampaigns = $campaigns->filter(fn($c) => !in_array((string)$c->external_id, $mappedIds));
        if ($otherCampaigns->isNotEmpty()) {
            $groups[] = [
                'id' => 'other',
                'name' => 'Прочее',
                'campaigns' => $otherCampaigns,
                'totals'    => [
                    'spend'       => $otherCampaigns->sum('period_spend'),
                    'clicks'      => $otherCampaigns->sum('period_clicks'),
                    'impressions' => $otherCampaigns->sum('period_impressions'),
                    'conversions' => $otherCampaigns->sum('period_conversions'),
                    'revenue'     => $otherCampaigns->sum('period_revenue'),
                ]
            ];
        }

        // Add metrics for group totals
        foreach ($groups as &$group) {
            $gt = &$group['totals'];
            $gt['cpc'] = $gt['clicks'] > 0 ? $gt['spend'] / $gt['clicks'] : 0;
            $gt['ctr'] = $gt['impressions'] > 0 ? ($gt['clicks'] / $gt['impressions']) * 100 : 0;
        }
        unset($group);

        // Sorting behavior requested by business:
        // - if categories are collapsed => sort category rows
        // - if a category is expanded => sort subcategories (campaigns) inside expanded categories
        $hasMapping = !empty($categoryMapping);
        if ($hasMapping && empty($this->expandedCategories)) {
            usort($groups, function (array $a, array $b): int {
                $aVal = $this->groupSortValue($a, $this->sortBy);
                $bVal = $this->groupSortValue($b, $this->sortBy);
                return $this->compareSortValues($aVal, $bVal);
            });
        } else {
            foreach ($groups as &$group) {
                $shouldSortCampaigns = !$hasMapping || in_array($group['id'], $this->expandedCategories, true);
                if ($shouldSortCampaigns) {
                    $sorted = $group['campaigns']->sort(function ($a, $b) {
                        $aVal = $this->campaignSortValue($a, $this->sortBy);
                        $bVal = $this->campaignSortValue($b, $this->sortBy);
                        return $this->compareSortValues($aVal, $bVal);
                    });
                    $group['campaigns'] = $sorted->values();
                }
            }
            unset($group);
        }

        // Detail data if campaign selected
        $selectedCampaign = null;
        $campaignStats = [];
        $campaignDailyStats = collect([]);

        if ($this->selectedCampaignId) {
            $selectedCampaign = AdCampaign::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->find($this->selectedCampaignId);
            if ($selectedCampaign) {
                $campaignDailyStats = AdStat::withoutGlobalScopes()
                    ->where('ad_campaign_id', $this->selectedCampaignId)
                    ->where('tenant_id', $tenant->id)
                    ->whereBetween('date', [$start, $end])
                    ->orderBy('date', 'desc')
                    ->get();

                $spend = $campaignDailyStats->sum('spend');
                $clicks = $campaignDailyStats->sum('clicks');
                $impressions = $campaignDailyStats->sum('impressions');
                $conversions = $campaignDailyStats->sum('conversions');
                $revenue = $campaignDailyStats->sum('revenue');

                $campaignStats = [
                    'spend' => $spend,
                    'clicks' => $clicks,
                    'impressions' => $impressions,
                    'conversions' => $conversions,
                    'revenue' => $revenue,
                    'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                    'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0
                ];
            }
        }

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.ads-campaigns'), [
            'groups'    => $groups,
            'totals'    => $totals,
            'selectedCampaign' => $selectedCampaign,
            'campaignStats' => $campaignStats,
            'campaignDailyStats' => $campaignDailyStats,
            'expandedCategories' => $this->expandedCategories,
            'hasMapping' => $hasMapping,
            'totalCampaignsCount' => collect($groups)->sum(fn($g) => count($g['campaigns'])),
        ])->layout($layout, ['header' => 'Реклама']);
    }

    private function compareSortValues($aVal, $bVal): int
    {
        if (is_string($aVal) || is_string($bVal)) {
            $result = strcasecmp((string) $aVal, (string) $bVal);
        } else {
            $result = ($aVal <=> $bVal);
        }

        return $this->sortDir === 'asc' ? $result : -$result;
    }

    private function groupSortValue(array $group, string $field)
    {
        if ($field === 'name' || $field === 'status') {
            return $group['name'] ?? '';
        }

        return $group['totals'][$field] ?? 0;
    }

    private function campaignSortValue($campaign, string $field)
    {
        if ($field === 'name' || $field === 'status') {
            return (string) ($campaign->{$field} ?? '');
        }

        $prop = 'period_' . $field;
        return (float) ($campaign->{$prop} ?? 0);
    }

    private function normalizeMarkerText(string $value): string
    {
        $value = mb_strtolower($value);
        return preg_replace('/[\s_\-]+/u', '', $value) ?? '';
    }
}
