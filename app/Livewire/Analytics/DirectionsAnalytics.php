<?php

namespace App\Livewire\Analytics;

use App\Models\Lead;
use App\Models\AdCampaign;
use App\Services\ThemeService;
use Livewire\Component;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class DirectionsAnalytics extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    public $search = '';

    protected $listeners = ['dateRangeUpdated' => 'updateRange'];

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

    public function render(ThemeService $themeService)
    {
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $isAdmin ? null : ($userSettings['allowed_external_ids'] ?? []);

        // 1. Fetch campaigns (without global scopes to see everything for attribution)
        $campaignsData = AdCampaign::withoutGlobalScopes()
            ->with(['adStats' => function($q) {
                $q->whereBetween('date', [$this->startDate, $this->endDate]);
            }])->get();

        // 2. Fetch leads in range
        $allLeads = Lead::where('user_id', auth()->id())
            ->whereBetween('created_at_source', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
            ->with('deal')
            ->get(['id', 'meta_data', 'qualified_at', 'status']);

        // 3. Attribution Mapping logic
        $campaignStatsMap = [];
        $attributionLookup = [];
        $campNames = [];

        foreach ($campaignsData as $camp) {
            $extId = (string)$camp->external_id;
            $name = mb_strtolower((string)$camp->name);
            $attributionLookup[strtolower($extId)] = $extId;
            if ($camp->utm_campaign) {
                $attributionLookup[strtolower((string)$camp->utm_campaign)] = $extId;
            }
            $campNames[$extId] = $name;
            
            $campaignStatsMap[$extId] = [
                'name' => $camp->name,
                'spend' => (float)$camp->adStats->sum('spend'),
                'leads' => 0,
                'qualified' => 0,
                'won' => 0,
                'lost' => 0,
            ];
        }

        foreach ($allLeads as $l) {
            $cid = $this->extractCampaignId($l->meta_data, $attributionLookup, $campNames);
            if ($cid && isset($campaignStatsMap[$cid])) {
                $campaignStatsMap[$cid]['leads']++;
                if ($l->qualified_at) $campaignStatsMap[$cid]['qualified']++;
                if ($l->status == 142 || ($l->deal && $l->deal->status === 'won')) {
                    $campaignStatsMap[$cid]['won']++;
                } elseif ($l->status == 143 || ($l->deal && $l->deal->status === 'lost')) {
                    $campaignStatsMap[$cid]['lost']++;
                }
            }
        }

        // 4. Group by Category (Direction)
        $directionSummary = [];
        $categoryMapping = $userSettings['category_mapping'] ?? [];

        foreach ($categoryMapping as $directionName => $campIds) {
            $dirStats = [
                'name' => $directionName,
                'spend' => 0,
                'leads' => 0,
                'qualified' => 0,
                'won' => 0,
                'lost' => 0,
                'count' => 0,
            ];

            foreach ($campIds as $extId) {
                if (isset($campaignStatsMap[$extId])) {
                    $s = $campaignStatsMap[$extId];
                    $dirStats['spend'] += $s['spend'];
                    $dirStats['leads'] += $s['leads'];
                    $dirStats['qualified'] += $s['qualified'];
                    $dirStats['won'] += $s['won'];
                    $dirStats['lost'] += $s['lost'];
                    $dirStats['count']++;
                }
            }
            
            if ($dirStats['spend'] > 0 || $dirStats['leads'] > 0) {
                $directionSummary[] = $dirStats;
            }
        }

        // 5. Totals for cards
        $totals = [
            'spend' => collect($directionSummary)->sum('spend'),
            'leads' => collect($directionSummary)->sum('leads'),
            'qualified' => collect($directionSummary)->sum('qualified'),
            'won' => collect($directionSummary)->sum('won'),
            'lost' => collect($directionSummary)->sum('lost'),
        ];

        $layout = $themeService->getView('layouts.app');

        return view('livewire.analytics.directions-analytics', [
            'directionSummary' => $directionSummary,
            'totals' => $totals,
        ])->layout($layout, ['header' => 'Аналитика по направлениям']);
    }

    private function extractCampaignId($meta, $attributionLookup, $campNames): ?string
    {
        if (!$meta || !is_array($meta)) return null;
        $rawTextLower = mb_strtolower(json_encode($meta, JSON_UNESCAPED_UNICODE));

        // Direct Exact
        foreach ($meta as $k => $v) {
            if (is_scalar($v)) {
                $val = mb_strtolower((string)$v);
                if (isset($attributionLookup[$val])) return $attributionLookup[$val];
            }
        }

        // Tokenized Greedy
        foreach ($meta as $k => $v) {
            if (is_scalar($v)) {
                $tokens = preg_split('/[_\-\s\.\/]+/u', mb_strtolower((string)$v));
                foreach ($tokens as $t) {
                    if (mb_strlen($t) < 3) continue;
                    if (isset($attributionLookup[$t])) return $attributionLookup[$t];
                }
            }
        }

        // Substring Name
        foreach ($campNames as $extId => $name) {
            if (mb_strlen($name) < 4) continue;
            if (str_contains($rawTextLower, $name)) return $extId;
        }

        return null;
    }
}
