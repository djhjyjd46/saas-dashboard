<div>
    {{-- Campaign detail panel (slides in from right) --}}
    @if ($selectedCampaign)
        <div class="fixed inset-0 z-40 flex justify-end" x-data x-on:keydown.escape.window="$wire.closeCampaign()">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeCampaign"></div>
            <div class="relative w-full max-w-2xl bg-[#181b21] border-l border-[#2a2e39] h-full overflow-y-auto z-50
            shadow-2xl"
                x-data x-init="$el.animate([{ transform: 'translateX(100%)' }, { transform: 'translateX(0)' }], { duration: 250, easing: 'ease-out' })">

                {{-- Panel header --}}
                <div
                    class="sticky top-0 bg-[#181b21] border-b border-[#2a2e39] p-6 flex items-start justify-between z-10">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-1">Рекламная кампания</p>
                        <h2 class="text-white font-bold text-lg leading-snug max-w-lg">{{ $selectedCampaign->name }}</h2>
                        <div class="flex items-center gap-2 mt-2 flex-wrap">
                            @php
                                $sc = $selectedCampaign;
                                $st = $sc->normalized_status;
                            @endphp
                            @if ($st === 'serving')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-green-500/10 text-green-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Показы идут
                                </span>
                            @elseif($st === 'active')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-teal-500/10 text-teal-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-teal-500"></span> Включена
                                </span>
                            @elseif($st === 'suspended' || $st === 'paused')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-yellow-500/10 text-yellow-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Приостановлена
                                </span>
                            @elseif($st === 'archived')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-gray-500/10 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span> Архив
                                </span>
                            @elseif($st === 'ended')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-gray-500/10 text-gray-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-500"></span> Завершена
                                </span>
                            @elseif($st === 'stopped')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-red-500/10 text-red-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Остановлена
                                </span>
                            @elseif($st === 'moderation')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-400"></span> Модерация
                                </span>
                            @elseif($st === 'draft')
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Черновик
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-gray-500/10 text-gray-500">
                                    <span class="w-1.5 h-1.5 rounded-full bg-gray-600"></span> {{ $st ?: 'Неизвестно' }}
                                </span>
                            @endif
                            @if ($selectedCampaign->entity)
                                <span class="text-xs px-2 py-0.5 rounded-full"
                                    style="background:{{ $selectedCampaign->entity->color ?? '#374151' }}20; color:{{ $selectedCampaign->entity->color ?? '#9ca3af' }}">
                                    {{ $selectedCampaign->entity->name }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-600">ID: {{ $selectedCampaign->external_id }}</span>
                        </div>
                    </div>
                    <button wire:click="closeCampaign" class="text-gray-500 hover:text-white transition p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-6">
                    {{-- Period summary cards --}}
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider mb-3">За выбранный период</p>
                        <div class="grid grid-cols-3 gap-3">
                            @php
                                $cs = $campaignStats;
                            @endphp
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">Расход</div>
                                <div class="text-xl font-bold text-yellow-400">
                                    ₽{{ number_format($cs['spend'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">Клики</div>
                                <div class="text-xl font-bold text-blue-400">
                                    {{ number_format($cs['clicks'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">Показы</div>
                                <div class="text-xl font-bold text-gray-300">
                                    {{ number_format($cs['impressions'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">CPC</div>
                                <div class="text-xl font-bold text-purple-400">
                                    ₽{{ number_format($cs['cpc'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">CTR</div>
                                <div class="text-xl font-bold text-pink-400">
                                    {{ number_format($cs['ctr'], 2, ',', '.') }}%</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">Лиды</div>
                                <div class="text-xl font-bold text-green-400">
                                    {{ number_format($cs['conversions'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">CPL</div>
                                <div class="text-xl font-bold text-indigo-400">
                                    ₽{{ number_format($cs['cpl'], 0, ',', ' ') }}</div>
                            </div>
                            <div class="p-4 rounded-xl bg-[#1a1d24] border border-[#2a2e39]">
                                <div class="text-xs text-gray-500 mb-1">Дней</div>
                                <div class="text-xl font-bold text-gray-300">{{ count($campaignDailyStats) }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Daily breakdown table --}}
                    @if (count($campaignDailyStats) > 0)
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wider mb-3">По дням</p>
                            <div class="rounded-xl border border-[#2a2e39] overflow-hidden">
                                <table class="w-full text-sm">
                                    <thead>
                                        <tr class="text-[11px] uppercase text-gray-600 border-b border-[#2a2e39]"
                                            style="background:#111317">
                                            <th class="p-3 text-left font-semibold">Дата</th>
                                            <th class="p-3 text-right font-semibold">Расход</th>
                                            <th class="p-3 text-right font-semibold">Клики</th>
                                            <th class="p-3 text-right font-semibold">Лиды</th>
                                            <th class="p-3 text-right font-semibold">CPL</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-[#2a2e39]">
                                        @foreach ($campaignDailyStats as $day)
                                            <tr class="hover:bg-white/[0.02] transition">
                                                <td class="p-3 text-gray-400 font-mono text-xs">{{ $day->date }}
                                                </td>
                                                <td class="p-3 text-right text-yellow-400 font-medium">
                                                    ₽{{ number_format($day->spend, 0, ',', ' ') }}</td>
                                                <td class="p-3 text-right text-blue-400">
                                                    {{ number_format($day->clicks, 0, ',', ' ') }}</td>
                                                <td class="p-3 text-right text-green-400">
                                                    {{ number_format($day->conversions, 0, ',', ' ') }}</td>
                                                <td class="p-3 text-right text-indigo-400 text-xs">
                                                    ₽{{ $day->conversions > 0 ? number_format($day->spend / $day->conversions, 0, ',', ' ') : '—' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Header + Search --}}
    <div class="flex items-center justify-between mb-6 gap-4">
        <div>
            <h2 class="text-lg font-bold text-white">Рекламные кампании</h2>
            <p class="text-xs text-gray-500 mt-0.5">Яндекс.Директ · {{ count($campaigns) }} кампаний за период</p>
        </div>
        <div class="relative">
            <svg class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-600" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Поиск по названию..."
                class="pl-9 pr-4 py-2 text-sm bg-[#1a1d24] border border-[#2a2e39] rounded-xl text-gray-300
                    placeholder-gray-600 focus:outline-none focus:border-[#eab308]/50 w-72 transition">
        </div>
    </div>

    {{-- Totals mini-cards --}}
    <div class="grid grid-cols-6 gap-3 mb-6">
        @php
            $miniStats = [
                [
                    'label' => 'Расход',
                    'value' => '₽' . number_format($totals['spend'], 0, ',', ' '),
                    'color' => 'text-yellow-400',
                ],
                [
                    'label' => 'Клики',
                    'value' => number_format($totals['clicks'], 0, ',', ' '),
                    'color' => 'text-blue-400',
                ],
                [
                    'label' => 'CTR',
                    'value' => number_format($totals['ctr'], 2, ',', '.') . '%',
                    'color' => 'text-pink-400',
                ],
                [
                    'label' => 'CPC',
                    'value' => '₽' . number_format($totals['cpc'], 0, ',', ' '),
                    'color' => 'text-purple-400',
                ],
                [
                    'label' => 'Лиды',
                    'value' => number_format($totals['conversions'], 0, ',', ' '),
                    'color' => 'text-green-400',
                ],
                [
                    'label' => 'CPL',
                    'value' => '₽' . number_format($totals['cpl'], 0, ',', ' '),
                    'color' => 'text-indigo-400',
                ],
            ];
        @endphp
        @foreach ($miniStats as $ms)
            <div class="p-4 rounded-xl border" style="background-color:#1a1d24; border-color:#2a2e39;">
                <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">{{ $ms['label'] }}</div>
                <div class="text-xl font-bold {{ $ms['color'] }}">{{ $ms['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-2xl border" style="background-color: #1a1d24; border-color: #2a2e39;">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="border-b text-[11px] uppercase tracking-wide text-gray-500" style="border-color:#2a2e39;">
                    <th class="p-4 font-semibold w-12 text-center">#</th>
                    <th class="p-4 font-semibold cursor-pointer hover:text-white transition select-none"
                        wire:click="sort('name')">
                        Кампания @if ($sortBy === 'name')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-yellow-400 transition select-none"
                        wire:click="sort('spend')">
                        Расход @if ($sortBy === 'spend')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-blue-400 transition select-none"
                        wire:click="sort('clicks')">
                        Клики @if ($sortBy === 'clicks')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-green-400 transition select-none"
                        wire:click="sort('leads')">
                        Лд @if ($sortBy === 'leads')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right text-indigo-400">
                        CPL
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-blue-300 transition select-none"
                        wire:click="sort('qual_leads')">
                        Кв @if ($sortBy === 'qual_leads')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right text-blue-400">
                        CRq
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-teal-400 transition select-none"
                        wire:click="sort('won_deals')">
                        Пр @if ($sortBy === 'won_deals')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right text-teal-400">
                        CRw
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-red-400 transition select-none"
                        wire:click="sort('lost_leads')">
                        ЗиН
                    </th>
                    <th class="p-4 font-semibold text-center cursor-pointer hover:text-white transition select-none"
                        wire:click="sort('status')">
                        Статус
                    </th>
                </tr>
            </thead>
            @php
                $isUserAdmin = auth()->user()?->isAdmin() ?? false;
                $catLookup = [];

                if ($isUserAdmin) {
                    $clients = \App\Models\User::where('role', 'client')->get();
                    foreach ($clients as $client) {
                        $mapping = $client->campaignSettings()['category_mapping'] ?? [];
                        foreach ($mapping as $catName => $ids) {
                            foreach ((array)$ids as $id) {
                                $catLookup[(string)$id] = $client->name . ' — ' . $catName;
                            }
                        }
                    }
                    $adminMapping = auth()->user()?->campaignSettings()['category_mapping'] ?? [];
                    foreach ($adminMapping as $catName => $ids) {
                        foreach ((array)$ids as $id) {
                            $catLookup[(string)$id] = $catName;
                        }
                    }
                } else {
                    $userSettings = auth()->user()?->campaignSettings() ?? [];
                    $mapping = $userSettings['category_mapping'] ?? [];
                    foreach ($mapping as $catName => $ids) {
                        foreach ((array)$ids as $id) {
                            $catLookup[(string)$id] = $catName;
                        }
                    }
                }

                $groupedCampaigns = collect($campaigns)->groupBy(function($c) use ($catLookup) {
                    return $catLookup[(string)$c->external_id] ?? 'Нераспределено';
                });
                if ($groupedCampaigns->has('Нераспределено')) {
                    $unassigned = $groupedCampaigns->pull('Нераспределено');
                    $groupedCampaigns->put('Нераспределено', $unassigned);
                }
                $globalIndex = 0;
            @endphp
            @forelse($groupedCampaigns as $categoryName => $group)
                <tbody x-data="{ expanded: true }">
                    <tr class="border-b hover:bg-white/[0.02] transition cursor-pointer" style="border-color:#2a2e39; background-color: rgba(0,0,0,0.2);" @click="expanded = !expanded">
                        <td colspan="12" class="p-3 text-sm font-semibold text-gray-300">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500 transition-transform duration-200" :class="expanded ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                                {{ $categoryName }} <span class="text-xs text-gray-600 font-normal ml-1">({{ count($group) }})</span>
                            </div>
                        </td>
                    </tr>
                    @foreach($group as $camp)
                        @php
                            $i = $globalIndex++;
                            $st = $camp->normalized_status;
                            $isStale = is_null($camp->last_synced_at);
                            if ($isStale) { $badge = ['bg-gray-700/50 text-gray-600', 'bg-gray-700', 'Устарело']; }
                            elseif ($st === 'serving') { $badge = ['bg-green-500/10 text-green-400', 'bg-green-400 animate-pulse', 'Показы']; }
                            elseif ($st === 'active') { $badge = ['bg-teal-500/10 text-teal-400', 'bg-teal-500', 'Вкл']; }
                            elseif ($st === 'suspended' || $st === 'paused') { $badge = ['bg-yellow-500/10 text-yellow-500', 'bg-yellow-500', 'Пауза']; }
                            else { $badge = ['bg-gray-500/10 text-gray-500', 'bg-gray-600', $st ?: '—']; }
                        @endphp
                        <tr x-show="expanded" class="border-b hover:bg-yellow-500/[0.03] transition cursor-pointer group"
                            style="border-color:#2a2e39;" wire:click="openCampaign({{ $camp->id }})">
                            <td class="p-4 text-center text-gray-600 text-xs font-mono">{{ $i + 1 }}</td>
                            <td class="p-4">
                                <div class="text-gray-200 font-medium max-w-sm leading-snug group-hover:text-white transition">
                                    {{ $camp->name }}</div>
                            </td>
                            <td class="p-4 text-right"><span class="text-yellow-400 font-bold">₽{{ number_format($camp->period_spend, 0, ',', ' ') }}</span></td>
                            <td class="p-4 text-right text-blue-400 font-medium">{{ number_format($camp->period_clicks, 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-pink-400">{{ number_format($camp->period_ctr, 1, ',', '.') }}%</td>
                            
                            <td class="p-4 text-right text-green-400 font-bold">{{ number_format($camp->period_leads, 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-indigo-400 text-[10px]">₽{{ number_format($camp->period_cpl, 0, ',', ' ') }}</td>
                            
                            <td class="p-4 text-right text-blue-300">{{ number_format($camp->period_qual_leads, 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-blue-400 text-[10px]">{{ number_format($camp->period_cr_qual, 1) }}%</td>
                            
                            <td class="p-4 text-right text-teal-400 font-bold">{{ number_format($camp->period_won_deals, 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-teal-500 text-[10px]">{{ number_format($camp->period_cr_won, 1) }}%</td>
                            
                            <td class="p-4 text-right text-red-400">{{ number_format($camp->period_lost_leads, 0, ',', ' ') }}</td>
                            
                            <td class="p-4 text-center">
                                <span class="inline-flex items-center gap-1.5 text-[10px] px-2 py-0.5 rounded-full font-medium {{ $badge[0] }}">
                                    {{ $badge[2] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @empty
                <tbody>
                    <tr><td colspan="12" class="p-12 text-center text-gray-600"><div class="text-sm">Нет данных</div></td></tr>
                </tbody>
            @endforelse
            @if (count($campaigns) > 0)
                <tfoot>
                    <tr class="border-t text-[11px] font-bold" style="border-color:#2a2e39; background-color:#111317;">
                        <td class="p-4" colspan="2">Итого</td>
                        <td class="p-4 text-right text-yellow-400">₽{{ number_format($totals['spend'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-blue-400">{{ number_format($totals['clicks'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-pink-400">{{ number_format($totals['ctr'], 1, ',', '.') }}%</td>
                        <td class="p-4 text-right text-green-400">{{ number_format($totals['leads'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-indigo-400">₽{{ number_format($totals['cpl'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-blue-300">{{ number_format($totals['qual_leads'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-blue-400">{{ number_format($totals['leads'] > 0 ? ($totals['qual_leads']/$totals['leads'])*100 : 0, 1) }}%</td>
                        <td class="p-4 text-right text-teal-400">{{ number_format($totals['won_deals'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-teal-500">{{ number_format($totals['leads'] > 0 ? ($totals['won_deals']/$totals['leads'])*100 : 0, 1) }}%</td>
                        <td class="p-4 text-right text-red-500">{{ number_format($totals['lost_leads'], 0, ',', ' ') }}</td>
                        <td class="p-4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
        </table>
    </div>
</div>
