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

        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $isAdmin ? null : ($userSettings['allowed_external_ids'] ?? []);

        $totalSpend = AdStat::whereHas('adCampaign', function ($q) use ($isAdmin, $allowedIds) {
            if (!$isAdmin) {
                $q->withoutGlobalScopes()->whereIn('external_id', $allowedIds ?? []);
            }
        })->whereBetween('date', [$start, $end])->sum('spend');

        // CRM leads: all leads for this user in period (UserScope already isolates by user_id)
        $leadsQuery = Lead::whereBetween('created_at_source', [
            Carbon::parse($start)->startOfDay(),
            Carbon::parse($end)->endOfDay()
        ]);
        $leadsCount = $leadsQuery->count();

        // Qualified leads (with deals) — also unfiltered by campaign
        $qualLeadsQuery = Lead::whereHas('deal')
            ->whereBetween('created_at_source', [
                Carbon::parse($start)->startOfDay(),
                Carbon::parse($end)->endOfDay()
            ]);
        $qualLeadsCount = $qualLeadsQuery->count();

        $cpl = $leadsCount > 0 ? $totalSpend / $leadsCount : 0;

        return view('livewire.dashboard-stats', [
            'spend'              => number_format($totalSpend, 0, ',', ' '),
            'cpl'                => number_format($cpl ?? 0, 0, ',', ' '),
            'leads'              => $leadsCount,
            'qualLeads'          => $qualLeadsCount,
            'spendSparkline'     => $this->getSparklineData(AdStat::class, 'spend', $start, $end, false, false, $isAdmin, $allowedIds),
            'leadsSparkline'     => $this->getLeadsSparklineData($start, $end, $isAdmin, $allowedIds),
            'qualLeadsSparkline' => $this->getSparklineData(Lead::class, 'count', $start, $end, false, true, $isAdmin, $allowedIds),
        ]);
    }

    private function getLeadsSparklineData($start, $end, bool $isAdmin = true, ?array $allowedIds = null): string
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $days = $startDate->diffInDays($endDate) + 1;
        $points = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');

            $query = Lead::whereBetween('created_at_source', [
                Carbon::parse($currentDate)->startOfDay(),
                Carbon::parse($currentDate)->endOfDay()
            ]);

            $crmLeads = (int) $query->count();
            $points[] = $crmLeads;
        }

        if (empty($points) || max($points) == 0) return "0,15 100,15";

        $max = max($points);
        $svgPoints = "";
        $width = 100;
        $height = 20;
        for ($i = 0; $i < count($points); $i++) {
            $value = $points[$i];
            $x = ($i / max(count($points) - 1, 1)) * $width;
            $y = $height - (($value / $max) * $height * 0.8) - 2;
            $svgPoints .= "$x,$y ";
        }

        return trim($svgPoints);
    }

    private function getSparklineData($model, $field, $start, $end, $isDeal = false, $isQual = false, bool $isAdmin = true, ?array $allowedIds = null)
    {
        $startDate = Carbon::parse($start);
        $endDate = Carbon::parse($end);
        $days = $startDate->diffInDays($endDate) + 1;
        $points = [];

        for ($i = 0; $i < $days; $i++) {
            $currentDate = $startDate->copy()->addDays($i)->format('Y-m-d');

            if ($field === 'count') {
                if (str_ends_with($model, 'Lead')) {
                    $query = $model::whereBetween('created_at_source', [
                        Carbon::parse($currentDate)->startOfDay(),
                        Carbon::parse($currentDate)->endOfDay()
                    ]);

                    if ($isQual) $query->whereHas('deal');
                    $val = $query->count();
                } else {
                    $val = $model::whereHas('adCampaign', function ($q) use ($isAdmin, $allowedIds) {
                        if (!$isAdmin && !empty($allowedIds)) {
                            $q->whereIn('external_id', $allowedIds);
                        }
                    })->whereBetween('date', [$currentDate, $currentDate])->sum($field);
                }
            } else {
                $val = $model::whereHas('adCampaign', function ($q) use ($isAdmin, $allowedIds) {
                    if (!$isAdmin && !empty($allowedIds)) {
                        $q->whereIn('external_id', $allowedIds);
                    }
                })->whereBetween('date', [$currentDate, $currentDate])->sum($field);
            }
            $points[] = $val;
        }

        if (empty($points) || max($points) == 0) return "0,15 100,15";

        $max = max($points);
        $svgPoints = "";
        $width = 100;
        $height = 20;
        for ($i = 0; $i < count($points); $i++) {
            $value = $points[$i];
            $x = ($i / max(count($points) - 1, 1)) * $width;
            $y = $height - (($value / $max) * $height * 0.8) - 2;
            $svgPoints .= "$x,$y ";
        }

        return trim($svgPoints);
    }
}
