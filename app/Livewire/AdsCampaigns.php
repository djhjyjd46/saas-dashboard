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

        $tenant = $this->tenantManager->getTenant();
        $isAdmin = auth()->user()?->role === 'admin';
        $allowedCompanies = $isAdmin ? [] : ($tenant->settings['allowed_companies'] ?? []);
        $allowedIds = $isAdmin ? [] : ($tenant->settings['allowed_external_ids'] ?? []);

        // Get all campaigns with their stats for the period
        $campaigns = AdCampaign::with(['adStats' => function ($q) use ($start, $end) {
            $q->whereBetween('date', [$start, $end]);
        }])
            ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                $q->whereIn('external_id', $allowedIds);
            })
            ->when(empty($allowedIds) && !empty($allowedCompanies), function ($q) use ($allowedCompanies) {
                $q->where(function ($qq) use ($allowedCompanies) {
                    foreach ($allowedCompanies as $comp) {
                        $qq->orWhere('name', 'LIKE', '%' . trim($comp) . '%');
                    }
                });
            })
            ->when($this->search, function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            })
            ->get()
            ->map(function ($camp) {
                $camp->period_spend       = $camp->adStats->sum('spend');
                $camp->period_clicks      = $camp->adStats->sum('clicks');
                $camp->period_impressions = $camp->adStats->sum('impressions');
                $camp->period_cpc         = $camp->period_clicks > 0
                    ? $camp->period_spend / $camp->period_clicks
                    : 0;
                $camp->period_ctr         = $camp->period_impressions > 0
                    ? ($camp->period_clicks / $camp->period_impressions) * 100
                    : 0;
                return $camp;
            });

        // Sort — numeric fields are prefixed with 'period_', string fields are direct
        $stringFields = ['name', 'status'];
        $sortKey = in_array($this->sortBy, $stringFields) ? $this->sortBy : 'period_' . $this->sortBy;
        $campaigns = $this->sortDir === 'desc'
            ? $campaigns->sortByDesc($sortKey)
            : $campaigns->sortBy($sortKey);

        $totals = [
            'spend'       => $campaigns->sum('period_spend'),
            'clicks'      => $campaigns->sum('period_clicks'),
            'impressions' => $campaigns->sum('period_impressions'),
        ];
        $totals['cpc'] = $totals['clicks'] > 0 ? $totals['spend'] / $totals['clicks'] : 0;
        $totals['ctr'] = $totals['impressions'] > 0 ? ($totals['clicks'] / $totals['impressions']) * 100 : 0;

        // Detail data if campaign selected
        $selectedCampaign = null;
        $campaignStats = [];
        $campaignDailyStats = collect([]);

        if ($this->selectedCampaignId) {
            $selectedCampaign = AdCampaign::find($this->selectedCampaignId);
            if ($selectedCampaign) {
                $campaignDailyStats = AdStat::where('ad_campaign_id', $this->selectedCampaignId)
                    ->whereBetween('date', [$start, $end])
                    ->orderBy('date', 'desc')
                    ->get();

                $spend = $campaignDailyStats->sum('spend');
                $clicks = $campaignDailyStats->sum('clicks');
                $impressions = $campaignDailyStats->sum('impressions');

                $campaignStats = [
                    'spend' => $spend,
                    'clicks' => $clicks,
                    'impressions' => $impressions,
                    'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                    'ctr' => $impressions > 0 ? ($clicks / $impressions) * 100 : 0
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
}
