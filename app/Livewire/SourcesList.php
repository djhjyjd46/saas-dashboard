<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\AdStat;
use App\Models\Lead;
use App\Models\Deal;
use App\Models\Entity;
use Carbon\Carbon;

class SourcesList extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

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

    public function render()
    {
        $start = $this->startDate;
        $end   = $this->endDate;

        $startDt = Carbon::parse($start)->startOfDay();
        $endDt   = Carbon::parse($end)->endOfDay();

        // Yandex Direct spend from AdStat
        $yandexSpend = AdStat::whereBetween('date', [$start, $end])->sum('spend');

        // CRM data grouped by entity (source proxy)
        $entities = Entity::where('is_active', true)->get();

        $sources = [];
        $totalLeads = Lead::whereBetween('created_at_source', [$startDt, $endDt])->count() ?: 0;

        // Yandex source — all AdStat spend + CRM leads attributed to entities
        $yandexLeads   = Lead::whereNotNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt])->count();
        $yandexSales   = Deal::whereHas('lead', fn($q) => $q->whereNotNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt]))->count();
        $yandexRevenue = Deal::whereHas('lead', fn($q) => $q->whereNotNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt]))->sum('revenue');

        // Other leads (no entity = direct/unknown)
        $otherLeads   = Lead::whereNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt])->count();
        $otherSales   = Deal::whereHas('lead', fn($q) => $q->whereNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt]))->count();
        $otherRevenue = Deal::whereHas('lead', fn($q) => $q->whereNull('entity_id')->whereBetween('created_at_source', [$startDt, $endDt]))->sum('revenue');

        // If CRM is empty, show spend-only mode (Yandex data only)
        if ($totalLeads === 0) {
            $sources = [[
                'name'       => 'Яндекс.Директ',
                'leads'      => '—',
                'sales'      => '—',
                'revenue'    => $yandexSpend,
                'percentage' => 100,
                'label'      => 'расход',
            ]];
        } else {
            $pct = fn($n) => $totalLeads > 0 ? round($n / $totalLeads * 100) : 0;
            $sources = [
                [
                    'name' => 'Яндекс.Директ',
                    'leads' => $yandexLeads,
                    'sales' => $yandexSales,
                    'revenue' => $yandexRevenue,
                    'percentage' => $pct($yandexLeads),
                    'label' => 'лиды',
                ],
                [
                    'name' => 'Прочее / Прямые',
                    'leads' => $otherLeads,
                    'sales' => $otherSales,
                    'revenue' => $otherRevenue,
                    'percentage' => $pct($otherLeads),
                    'label' => 'лиды',
                ],
            ];
            $sources = array_filter($sources, fn($s) => $s['leads'] > 0 || $s['revenue'] > 0);
        }

        return view('livewire.sources-list', [
            'sources'      => collect($sources),
            'crmIsEmpty'   => $totalLeads === 0,
        ]);
    }
}
