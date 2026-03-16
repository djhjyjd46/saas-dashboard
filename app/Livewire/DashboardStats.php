<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\AdStat;
use App\Models\Deal;
use App\Models\Lead;
use Carbon\Carbon;

class DashboardStats extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    public function mount()
    {
        if (!$this->startDate) $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        if (!$this->endDate) $this->endDate = Carbon::now()->format('Y-m-d');
    }

    #[On('dateRangeUpdated')]
    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
    }

    public function render()
    {
        $start = $this->startDate;
        $end = $this->endDate;

        $totalSpend = AdStat::whereHas('adCampaign')->whereBetween('date', [$start, $end])->sum('spend');

        $income = Deal::whereHas('lead', function ($q) use ($start, $end) {
            $q->whereBetween('created_at_source', [Carbon::parse($start)->startOfDay(), Carbon::parse($end)->endOfDay()]);
        })->sum('revenue');

        $leadsCount = Lead::whereBetween('created_at_source', [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay()
        ])->count();

        $qualLeadsCount = Lead::whereHas('deal')
            ->whereBetween('created_at_source', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ])->count();

        return view('livewire.dashboard-stats', [
            'spend'              => number_format($totalSpend, 0, ',', ' '),
            'income'             => number_format($income ?? 0, 0, ',', ' '),
            'leads'              => $leadsCount,
            'qualLeads'          => $qualLeadsCount,
            'spendSparkline'     => $this->getSparklineData(AdStat::class, 'spend', $start, $end),
            'incomeSparkline'    => $this->getSparklineData(Deal::class, 'revenue', $start, $end, true),
            'leadsSparkline'     => $this->getSparklineData(Lead::class, 'count', $start, $end),
            'qualLeadsSparkline' => $this->getSparklineData(Lead::class, 'count', $start, $end, false, true),
        ]);
    }

    private function getSparklineData($model, $field, $start, $end, $isDeal = false, $isQual = false)
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $days = $startDate->diffInDays($endDate) + 1;
        $points = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');
            if ($field === 'count') {
                $query = $model::whereBetween('created_at_source', [
                    Carbon::parse($currentDate)->startOfDay(),
                    Carbon::parse($currentDate)->endOfDay()
                ]);
                if ($isQual) $query->whereHas('deal');
                $val = $query->count();
            } else {
                if ($isDeal) {
                    $val = $model::whereHas('lead', function ($q) use ($currentDate) {
                        $q->whereBetween('created_at_source', [
                            Carbon::parse($currentDate)->startOfDay(),
                            Carbon::parse($currentDate)->endOfDay()
                        ]);
                    })->sum($field);
                } else {
                    $val = $model::whereHas('adCampaign')->whereBetween('date', [$currentDate, $currentDate])->sum($field);
                }
            }
            $points[] = $val;
        }

        if (empty($points) || max($points) == 0) return "0,15 100,15";

        $max = max($points);
        $svgPoints = "";
        $width = 100;
        $height = 20;
        foreach ($points as $index => $value) {
            $x = ($index / max(count($points) - 1, 1)) * $width;
            $y = $height - (($value / $max) * $height * 0.8) - 2;
            $svgPoints .= "$x,$y ";
        }

        return trim($svgPoints);
    }
}
