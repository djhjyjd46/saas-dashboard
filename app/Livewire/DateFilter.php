<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use App\Models\Integration;
use App\Services\Providers\YandexProvider;
use App\Services\Integrations\Providers\AmoCrmProvider;
use App\Services\Sync\YandexSyncThrottleService;
use Carbon\Carbon;

class DateFilter extends Component
{
    #[Url]
    public $startDate;

    #[Url]
    public $endDate;

    public function mount()
    {
        if (!$this->startDate) {
            $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        if (!$this->endDate) {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
    }

    #[On('dateRangeUpdated')]
    public function updateRange($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        // No need for extra logic, render() will be called automatically
    }

    public function updatePeriod($start, $end)
    {
        $this->startDate = $start;
        $this->endDate = $end;
        $this->dispatch('dateRangeUpdated', $this->startDate, $this->endDate);
    }

    public function updated($property)
    {
        if ($property === 'startDate' || $property === 'endDate') {
            $this->dispatch('dateRangeUpdated', $this->startDate, $this->endDate);
        }
    }

    public function syncData()
    {
        $tenantId = (int) (auth()->user()?->tenant_id ?? 0);
        if ($tenantId <= 0) {
            $this->dispatch('sync-error', message: 'Не удалось определить проект для синхронизации.');
            return;
        }

        $throttle = app(YandexSyncThrottleService::class);
        $isAdmin = auth()->user()?->role === 'admin';
        if (!$isAdmin) {
            $remaining = $throttle->manualRemainingSeconds($tenantId);
            if ($remaining > 0) {
                $this->dispatch('sync-cooldown', message: 'Ближайшее обновление доступно через ' . $throttle->formatRemaining($remaining));
                return;
            }
        }

        try {
            $yandexIntegrations = Integration::where('type', 'yandex')
                ->where('is_active', true)
                ->get();

            $dateFrom = Carbon::now()->subDays(90)->format('Y-m-d');
            $dateTo   = Carbon::now()->format('Y-m-d');

            foreach ($yandexIntegrations as $integration) {
                $provider = new YandexProvider($integration);
                $provider->syncCampaigns();
                $provider->syncStats($dateFrom, $dateTo);
            }

            $amoIntegrations = Integration::where('type', 'amocrm')
                ->where('is_active', true)
                ->get();

            foreach ($amoIntegrations as $integration) {
                (new AmoCrmProvider())
                    ->setIntegration($integration)
                    ->syncLeads(30);
            }

            $throttle->markManualRun($tenantId);
            $this->dispatch('sync-success', message: 'Данные успешно обновлены.');
            $this->dispatch('dateRangeUpdated', $this->startDate, $this->endDate); // Trigger UI refresh
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('DateFilter sync failed: ' . $e->getMessage());
            $this->dispatch('sync-error', message: 'Ошибка обновления: ' . $e->getMessage());
        }
    }

    #[Computed]
    public function formattedDate()
    {
        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);

        $months = [
            1 => 'янв.',
            2 => 'фев.',
            3 => 'мар.',
            4 => 'апр.',
            5 => 'мая',
            6 => 'июня',
            7 => 'июля',
            8 => 'авг.',
            9 => 'сент.',
            10 => 'окт.',
            11 => 'нояб.',
            12 => 'дек.'
        ];

        $startYear = $start->format('Y');
        $endYear   = $end->format('Y');

        $startStr = $start->format('j') . ' ' . $months[$start->month];
        if ($startYear !== $endYear) {
            $startStr .= ' ' . $startYear;
        }
        $endStr = $end->format('j') . ' ' . $months[$end->month] . ' ' . $endYear;

        return $startStr . ' — ' . $endStr;
    }

    public function render()
    {
        return view('livewire.date-filter');
    }
}
