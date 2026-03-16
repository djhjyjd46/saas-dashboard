<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Carbon\Carbon;

class DateFilter extends Component
{
    #[Url]
    public $startDate = '2026-02-01';

    #[Url]
    public $endDate = '2026-02-28';

    public function mount()
    {
        if (!$this->startDate || $this->startDate === '2026-02-01') {
            $this->startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        }
        if (!$this->endDate || $this->endDate === '2026-02-28') {
            $this->endDate = Carbon::now()->format('Y-m-d');
        }
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
