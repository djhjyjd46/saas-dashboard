<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AdCampaign;
use App\Models\AdStat;
use App\Models\Lead;
use App\Models\Deal;
use App\Services\Tenancy\TenantManager;
use App\Services\ThemeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Dashboard extends Component
{
    public $activeBrand = 'VUZ'; // Keeping this for the UI logic
    public $activePeriod = '7 дней';
    public $customStart;
    public $customEnd;
    public $expandedCategories = [];
    public $selectedCampaignId = null;

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

    public function syncData()
    {
        $tenantId = $this->tenantManager->getTenantId();

        try {
            // Check if tenant has yandex integration
            $yandexIntegrations = \App\Models\Integration::where('type', 'yandex')
                ->where('is_active', true)
                ->get(); // Note: TenantScope is active

            foreach ($yandexIntegrations as $integration) {
                $provider = new \App\Services\Providers\YandexProvider($integration);
                $provider->syncCampaigns();
                $dateFrom = \Carbon\Carbon::now()->subDays(90)->format('Y-m-d');
                $dateTo = \Carbon\Carbon::now()->format('Y-m-d');
                $provider->syncStats($dateFrom, $dateTo);
            }

            // Sync AmoCRM
            $amoIntegrations = \App\Models\Integration::where('type', 'amocrm')
                ->where('is_active', true)
                ->get();

            /** @var \App\Models\Integration $integration */
            foreach ($amoIntegrations as $integration) {
                (new \App\Services\Integrations\Providers\AmoCrmProvider())
                    ->setIntegration($integration)
                    ->syncLeads(30); // Sync last 30 days
            }

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
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        // Single day → hourly breakdown (24 points)
        if ($days === 1) {
            $points = [];
            for ($h = 0; $h < 24; $h++) {
                $from = $start->copy()->startOfDay()->addHours($h);
                $to   = $from->copy()->addHour();
                $points[] = match ($type) {
                    'spend'      => (float) AdStat::whereHas('adCampaign')
                        ->where('date', $start->format('Y-m-d'))->sum('spend') / 24,
                    'leads'      => Lead::whereBetween('created_at_source', [$from, $to])->count(),
                    'qual_leads' => Lead::whereHas('deal')->whereBetween('created_at_source', [$from, $to])->count(),
                    'revenue'    => (float) Deal::whereHas('lead', fn($q) => $q->whereBetween('created_at_source', [$from, $to]))->sum('revenue'),
                    default      => 0,
                };
            }
        } else {
            $points = [];
            for ($i = 0; $i < $days; $i++) {
                $date = $start->copy()->startOfDay()->addDays($i)->format('Y-m-d');
                $dayStart = Carbon::parse($date)->startOfDay();
                $dayEnd   = Carbon::parse($date)->endOfDay();
                $tenant = $this->tenantManager->getTenant();
                $allowedIds = $tenant->settings['allowed_external_ids'] ?? [];

                $points[] = match ($type) {
                    'spend'      => (float) AdStat::whereHas('adCampaign', function ($q) use ($allowedIds) {
                        if (!empty($allowedIds)) {
                            $q->whereIn('external_id', $allowedIds);
                        }
                    })
                        ->whereBetween('date', [$date, $date])->sum('spend'),
                    'leads'      => Lead::whereBetween('created_at_source', [$dayStart, $dayEnd])
                        ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                            $q->where(function ($qq) use ($allowedIds) {
                                $qq->whereIn('meta_data->campaign_id', $allowedIds)
                                    ->orWhereIn('meta_data->utm_campaign', $allowedIds);
                            });
                        })
                        ->count(),
                    'qual_leads' => Lead::whereHas('deal')->whereBetween('created_at_source', [$dayStart, $dayEnd])
                        ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                            $q->where(function ($qq) use ($allowedIds) {
                                $qq->whereIn('meta_data->campaign_id', $allowedIds)
                                    ->orWhereIn('meta_data->utm_campaign', $allowedIds);
                            });
                        })
                        ->count(),
                    'revenue'    => (float) Deal::whereHas('lead', function ($q) use ($dayStart, $dayEnd, $allowedIds) {
                        $q->whereBetween('created_at_source', [$dayStart, $dayEnd]);
                        if (!empty($allowedIds)) {
                            $q->where(function ($qq) use ($allowedIds) {
                                $qq->whereIn('meta_data->campaign_id', $allowedIds)
                                    ->orWhereIn('meta_data->utm_campaign', $allowedIds);
                            });
                        }
                    })->sum('revenue'),
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

        // Global filtering by allowed external IDs (from Technical Settings TSV)
        $allowedIds = $tenant->settings['allowed_external_ids'] ?? [];

        [$start, $end] = $this->getDates();

        // Fetch all campaigns for this tenant (Scoped automatically)
        $allCampaigns = AdCampaign::query()
            ->when(!empty($allowedIds), function ($q) use ($allowedIds) {
                $q->whereIn('external_id', $allowedIds);
            })->get();
        $campaignDataMap = [];

        foreach ($allCampaigns as $camp) {
            $stats = AdStat::where('ad_campaign_id', $camp->id)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->selectRaw('SUM(spend) as spend, SUM(clicks) as clicks, SUM(impressions) as views')
                ->first();

            $spend = (float)($stats->spend ?? 0);
            $clicks = (int)($stats->clicks ?? 0);
            $views = (int)($stats->views ?? 0);

            $campaignDataMap[$camp->external_id] = [
                'id' => $camp->id,
                'external_id' => $camp->external_id,
                'name' => $camp->name,
                'spend' => $spend,
                'clicks' => $clicks,
                'views' => $views,
                'ctr' => $views > 0 ? ($clicks / $views) * 100 : 0,
                'cpc' => $clicks > 0 ? $spend / $clicks : 0,
                'leads' => 0,
                'qual_leads' => 0,
                'deals' => 0,
                'revenue' => 0,
            ];
        }

        // Calculate specific metrics per campaign from Leads/Deals
        // This makes the "Gold" and "Default" themes ALIVE.

        $baseLeads = Lead::whereBetween('created_at_source', [$start, $end]);

        if (!empty($allowedIds)) {
            $baseLeads->where(function ($q) use ($allowedIds) {
                $q->whereIn('meta_data->campaign_id', $allowedIds)
                    ->orWhereIn('meta_data->utm_campaign', $allowedIds);
            });
        }

        $leadsCollection = $baseLeads->with('deal')->get();

        // Calculate specific metrics per campaign from Leads/Deals
        // DEBUG: uncomment to check why leads don't bind to campaigns
        // \Log::debug('campaignDataMap keys', array_keys($campaignDataMap));
        // \Log::debug('leadsCollection first', $leadsCollection->take(3)->map(fn($l) => ['meta' => $l->meta_data])->toArray());
        foreach ($leadsCollection as $lead) {
            $extId = $lead->meta_data['utm_campaign'] ?? ($lead->meta_data['campaign_id'] ?? null);
            if ($extId && isset($campaignDataMap[$extId])) {
                $campaignDataMap[$extId]['leads']++;
                if ($lead->deal) {
                    $campaignDataMap[$extId]['qual_leads']++;
                    if ($lead->deal->status === 'won') {
                        $campaignDataMap[$extId]['deals']++;
                        $campaignDataMap[$extId]['revenue'] += (float)$lead->deal->revenue;
                    }
                }
            }
        }

        // Mapping logic (from tenant settings)
        $categoryMapping = $tenant->settings['category_mapping'] ?? [];
        $groups = [];
        $mappedIds = [];

        foreach ($categoryMapping as $categoryName => $ids) {
            $groupCampaigns = [];
            $gSpend = 0;
            $gLeads = 0;
            $gQual = 0;
            $gDeals = 0;
            $gRev = 0;

            foreach ($ids as $extId) {
                if (isset($campaignDataMap[$extId])) {
                    $c = $campaignDataMap[$extId];
                    $groupCampaigns[] = $c;
                    $gSpend += $c['spend'];
                    $gLeads += $c['leads'];
                    $gQual += $c['qual_leads'];
                    $gDeals += $c['deals'];
                    $gRev += $c['revenue'];
                    $mappedIds[] = $extId;
                }
            }

            if (!empty($groupCampaigns)) {
                $groups[] = [
                    'name' => $categoryName,
                    'campaigns' => $groupCampaigns,
                    'metrics' => $this->calculateMetrics($gSpend, $gLeads, $gQual, $gDeals, $gRev),
                    'id' => md5($categoryName),
                ];
            }
        }

        // "Other" Group logic
        $otherCampaigns = [];
        $oSpend = 0;
        $oLeads = 0;
        $oQual = 0;
        $oDeals = 0;
        $oRev = 0;
        foreach ($campaignDataMap as $extId => $c) {
            if (!in_array($extId, $mappedIds)) {
                $otherCampaigns[] = $c;
                $oSpend += $c['spend'];
                $oLeads += $c['leads'];
                $oQual += $c['qual_leads'];
                $oDeals += $c['deals'];
                $oRev += $c['revenue'];
            }
        }
        if (!empty($otherCampaigns)) {
            $groups[] = [
                'name' => 'Прочее',
                'campaigns' => $otherCampaigns,
                'metrics' => $this->calculateMetrics($oSpend, $oLeads, $oQual, $oDeals, $oRev),
                'id' => 'other',
            ];
        }

        // Overall statistics
        $totalSpend = AdStat::whereHas('adCampaign', function ($q) use ($allowedIds) {
            if (!empty($allowedIds)) {
                $q->whereIn('external_id', $allowedIds);
            }
        })
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->sum('spend');

        $totalLeads = $leadsCollection->count();
        $totalQualLeads = $leadsCollection->filter(fn($l) => $l->deal !== null)->count();
        $totalDeals = $leadsCollection->filter(fn($l) => $l->deal && $l->deal->status === 'won')->count();
        $totalRevenue = $leadsCollection->filter(fn($l) => $l->deal && $l->deal->status === 'won')->sum(fn($l) => $l->deal->revenue);

        $overall = $this->calculateMetrics($totalSpend, $totalLeads, $totalQualLeads, $totalDeals, $totalRevenue);

        $currentCampaign = null;
        $campaignDailyStats = [];
        if ($this->selectedCampaignId) {
            $currentCampaign = AdCampaign::find($this->selectedCampaignId);
            $campaignDailyStats = AdStat::whereHas('adCampaign')
                ->where('ad_campaign_id', $this->selectedCampaignId)
                ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                ->orderBy('date', 'desc')
                ->get();
        }

        $view = $this->themeService->getView('dashboard');

        // Dynamic layout: layouts.app is the default conventionally
        $layout = $this->themeService->getView('layouts.app');

        $startStr = $start->format('Y-m-d');
        $endStr   = $end->format('Y-m-d');

        return view($view, [
            'stats'               => $overall,
            'groups'              => $groups,
            'currentCampaign'     => $currentCampaign,
            'dailyStats'          => $campaignDailyStats,
            'activeBrand'         => $this->activeBrand,
            'activePeriod'        => $this->activePeriod,
            'spendSparkline'      => $this->getSparklineData('spend',      $start, $end),
            'revenueSparkline'    => $this->getSparklineData('revenue',    $start, $end),
            'leadsSparkline'      => $this->getSparklineData('leads',      $start, $end),
            'qualLeadsSparkline'  => $this->getSparklineData('qual_leads', $start, $end),
        ])->layout($layout, ['header' => 'Сводка']);
    }
}
