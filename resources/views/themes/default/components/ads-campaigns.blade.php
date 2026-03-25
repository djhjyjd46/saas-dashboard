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
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse"></span> Показы
                                    идут
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
                                            <th class="p-3 text-right font-semibold">Показы</th>
                                            <th class="p-3 text-right font-semibold">CTR</th>

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
                                                <td class="p-3 text-right text-gray-500">
                                                    {{ number_format($day->impressions, 0, ',', ' ') }}</td>
                                                <td class="p-3 text-right text-pink-400 text-xs">
                                                    {{ $day->impressions > 0 ? number_format(($day->clicks / $day->impressions) * 100, 2, ',', '.') : '0,00' }}%
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
            <p class="text-xs text-gray-500 mt-0.5">Яндекс.Директ · {{ $totalCampaignsCount }} кампаний за период</p>
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
    <div class="grid grid-cols-5 gap-3 mb-6">
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
                    'label' => 'Показы',
                    'value' => number_format($totals['impressions'], 0, ',', ' '),
                    'color' => 'text-gray-300',
                ],
                [
                    'label' => 'CPC',
                    'value' => '₽' . number_format($totals['cpc'], 0, ',', ' '),
                    'color' => 'text-purple-400',
                ],
                [
                    'label' => 'CTR',
                    'value' => number_format($totals['ctr'], 2, ',', '.') . '%',
                    'color' => 'text-pink-400',
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
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-gray-300 transition select-none"
                        wire:click="sort('impressions')">
                        Показы @if ($sortBy === 'impressions')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-purple-400 transition select-none"
                        wire:click="sort('cpc')">
                        CPC @if ($sortBy === 'cpc')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                    <th class="p-4 font-semibold text-right cursor-pointer hover:text-pink-400 transition select-none"
                        wire:click="sort('ctr')">
                        CTR @if ($sortBy === 'ctr')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>

                    <th class="p-4 font-semibold text-center cursor-pointer hover:text-white transition select-none"
                        wire:click="sort('status')">
                        Статус @if ($sortBy === 'status')
                            <span class="ml-1">{{ $sortDir === 'desc' ? '↓' : '↑' }}</span>
                        @endif
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($groups as $group)
                    @php
                        $isExpanded = in_array($group['id'], $expandedCategories) || !$hasMapping;
                        $gt = $group['totals'];
                    @endphp

                    {{-- Group Header Row --}}
                    @if ($hasMapping)
                        <tr class="bg-[#111317] border-b border-[#2a2e39] cursor-pointer hover:bg-white/[0.02] transition"
                            wire:click="toggleCategory('{{ $group['id'] }}')">
                            <td class="p-4 text-center">
                                <span
                                    class="text-xs text-gray-500 transition-transform duration-200 inline-block {{ $isExpanded ? 'rotate-90' : '' }}">▶</span>
                            </td>
                            <td class="p-4 font-bold text-yellow-500 uppercase tracking-wider text-[11px]">
                                {{ $group['name'] }}
                                <span
                                    class="ml-2 text-[10px] text-gray-600 font-normal">({{ count($group['campaigns']) }})</span>
                            </td>
                            <td class="p-4 text-right font-bold text-yellow-500/80">
                                ₽{{ number_format($gt['spend'], 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-blue-400/80">
                                {{ number_format($gt['clicks'], 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-gray-500/80">
                                {{ number_format($gt['impressions'], 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-purple-400/80">
                                ₽{{ number_format($gt['cpc'], 0, ',', ' ') }}</td>
                            <td class="p-4 text-right text-pink-400/80">{{ number_format($gt['ctr'], 2, ',', '.') }}%
                            </td>

                            <td class="p-4"></td>
                        </tr>
                    @endif

                    @if ($isExpanded)
                        @foreach ($group['campaigns'] as $i => $camp)
                            @php
                                $st = $camp->normalized_status;
                                $isStale = is_null($camp->last_synced_at);
                                if ($isStale) {
                                    $badge = ['bg-gray-700/50 text-gray-600', 'bg-gray-700', 'Устарело'];
                                } elseif ($st === 'serving') {
                                    $badge = ['bg-green-500/10 text-green-400', 'bg-green-400 animate-pulse', 'Показы'];
                                } elseif ($st === 'active') {
                                    $badge = ['bg-teal-500/10 text-teal-400', 'bg-teal-500', 'Включена'];
                                } elseif ($st === 'suspended' || $st === 'paused') {
                                    $badge = ['bg-yellow-500/10 text-yellow-500', 'bg-yellow-500', 'Пауза'];
                                } elseif ($st === 'archived') {
                                    $badge = ['bg-gray-500/10 text-gray-600', 'bg-gray-700', 'Архив'];
                                } elseif ($st === 'ended') {
                                    $badge = ['bg-gray-500/10 text-gray-400', 'bg-gray-500', 'Завершена'];
                                } elseif ($st === 'stopped') {
                                    $badge = ['bg-red-500/10 text-red-400', 'bg-red-500', 'Остановлена'];
                                } elseif ($st === 'moderation') {
                                    $badge = ['bg-blue-500/10 text-blue-400', 'bg-blue-400', 'Модерация'];
                                } elseif ($st === 'rejected') {
                                    $badge = ['bg-red-500/10 text-red-400', 'bg-red-600', 'Отклонена'];
                                } elseif ($st === 'draft') {
                                    $badge = ['bg-blue-500/10 text-blue-400', 'bg-blue-500', 'Черновик'];
                                } else {
                                    $badge = ['bg-gray-500/10 text-gray-500', 'bg-gray-600', $st ?: '—'];
                                }
                            @endphp
                            <tr class="border-b border-[#2a2e39] hover:bg-yellow-500/[0.03] transition cursor-pointer group"
                                wire:click="openCampaign({{ $camp->id }})">
                                <td class="p-4 text-center text-gray-600 text-xs font-mono">
                                    {{ $hasMapping ? '↳' : $i + 1 }}
                                </td>
                                <td class="p-4">
                                    <div
                                        class="text-gray-200 font-medium max-w-sm leading-snug group-hover:text-white transition">
                                        {{ $camp->name }}
                                    </div>
                                    @if ($camp->entity)
                                        <span
                                            class="inline-flex items-center gap-1 mt-1 text-xs px-2 py-0.5 rounded-full"
                                            style="background-color:{{ $camp->entity->color ?? '#374151' }}20; color:{{ $camp->entity->color ?? '#9ca3af' }}">
                                            {{ $camp->entity->name }}
                                        </span>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <span
                                        class="text-yellow-400 font-bold">₽{{ number_format($camp->period_spend, 0, ',', ' ') }}</span>
                                </td>
                                <td class="p-4 text-right text-blue-400 font-medium">
                                    {{ number_format($camp->period_clicks, 0, ',', ' ') }}
                                </td>
                                <td class="p-4 text-right text-gray-400">
                                    {{ number_format($camp->period_impressions, 0, ',', ' ') }}
                                </td>
                                <td class="p-4 text-right text-purple-400">
                                    ₽{{ number_format($camp->period_cpc, 0, ',', ' ') }}
                                </td>
                                <td class="p-4 text-right text-pink-400">
                                    {{ number_format($camp->period_ctr, 2, ',', '.') }}%
                                </td>

                                <td class="p-4 text-center">
                                    <span
                                        class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full font-medium {{ $badge[0] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $badge[1] }}"></span>
                                        {{ $badge[2] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                @endforeach

                @if (empty($groups))
                    <tr>
                        <td colspan="8" class="p-12 text-center text-gray-600">
                            <div class="text-4xl mb-3">📊</div>
                            <div class="text-sm">Нет данных за выбранный период</div>
                        </td>
                    </tr>
                @endif
            </tbody>
            @if (!empty($groups))
                <tfoot>
                    <tr class="border-t text-sm font-bold" style="border-color:#2a2e39; background-color:#111317;">
                        <td class="p-4" colspan="2">Итого</td>
                        <td class="p-4 text-right text-yellow-400">₽{{ number_format($totals['spend'], 0, ',', ' ') }}
                        </td>
                        <td class="p-4 text-right text-blue-400">{{ number_format($totals['clicks'], 0, ',', ' ') }}
                        </td>
                        <td class="p-4 text-right text-gray-400">
                            {{ number_format($totals['impressions'], 0, ',', ' ') }}</td>
                        <td class="p-4 text-right text-purple-400">₽{{ number_format($totals['cpc'], 0, ',', ' ') }}
                        </td>
                        <td class="p-4 text-right text-pink-400">{{ number_format($totals['ctr'], 2, ',', '.') }}%
                        </td>

                        <td class="p-4"></td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
