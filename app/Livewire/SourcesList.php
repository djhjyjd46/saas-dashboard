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

        $user = auth()->user();
        $isAdmin = $user?->role === 'admin';
        $userSettings = $user?->campaignSettings() ?? [];
        $allowedIds = $isAdmin ? null : ($userSettings['allowed_external_ids'] ?? []);
        $campaignTenantId = $user->campaignTenantId();

        // Yandex Direct spend filtered by allowed campaigns
        $yandexSpend = AdStat::withoutGlobalScopes()
            ->where('tenant_id', $campaignTenantId)
            ->whereHas('adCampaign', function ($q) use ($isAdmin, $allowedIds, $campaignTenantId) {
                $q->withoutGlobalScopes()->where('tenant_id', $campaignTenantId);
                if (!$isAdmin) {
                    $q->whereIn('external_id', $allowedIds ?? []);
                }
            })->whereBetween('date', [$start, $end])->sum('spend');

        // Total CRM Leads for the tenant/period
        $totalLeads = (int) Lead::whereBetween('created_at_source', [$startDt, $endDt])->count();

        // Yandex source — Leads with utm_campaign from allowed list OR non-empty campaign_id
        $yandexLeads = Lead::whereBetween('created_at_source', [$startDt, $endDt])
            ->where(function($q) use ($isAdmin, $allowedIds, $campaignTenantId) {
                if ($isAdmin) {
                    $q->whereNotNull('meta_data->campaign_id')
                      ->orWhereNotNull('meta_data->utm_campaign');
                } else {
                    $allowedUtms = \App\Models\AdCampaign::withoutGlobalScopes()
                        ->where('tenant_id', $campaignTenantId)
                        ->whereIn('external_id', $allowedIds ?? [])
                        ->pluck('utm_campaign')
                        ->filter()
                        ->toArray();
                    $allAllowedRefs = array_unique(array_merge($allowedIds ?? [], $allowedUtms));

                    $q->whereIn('meta_data->campaign_id', $allAllowedRefs)
                      ->orWhereIn('meta_data->utm_campaign', $allAllowedRefs);
                }
            })->count();

        $yandexSales = Deal::whereHas('lead', function ($q) use ($startDt, $endDt, $isAdmin, $allowedIds, $campaignTenantId) {
             $q->whereBetween('created_at_source', [$startDt, $endDt])
               ->where(function($qq) use ($isAdmin, $allowedIds, $campaignTenantId) {
                   if ($isAdmin) {
                       $qq->whereNotNull('meta_data->campaign_id')
                         ->orWhereNotNull('meta_data->utm_campaign');
                   } else {
                       $allowedUtms = \App\Models\AdCampaign::withoutGlobalScopes()
                           ->where('tenant_id', $campaignTenantId)
                           ->whereIn('external_id', $allowedIds ?? [])
                           ->pluck('utm_campaign')
                           ->filter()
                           ->toArray();
                       $allAllowedRefs = array_unique(array_merge($allowedIds ?? [], $allowedUtms));

                       $qq->whereIn('meta_data->campaign_id', $allAllowedRefs)
                          ->orWhereIn('meta_data->utm_campaign', $allAllowedRefs);
                   }
               });
        })->count();

        $yandexRevenue = Deal::whereHas('lead', function ($q) use ($startDt, $endDt, $isAdmin, $allowedIds, $campaignTenantId) {
             $q->whereBetween('created_at_source', [$startDt, $endDt])
               ->where(function($qq) use ($isAdmin, $allowedIds, $campaignTenantId) {
                   if ($isAdmin) {
                       $qq->whereNotNull('meta_data->campaign_id')
                         ->orWhereNotNull('meta_data->utm_campaign');
                   } else {
                       $allowedUtms = \App\Models\AdCampaign::withoutGlobalScopes()
                           ->where('tenant_id', $campaignTenantId)
                           ->whereIn('external_id', $allowedIds ?? [])
                           ->pluck('utm_campaign')
                           ->filter()
                           ->toArray();
                       $allAllowedRefs = array_unique(array_merge($allowedIds ?? [], $allowedUtms));

                       $qq->whereIn('meta_data->campaign_id', $allAllowedRefs)
                          ->orWhereIn('meta_data->utm_campaign', $allAllowedRefs);
                   }
               });
        })->sum('revenue');

        $otherLeads = $totalLeads - $yandexLeads;
        $otherSales = Deal::whereHas('lead', function ($q) use ($startDt, $endDt) {
             $q->whereBetween('created_at_source', [$startDt, $endDt]);
        })->count() - $yandexSales;

        $otherRevenue = Deal::whereHas('lead', function ($q) use ($startDt, $endDt) {
             $q->whereBetween('created_at_source', [$startDt, $endDt]);
        })->sum('revenue') - $yandexRevenue;

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

        return view('livewire.sources-list', [
            'sources'      => collect($sources),
            'crmIsEmpty'   => $totalLeads === 0,
        ]);
    }
}
