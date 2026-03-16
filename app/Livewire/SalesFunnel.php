<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AdStat;
use App\Models\Lead;
use App\Models\Deal;
use Carbon\Carbon;
use Livewire\Attributes\Url;

class SalesFunnel extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    protected $listeners = ['dateRangeUpdated' => 'updateRange'];

    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
    }

    public function render()
    {
        if (!$this->startDate) $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        if (!$this->endDate)   $this->endDate   = Carbon::now()->format('Y-m-d');

        $start = $this->startDate;
        $end   = $this->endDate;

        // Real Yandex Clicks filtered by period
        $clicks = AdStat::whereBetween('date', [$start, $end])->sum('clicks');

        // Real Leads
        $leads = Lead::whereBetween('created_at_source', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])->count();

        $sales = Deal::where('status', 'won')
            ->whereBetween('closed_at', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()])
            ->count();

        $convClickToLead = $clicks > 0 ? ($leads / $clicks) * 100 : 0;
        $convLeadToSale = $leads > 0 ? ($sales / $leads) * 100 : 0;

        return view('livewire.sales-funnel', [
            'clicks' => number_format($clicks, 0, ',', ' '),
            'leads' => $leads,
            'sales' => $sales,
            'convClickToLead' => round($convClickToLead, 1),
            'convLeadToSale' => round($convLeadToSale, 1),
        ]);
    }
}
