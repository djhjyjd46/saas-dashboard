<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\AdCampaign;
use App\Models\AdStat;
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
        $userId = auth()->id();

        $tenant = $this->tenantManager->getTenant();
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];

        // 1. Get Campaigns
        $allowedIds = $isAdmin ? [] : ($userSettings['allowed_external_ids'] ?? []);
        $allCampaigns = AdCampaign::with(['adStats' => function ($q) use ($start, $end) {
                $q->whereBetween('date', [$start, $end]);
            }])
            ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                $q->whereIn('external_id', $allowedIds);
            })
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->get();

        // 2. Prepare Attribution Map
        $campaignDataMap = [];
        $attributionLookup = [];
        foreach ($allCampaigns as $camp) {
            $extId = (string)$camp->external_id;
            $attributionLookup[$extId] = $extId;
            if ($camp->utm_campaign) {
                $attributionLookup[(string)$camp->utm_campaign] = $extId;
            }

            $stats = $camp->adStats;
            $campaignDataMap[$extId] = [
                'model' => $camp,
                'spend' => (float)$stats->sum('spend'),
                'clicks' => (int)$stats->sum('clicks'),
                'views' => (int)$stats->sum('impressions'),
                'y_conv' => (int)$stats->sum('conversions'),
                'leads' => 0,
                'qual_leads' => 0,
                'won_deals' => 0,
                'lost_leads' => 0,
                'revenue' => 0,
            ];
        }

        // 3. Fetch CRM Data
        $leadsInRange = \App\Models\Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->whereBetween('created_at_source', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->get();

        foreach ($leadsInRange as $lead) {
            $extId = $this->attributeEntity($lead, $attributionLookup, $campaignDataMap);
            if ($extId && isset($campaignDataMap[$extId])) {
                $campaignDataMap[$extId]['leads']++;
                if ($lead->qualified_at) {
                    $campaignDataMap[$extId]['qual_leads']++;
                }
                
                // Track lost leads (status 143 as per DirectionsAnalytics)
                if ($lead->status == 143) {
                    $campaignDataMap[$extId]['lost_leads']++;
                }
            }
        }

        $dealsInRange = \App\Models\Deal::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->with('lead')
            ->whereBetween('closed_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->get();

        foreach ($dealsInRange as $deal) {
            if (!$deal->lead) continue;
            $extId = $this->attributeEntity($deal->lead, $attributionLookup, $campaignDataMap);
            if ($extId && isset($campaignDataMap[$extId])) {
                if ($deal->status === 'won') {
                    $campaignDataMap[$extId]['won_deals']++;
                    $campaignDataMap[$extId]['revenue'] += (float)$deal->revenue;
                } elseif ($deal->status === 'lost') {
                    $campaignDataMap[$extId]['lost_leads']++;
                }
            }
        }

        // 4. Map to final collection
        $campaigns = collect($campaignDataMap)->map(function ($data, $extId) {
            $camp = $data['model'];
            $camp->period_spend = $data['spend'];
            $camp->period_clicks = $data['clicks'];
            $camp->period_impressions = $data['views'];
            
            // If CRM leads are 0, use Yandex conversions as fallback for "Applications" (Заявки)
            $camp->period_leads = $data['leads'] > 0 ? $data['leads'] : $data['y_conv'];
            
            $camp->period_qual_leads = $data['qual_leads'];
            $camp->period_won_deals = $data['won_deals'];
            $camp->period_lost_leads = $data['lost_leads'];
            $camp->period_revenue = $data['revenue'];

            $camp->period_cpc = $camp->period_clicks > 0 ? $camp->period_spend / $camp->period_clicks : 0;
            $camp->period_ctr = $camp->period_impressions > 0 ? ($camp->period_clicks / $camp->period_impressions) * 100 : 0;
            
            // Requirement 1: CPL
            $camp->period_cpl = $camp->period_leads > 0 ? $camp->period_spend / $camp->period_leads : 0;
            
            // Requirement 2: CR in qual lead, Cost of qual lead
            $camp->period_cr_qual = $camp->period_leads > 0 ? ($camp->period_qual_leads / $camp->period_leads) * 100 : 0;
            $camp->period_cost_qual = $camp->period_qual_leads > 0 ? $camp->period_spend / $camp->period_qual_leads : 0;

            // Requirement 3: CR in won, Cost of won lead
            $camp->period_cr_won = $camp->period_leads > 0 ? ($camp->period_won_deals / $camp->period_leads) * 100 : 0;
            $camp->period_cost_won = $camp->period_won_deals > 0 ? $camp->period_spend / $camp->period_won_deals : 0;

            // Requirement 4: CR in lost (ЗиН)
            $camp->period_cr_lost = $camp->period_leads > 0 ? ($camp->period_lost_leads / $camp->period_leads) * 100 : 0;

            return $camp;
        });

        // 5. Sorting
        $stringFields = ['name', 'status'];
        $sortKey = in_array($this->sortBy, $stringFields) ? $this->sortBy : 'period_' . $this->sortBy;
        if ($this->sortBy === 'conversions') $sortKey = 'period_leads';

        $campaigns = $this->sortDir === 'desc'
            ? $campaigns->sortByDesc($sortKey)
            : $campaigns->sortBy($sortKey);

        $totals = [
            'spend' => $campaigns->sum('period_spend'),
            'clicks' => $campaigns->sum('period_clicks'),
            'impressions' => $campaigns->sum('period_impressions'),
            'leads' => $campaigns->sum('period_leads'),
            'qual_leads' => $campaigns->sum('period_qual_leads'),
            'won_deals' => $campaigns->sum('period_won_deals'),
            'lost_leads' => $campaigns->sum('period_lost_leads'),
        ];
        $totals['cpc'] = $totals['clicks'] > 0 ? $totals['spend'] / $totals['clicks'] : 0;
        $totals['ctr'] = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;
        $totals['cpl'] = $totals['leads'] > 0 ? $totals['spend'] / $totals['leads'] : 0;
        $totals['cost_qual'] = $totals['qual_leads'] > 0 ? $totals['spend'] / $totals['qual_leads'] : 0;
        $totals['cost_won'] = $totals['won_deals'] > 0 ? $totals['spend'] / $totals['won_deals'] : 0;

        // Detail data
        $selectedCampaign = null;
        $campaignStats = [];
        $campaignDailyStats = collect([]);

        if ($this->selectedCampaignId) {
            $selectedCampaign = AdCampaign::find($this->selectedCampaignId);
            if ($selectedCampaign) {
                // For simplicity in detail view, we show basic stats from AdStat
                $campaignDailyStats = AdStat::where('ad_campaign_id', $this->selectedCampaignId)
                    ->whereBetween('date', [$start, $end])
                    ->orderBy('date', 'desc')
                    ->get();

                $spend = (float)$campaignDailyStats->sum('spend');
                $clicks = (int)$campaignDailyStats->sum('clicks');
                $conversions = (int)$campaignDailyStats->sum('conversions');

                $campaignStats = [
                    'spend' => $spend,
                    'clicks' => $clicks,
                    'conversions' => $conversions,
                    'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                    'cpl' => $conversions > 0 ? $spend / $conversions : 0,
                ];
            }
        }

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.ads-campaigns'), [
            'campaigns' => $campaigns->values(),
            'totals'    => $totals,
            'selectedCampaign' => $selectedCampaign,
            'campaignStats' => $campaignStats,
            'campaignDailyStats' => $campaignDailyStats
        ])->layout($layout, ['header' => 'Реклама']);
    }

    private function attributeEntity($lead, array $attributionLookup, array $campaignDataMap): ?string
    {
        $meta = is_array($lead->meta_data) ? $lead->meta_data : [];
        $rawCampaignRef = $meta['utm_campaign'] ?? ($meta['campaign_id'] ?? null);
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
                $nameNormalized = $this->normalizeMarkerText((string)($cData['model']->name ?? ''));
                if ($nameNormalized !== '' && (str_contains($nameNormalized, $rawNormalized) || str_contains($rawNormalized, $nameNormalized))) return (string)$extId;
            }
        }
        return null;
    }

    private function normalizeMarkerText(string $value): string
    {
        return preg_replace('/[\s_\-]+/u', '', mb_strtolower($value)) ?? '';
    }
}
