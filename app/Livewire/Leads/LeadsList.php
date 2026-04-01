<?php

namespace App\Livewire\Leads;

use App\Models\Lead;
use App\Models\CrmStatus;
use App\Models\AdCampaign;
use App\Services\ThemeService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;

class LeadsList extends Component
{
    use WithPagination;

    public $search = '';
    public $selectedLeadId = null;
    public $startDate;
    public $endDate;

    public $sortBy = 'leads.created_at_source';
    public $sortDir = 'desc';
    public $statusFilter = '';
    public $phoneFilter = ''; // 'all', 'with', 'without'
    public $sourceFilter = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'sortBy' => ['except' => 'leads.created_at_source'],
        'sortDir' => ['except' => 'desc'],
        'statusFilter' => ['except' => ''],
        'phoneFilter' => ['except' => ''],
        'sourceFilter' => ['except' => ''],
    ];

    public function mount()
    {
        $this->startDate = now()->subDays(30)->format('Y-m-d');
        $this->endDate = now()->format('Y-m-d');
    }

    #[On('dateRangeUpdated')]
    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->resetPage();
    }

    public function updating()
    {
        $this->resetPage();
    }

    public function sort($column)
    {
        $map = [
            'lead_name' => 'leads.lead_name',
            'created_at_source' => 'leads.created_at_source',
            'qualified_at' => 'leads.qualified_at',
            'budget' => 'deals.revenue',
            'status' => 'crm_statuses.name',
            'camp' => 'leads.meta_data->campaign_id',
        ];

        $target = $map[$column] ?? $column;

        if ($this->sortBy === $target) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $target;
            $this->sortDir = 'asc';
        }
    }

    public function selectLead($id)
    {
        $this->selectedLeadId = $id;
        
        $lead = Lead::find($id);
        if ($lead && $lead->integration_id) {
            $integration = \App\Models\Integration::find($lead->integration_id);
            if ($integration) {
                $provider = $integration->getProvider();
                if ($provider instanceof \App\Services\Integrations\Providers\AmoCrmProvider) {
                    try {
                        $provider->syncLeadHistory($lead);
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("AmoCRM History Sync Failed for lead {$id}: " . $e->getMessage());
                    }
                }
            }
        }
    }

    public function closeLead()
    {
        $this->selectedLeadId = null;
    }

    public function getSelectedLeadProperty()
    {
        if (!$this->selectedLeadId) return null;
        return Lead::with(['statusHistories', 'crmStatus'])->find($this->selectedLeadId);
    }

    public function render(ThemeService $themeService)
    {
        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $isAdmin ? null : ($userSettings['allowed_external_ids'] ?? []);
        
        $query = Lead::query()
            ->with(['deal', 'crmStatus'])
            ->select('leads.*')
            ->leftJoin('deals', 'leads.id', '=', 'deals.lead_id')
            ->leftJoin('crm_statuses', 'leads.status', '=', 'crm_statuses.external_id')
            ->where('leads.user_id', auth()->id())
            ->whereBetween('leads.created_at_source', [
                $this->startDate . ' 00:00:00',
                $this->endDate . ' 23:59:59'
            ]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('leads.lead_name', 'like', '%' . $this->search . '%')
                    ->orWhere('leads.phone', 'like', '%' . $this->search . '%')
                    ->orWhere('leads.external_id', 'like', '%' . $this->search . '%')
                    ->orWhere('crm_statuses.name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->whereIn('leads.status', function($q) {
                $q->select('external_id')
                  ->from('crm_statuses')
                  ->where('name', $this->statusFilter);
            });
        }

        if ($this->phoneFilter === 'with') {
            $query->whereNotNull('leads.phone')->where('leads.phone', '!=', '');
        } elseif ($this->phoneFilter === 'without') {
            $query->where(function($q) {
                $q->whereNull('leads.phone')->orWhere('leads.phone', '');
            });
        }

        if ($this->sourceFilter) {
            $query->where('leads.meta_data->Форма', $this->sourceFilter);
        }

        $leadsPaginator = $query->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);

        // Calculate Stats for the current filtered set of leads
        $allLeads = Lead::query()
            ->with('deal')
            ->where('user_id', auth()->id())
            ->whereBetween('created_at_source', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59'])
            ->get(['id', 'meta_data', 'qualified_at', 'status']);

        $campaignsData = AdCampaign::withoutGlobalScopes()
            ->with(['adStats' => function($q) {
                $q->whereBetween('date', [$this->startDate, $this->endDate]);
            }])->get();

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
                'id' => $camp->id,
                'name' => $camp->name,
                'spend' => (float)$camp->adStats->sum('spend'),
                'leads' => 0,
                'qualified' => 0,
                'won' => 0,
                'lost' => 0,
                'ext_id' => $extId,
            ];
        }

        foreach ($allLeads as $l) {
            $cid = $this->extractCampaignId($l->meta_data, $userSettings, $attributionLookup, $campNames);
            $l->attributed_campaign_id = $cid;
            
            if ($cid && isset($campaignStatsMap[$cid])) {
                $campaignStatsMap[$cid]['leads']++;
                if ($l->qualified_at) {
                    $campaignStatsMap[$cid]['qualified']++;
                }
                if ($l->status == 142 || ($l->deal && $l->deal->status === 'won')) {
                    $campaignStatsMap[$cid]['won']++;
                } elseif ($l->status == 143 || ($l->deal && $l->deal->status === 'lost')) {
                    $campaignStatsMap[$cid]['lost']++;
                }
            }
        }

        foreach ($leadsPaginator->items() as $l) {
            $l->attributed_campaign_id = $this->extractCampaignId($l->meta_data, $userSettings, $attributionLookup, $campNames);
        }

        $totalSpend = 0;
        foreach ($campaignsData as $camp) {
            $extId = (string)$camp->external_id;
            if (!$isAdmin && !empty($allowedIds) && !in_array($extId, $allowedIds)) {
                continue;
            }
            $totalSpend += $camp->adStats->sum('spend');
        }

        $totalLeadsInRange = $allLeads->count();
        $avgCpl = $totalLeadsInRange > 0 ? $totalSpend / $totalLeadsInRange : 0;

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
            ];

            foreach ($campIds as $extId) {
                if (isset($campaignStatsMap[$extId])) {
                    $s = $campaignStatsMap[$extId];
                    $dirStats['spend'] += $s['spend'];
                    $dirStats['leads'] += $s['leads'];
                    $dirStats['qualified'] += $s['qualified'];
                    $dirStats['won'] += $s['won'];
                    $dirStats['lost'] += $s['lost'];
                }
            }
            
            if ($dirStats['spend'] > 0 || $dirStats['leads'] > 0) {
                $directionSummary[] = $dirStats;
            }
        }

        $availableStatuses = CrmStatus::whereIn('external_id', function($query) {
            $query->select('status')->from('leads')->where('user_id', auth()->id());
        })->get()->unique(fn($s) => mb_strtolower(trim($s->name)))->values();
        
        $layout = $themeService->getView('layouts.app');

        return view('livewire.leads.leads-list', [
            'leads' => $leadsPaginator,
            'availableStatuses' => $availableStatuses,
            'totalSpend' => $totalSpend,
            'avgCpl' => $avgCpl,
            'campaignStatsMap' => $campaignStatsMap,
            'directionSummary' => $directionSummary,
        ])->layout($layout, [
            'header' => 'Лиды AmoCRM'
        ]);
    }

    private function extractCampaignId($meta, $userSettings = [], $attributionLookup = [], $campNames = []): ?string
    {
        if (!$meta || !is_array($meta)) return null;

        $rawText = json_encode($meta, JSON_UNESCAPED_UNICODE);
        $rawTextLower = mb_strtolower($rawText);

        // 1. Exact Match
        foreach ($meta as $k => $v) {
            if (is_scalar($v)) {
                $val = mb_strtolower((string)$v);
                if (isset($attributionLookup[$val])) return $attributionLookup[$val];
            }
        }
        
        // 2. Tokenized Greedy
        foreach ($meta as $k => $v) {
            if (is_scalar($v)) {
                $tokens = preg_split('/[_\-\s\.\/]+/u', mb_strtolower((string)$v));
                foreach ($tokens as $t) {
                    if (mb_strlen($t) < 3) continue;
                    if (isset($attributionLookup[$t])) return $attributionLookup[$t];
                }
            }
        }

        // 3. Substring name match
        foreach ($campNames as $extId => $name) {
            if (mb_strlen($name) < 4) continue;
            if (str_contains($rawTextLower, $name)) return $extId;
        }

        return null;
    }
}
