<div class="w-full h-full mx-auto bg-gray rounded-2xl flex overflow-hidden">
    <div class="left w-1/5 px-5 py-8 flex flex-col justify-between">
        <div class="space-y-8">
            <!-- Brands -->
            <div class="flex flex-col gap-4 text-xl font-bold">
                <label wire:click="setBrand('VUZ')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activeBrand === 'VUZ' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="brand" value="VUZ" {{ $activeBrand === 'VUZ' ? 'checked' : '' }}
                        class="hidden">
                    <div class="status-circle"></div>
                    <span>ВУЗ</span>
                </label>
                <label wire:click="setBrand('College')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activeBrand === 'College' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="brand" value="College"
                        {{ $activeBrand === 'College' ? 'checked' : '' }} class="hidden">
                    <div class="status-circle"></div>
                    <span class="text-[24px] text-white">Колледж</span>
                </label>
            </div>

            <div class="h-[2px] bg-gold w-full"></div>

            <!-- Period -->
            <div class="flex flex-col gap-4 text-xl font-medium">
                <div class="flex items-center gap-4 mb-2 px-4 py-2">
                    <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                    </svg>
                    <span>Период</span>
                </div>

                <label wire:click="setPeriod('Сегодня')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activePeriod === 'Сегодня' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="period" value="today"
                        {{ $activePeriod === 'Сегодня' ? 'checked' : '' }} class="hidden">
                    <div class="status-circle"></div>
                    <span>Сегодня</span>
                </label>

                <label wire:click="setPeriod('Вчера')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activePeriod === 'Вчера' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="period" value="yesterday"
                        {{ $activePeriod === 'Вчера' ? 'checked' : '' }} class="hidden">
                    <div class="status-circle"></div>
                    <span>Вчера</span>
                </label>

                <label wire:click="setPeriod('7 дней')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activePeriod === '7 дней' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="period" value="7days"
                        {{ $activePeriod === '7 дней' ? 'checked' : '' }} class="hidden">
                    <div class="status-circle"></div>
                    <span>7 дней</span>
                </label>

                <label wire:click="setPeriod('30 дней')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activePeriod === '30 дней' ? 'sidebar-active' : '' }}">
                    <input type="radio" name="period" value="30days"
                        {{ $activePeriod === '30 дней' ? 'checked' : '' }} class="hidden">
                    <div class="status-circle"></div>
                    <span>30 дней</span>
                </label>

                <label wire:click="setPeriod('Произвольный')"
                    class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ $activePeriod === 'Произвольный' ? 'sidebar-active' : '' }}">
                    <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 24 24">
                        <path
                            d="M20 7h-4V5a2 2 0 00-2-2H10a2 2 0 00-2 2v2H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zM10 5h4v2h-4V5zM4 9h16v2H4V9zm0 10V13h16v6H4z" />
                    </svg>
                    <span>Произвольный</span>
                </label>
                @if ($activePeriod === 'Произвольный')
                    <div class="flex flex-col gap-2 px-4">
                        <input type="date" wire:model.lazy="customStart"
                            class="bg-[#2B2B2B] border border-white/10 rounded-lg px-3 py-2 text-white text-sm">
                        <input type="date" wire:model.lazy="customEnd"
                            class="bg-[#2B2B2B] border border-white/10 rounded-lg px-3 py-2 text-white text-sm">
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom Buttons -->
        <div class="pr-8 flex flex-col gap-3">
            @if (session()->has('sync_success'))
                <div class="text-[12px] text-green-400 font-medium mb-1">{{ session('sync_success') }}</div>
            @endif
            @if (session()->has('sync_error'))
                <div class="text-[12px] text-red-500 font-medium mb-1">{{ session('sync_error') }}</div>
            @endif

            <div class="flex gap-3 items-center">
                <button wire:click="syncData" wire:loading.attr="disabled"
                    class="w-full py-4 bg-[#2B2B2B] border border-white/5 rounded-xl text-[20px] text-gold font-bold hover:bg-white/5 transition-colors disabled:opacity-50 disabled:cursor-wait">
                    <span wire:loading.remove wire:target="syncData">Обновить данные</span>
                    <span wire:loading wire:target="syncData">Синхронизация...</span>
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="size-10 flex item-center justify-center">


                        <svg class="" fill="#AC9658 " width="100%" height="100%" viewBox="0 0 20 20"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 10l-6-5v3H6v4h7v3l6-5zM3 3h8V1H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H3V3z" />
                        </svg>

                    </button>
                </form>
            </div>

            <!-- Hidden Technical Link -->
            <div class="hidden mt-4 justify-start opacity-0 hover:opacity-20 transition-opacity">
                <a href="{{ route('technical.settings') }}" class="text-[10px] text-white/50 cursor-default">.</a>
            </div>
        </div>
    </div>
    <div class="right w-4/5 bg-[#434141] rounded-2xl px-5 py-7 flex flex-col gap-8 overflow-y-auto">
            <div class="cards grid grid-cols-4 gap-5">
                <div class="card bg-card rounded-2xl px-5 py-2 h-[140px]">
                    <div class="flex justify-between mb-4">
                        <div class="top flex justify-between w-full items-center">
                            <span class="gradient-text">РАСХОД</span>
                            <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="value text-2xl gradient-text !text-[32px] !font-extrabold">
                        {{ number_format($stats['spend'], 0, '.', ' ') }} ₽</div>
                    <div class="grafik h-8">
                        <svg class="w-full h-full" viewBox="0 0 100 20" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#F1D38C" stroke-width="2"
                                points="{{ $spendSparkline }}">
                            </polyline>
                        </svg>
                    </div>
                </div>
                <div class="card bg-card rounded-2xl px-5 py-2 h-[140px]">
                    <div class="flex justify-between mb-4">
                        <div class="top flex justify-between w-full items-center">
                            <span class="gradient-text">ДОХОД</span>
                            <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="value text-2xl gradient-text !text-[32px] !font-extrabold">
                        {{ number_format($stats['revenue'], 0, '.', ' ') }} ₽</div>
                    <div class="grafik h-8">
                        <svg class="w-full h-full" viewBox="0 0 100 20" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#F1D38C" stroke-width="2"
                                points="{{ $revenueSparkline }}"></polyline>
                        </svg>
                    </div>
                </div>
                <div class="card bg-card rounded-2xl px-5 py-2 h-[140px]">
                    <div class="flex justify-between mb-4">
                        <div class="top flex justify-between w-full items-center">
                            <span class="gradient-text">ЛИДЫ</span>
                            <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="value text-2xl gradient-text !text-[32px] !font-extrabold">{{ $stats['leads'] }}</div>
                    <div class="grafik h-8">
                        <svg class="w-full h-full" viewBox="0 0 100 20" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#F1D38C" stroke-width="2"
                                points="{{ $leadsSparkline }}">
                            </polyline>
                        </svg>
                    </div>
                </div>
                <div class="card bg-card rounded-2xl px-5 py-2 h-[140px]">
                    <div class="flex justify-between mb-4">
                        <div class="top flex justify-between w-full items-center">
                            <span class="gradient-text">КВАЛ ЛИДЫ</span>
                            <div class="w-8 h-8 rounded-lg bg-gold/10 flex items-center justify-center">
                                <svg class="w-4 h-4 text-gold" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="value text-2xl gradient-text !text-[32px] !font-extrabold">{{ $stats['qual_leads'] }}
                    </div>
                    <div class="grafik h-8">
                        <svg class="w-full h-full" viewBox="0 0 100 20" preserveAspectRatio="none">
                            <polyline fill="none" stroke="#F1D38C" stroke-width="2"
                                points="{{ $qualLeadsSparkline }}"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
            <div class="bg-card rounded-2xl border border-white/5">
                <table class="gold-dashboard-table overflow-y-auto">
                    <thead>
                        <!-- Row 1: Groups -->
                        <tr>
                            <th rowspan="2">Направление</th>
                            <th rowspan="2">Расход</th>
                            <th colspan="2">Лиды</th>
                            <th colspan="3">Квал Лиды</th>
                            <th colspan="4">Продажи</th>
                            <th rowspan="2">Доход</th>
                        </tr>
                        <!-- Row 2: Sub-columns -->
                        <tr>
                            <th>CPL</th>
                            <th>Кол-во</th>
                            <th>CR1</th>
                            <th>CPL2</th>
                            <th>Кол-во</th>
                            <th>CR2</th>
                            <th>CR3</th>
                            <th>CPS</th>
                            <th>Кол-во</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Total Row -->
                        <tr>
                            <td style="font-weight: 700;">Итого</td>
                            <td>{{ number_format($stats['spend'], 0, '.', ' ') }} ₽</td>
                            <td>{{ number_format($stats['cpl'], 0, '.', ' ') }} ₽</td>
                            <td>{{ $stats['leads'] }}</td>
                            <td>{{ number_format($stats['cr1'], 1) }}%</td>
                            <td>{{ number_format($stats['cpl2'], 0, '.', ' ') }} ₽</td>
                            <td>{{ $stats['qual_leads'] }}</td>
                            <td>{{ number_format($stats['cr2'], 1) }}%</td>
                            <td>{{ number_format($stats['cr3'], 1) }}%</td>
                            <td>{{ number_format($stats['cps'], 0, '.', ' ') }} ₽</td>
                            <td>{{ $stats['deals'] }}</td>
                            <td>{{ number_format($stats['revenue'], 0, '.', ' ') }} ₽</td>
                        </tr>

                        @foreach ($groups as $group)
                            {{-- Group header row --}}
                            <tr wire:click="toggleCategory('{{ $group['id'] }}')" style="cursor:pointer;">
                                <td>
                                    <span style="color: #F1D38C; margin-right: 8px; display: inline-block;">
                                        {{ in_array($group['id'], $expandedCategories) ? '▼' : '▶' }}
                                    </span> {{ ucfirst($group['name']) }}
                                </td>
                                <td>{{ number_format($group['metrics']['spend'], 0, '.', ' ') }} ₽</td>
                                <td>{{ number_format($group['metrics']['cpl'], 0, '.', ' ') }} ₽</td>
                                <td>{{ $group['metrics']['leads'] }}</td>
                                <td>{{ number_format($group['metrics']['cr1'], 1) }}%</td>
                                <td>{{ number_format($group['metrics']['cpl2'], 0, '.', ' ') }} ₽</td>
                                <td>{{ $group['metrics']['qual_leads'] }}</td>
                                <td>{{ number_format($group['metrics']['cr2'], 1) }}%</td>
                                <td>{{ number_format($group['metrics']['cr3'], 1) }}%</td>
                                <td>{{ number_format($group['metrics']['cps'], 0, '.', ' ') }} ₽</td>
                                <td>{{ $group['metrics']['deals'] }}</td>
                                <td>{{ number_format($group['metrics']['revenue'], 0, '.', ' ') }} ₽</td>
                            </tr>
                            {{-- Campaign sub-rows --}}
                            @if (in_array($group['id'], $expandedCategories))
                                @foreach ($group['campaigns'] as $camp)
                                    @php
                                        $cSpend = $camp['spend'];
                                        $cLeads = $camp['leads'];
                                        $cQual = $camp['qual_leads'];
                                        $cDeals = $camp['deals'];
                                        $cRevenue = $camp['revenue'];
                                        $cCpl = $cLeads > 0 ? $cSpend / $cLeads : 0;
                                        $cCpl2 = $cQual > 0 ? $cSpend / $cQual : 0;
                                        $cCr1 = $cLeads > 0 ? ($cQual / $cLeads) * 100 : 0;
                                        $cCr2 = $cQual > 0 ? ($cDeals / $cQual) * 100 : 0;
                                        $cCr3 = $cLeads > 0 ? ($cDeals / $cLeads) * 100 : 0;
                                        $cCps = $cDeals > 0 ? $cSpend / $cDeals : 0;
                                    @endphp
                                    <tr wire:click="openCampaign({{ $camp['id'] }})" style="cursor:pointer;">
                                        <td style="padding-left: 60px; font-weight: 400; width: 330px;">{{ $camp['name'] }}</td>
                                        <td>{{ number_format($cSpend, 0, '.', ' ') }} ₽</td>
                                        <td>{{ number_format($cCpl, 0, '.', ' ') }} ₽</td>
                                        <td>{{ $cLeads }}</td>
                                        <td>{{ number_format($cCr1, 1) }}%</td>
                                        <td>{{ number_format($cCpl2, 0, '.', ' ') }} ₽</td>
                                        <td>{{ $cQual }}</td>
                                        <td>{{ number_format($cCr2, 1) }}%</td>
                                        <td>{{ number_format($cCr3, 1) }}%</td>
                                        <td>{{ number_format($cCps, 0, '.', ' ') }} ₽</td>
                                        <td>{{ $cDeals }}</td>
                                        <td>{{ number_format($cRevenue, 0, '.', ' ') }} ₽</td>
                                    </tr>
                                @endforeach
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Slide Panel / Modal -->
        <div id="sidePanel"
            class="fixed inset-0 z-50 {{ $selectedCampaignId ? '' : 'invisible' }} transition-all duration-300">
            <div id="backdrop"
                class="absolute inset-0 bg-black/60 backdrop-blur-sm {{ $selectedCampaignId ? 'opacity-100' : 'opacity-0' }} transition-opacity duration-300"
                wire:click="closeCampaign"></div>
            <div id="panelContent"
                class="absolute top-0 right-0 w-full max-w-2xl h-full bg-[#1e1e1e] border-l border-white/5 shadow-2xl {{ $selectedCampaignId ? '' : 'translate-x-full' }} transition-transform duration-300 flex flex-col">
                <div class="p-8 border-b border-white/5 flex flex-col gap-4">
                    <div class="flex items-start justify-between">
                        <div>
                            <span class="text-white/40 text-sm block mb-1">Рекламная компания</span>
                            <h2 id="campaignName" class="text-3xl font-bold text-white mb-3">
                                {{ $currentCampaign?->name ?? 'Детали кампании' }}</h2>
                            <div class="flex items-center gap-4">
                                <div
                                    class="flex items-center gap-2 bg-[#1B2B1B] px-3 py-1 rounded-full border border-green-500/10">
                                    <div class="w-2 h-2 rounded-full bg-green-500"></div>
                                    <span class="text-green-500 text-xs font-medium uppercase tracking-wider">Показы
                                        идут</span>
                                </div>
                                <span
                                    class="text-white/40 text-xs">id:{{ $currentCampaign?->external_id ?? '—' }}</span>
                            </div>
                        </div>
                        <button wire:click="closeCampaign"
                            class="w-10 h-10 rounded-xl bg-white/5 flex items-center justify-center hover:bg-white/10 transition-colors">
                            <svg class="w-5 h-5 text-white/40" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-8 flex-1 overflow-y-auto space-y-8">
                    <!-- Section Title -->
                    <h3 class="text-white text-xl font-bold">За выбранный период</h3>

                    @php
                        $dailyCollection = collect($dailyStats);
                        $campSpend = $dailyCollection->sum('spend');
                        $campClicks = $dailyCollection->sum('clicks');
                        $campImpressions = $dailyCollection->sum('impressions');
                        $campDays = $dailyCollection->count();
                        $campCpc = $campClicks > 0 ? $campSpend / $campClicks : 0;
                        $campCtr = $campImpressions > 0 ? ($campClicks / $campImpressions) * 100 : 0;
                    @endphp

                    <!-- Stats Grid (2x3) -->
                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">Расход</div>
                            <div class="text-3xl font-bold text-white"><span class="text-[#AC9658]">₽</span>
                                {{ number_format($campSpend, 0, '.', ' ') }}</div>
                        </div>
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">Клики</div>
                            <div class="text-3xl font-bold text-[#65ABEA]">
                                {{ number_format($campClicks, 0, '.', ' ') }}
                            </div>
                        </div>
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">Показы</div>
                            <div class="text-3xl font-bold text-white">
                                {{ number_format($campImpressions, 0, '.', ' ') }}
                            </div>
                        </div>
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">CPC</div>
                            <div class="text-3xl font-bold text-white"><span class="text-[#D155FF]">₽</span>
                                {{ number_format($campCpc, 0, '.', ' ') }}</div>
                        </div>
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">CTR</div>
                            <div class="text-3xl font-bold text-[#D155FF]">{{ number_format($campCtr, 2) }} %</div>
                        </div>
                        <div class="bg-[#2B2B2B] rounded-2xl p-5 border border-white/5">
                            <div class="text-white/40 text-xs uppercase mb-4">Дней</div>
                            <div class="text-3xl font-bold text-white">{{ $campDays }}</div>
                        </div>
                    </div>

                    <!-- History Table -->
                    <div class="rounded-2xl overflow-hidden border border-white/5">
                        <table class="w-full text-left border-collapse bg-[#2B2B2B]">
                            <thead>
                                <tr class="bg-white/5">
                                    <th class="p-4 text-white/40 text-xs font-bold uppercase">Дата</th>
                                    <th class="p-4 text-white/40 text-xs font-bold uppercase text-center">Расход</th>
                                    <th class="p-4 text-white/40 text-xs font-bold uppercase text-center">Клики</th>
                                    <th class="p-4 text-white/40 text-xs font-bold uppercase text-center">Показы</th>
                                    <th class="p-4 text-white/40 text-xs font-bold uppercase text-center">CTR</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm">
                                @forelse($dailyCollection as $row)
                                    @php
                                        $rowCtr = $row->impressions > 0 ? ($row->clicks / $row->impressions) * 100 : 0;
                                    @endphp
                                    <tr class="border-b border-white/5">
                                        <td class="p-4 text-white/60">
                                            {{ \Carbon\Carbon::parse($row->date)->format('d-m-Y') }}</td>
                                        <td class="p-4 text-center text-white font-medium"><span
                                                class="text-[#AC9658]">₽</span>
                                            {{ number_format($row->spend, 0, '.', ' ') }}</td>
                                        <td class="p-4 text-center text-[#65ABEA] font-medium">
                                            {{ number_format($row->clicks, 0, '.', ' ') }}</td>
                                        <td class="p-4 text-center text-white font-medium">
                                            {{ number_format($row->impressions, 0, '.', ' ') }}</td>
                                        <td class="p-4 text-center text-[#D155FF] font-medium">
                                            {{ number_format($rowCtr, 2) }} %</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="p-4 text-center text-white/40">Нет данных за период
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // JS toggleGroup and openPanel/closePanel are handled by Livewire wire:click
            // Left here for backward compat if needed
        </script>
    </div>
