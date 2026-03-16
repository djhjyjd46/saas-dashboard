<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\AdStat;
use App\Models\Deal;
use Livewire\Attributes\Url;
use Carbon\Carbon;

class TrendChart extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    protected $listeners = ['dateRangeUpdated' => 'updateRange'];

    public function mount()
    {
        if (!$this->startDate) $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        if (!$this->endDate) $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
    }

    public $labels = '[]';
    public $spendData = '[]';
    public $incomeData = '[]';

    private static array $ruMonths = [
        1 => 'янв',
        2 => 'фев',
        3 => 'мар',
        4 => 'апр',
        5 => 'май',
        6 => 'июн',
        7 => 'июл',
        8 => 'авг',
        9 => 'сен',
        10 => 'окт',
        11 => 'ноя',
        12 => 'дек'
    ];

    public function render()
    {
        $start = Carbon::parse($this->startDate);
        $end   = Carbon::parse($this->endDate);
        $days  = $start->diffInDays($end);

        // Group spend by date
        $stats = AdStat::whereHas('adCampaign')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        // Group real income by lead created_at date (via deal revenue)
        $deals = Deal::whereHas('lead', function ($q) use ($start, $end) {
            $q->whereBetween('created_at_source', [$start->copy()->startOfDay(), $end->copy()->endOfDay()]);
        })->with('lead')->get();

        $incomeByDate = [];
        foreach ($deals as $deal) {
            $d = Carbon::parse($deal->lead->created_at_source)->format('Y-m-d');
            $incomeByDate[$d] = ($incomeByDate[$d] ?? 0) + $deal->revenue;
        }

        $labelsArr  = [];
        $spendArr   = [];
        $incomeArr  = [];

        for ($i = 0; $i <= $days; $i++) {
            $date    = (clone $start)->addDays($i);
            $dateStr = $date->format('Y-m-d');

            // Russian label: "11 мар"
            $labelsArr[]  = $date->format('d') . ' ' . self::$ruMonths[(int)$date->format('n')];
            $spendArr[]   = isset($stats[$dateStr]) ? round($stats[$dateStr]->sum('spend'), 2) : 0;
            $incomeArr[]  = $incomeByDate[$dateStr] ?? 0;
        }

        $this->labels     = json_encode($labelsArr);
        $this->spendData  = json_encode($spendArr);
        $this->incomeData = json_encode($incomeArr);

        return view('livewire.trend-chart');
    }
}
