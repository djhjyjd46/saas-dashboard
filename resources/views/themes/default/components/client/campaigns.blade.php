<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Мои кампании</h2>
            <p class="text-gray-400 mt-1">Список кампаний Яндекс.Директ, доступных для вашего аккаунта.</p>
        </div>
    </div>

    {{-- Search --}}
    <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-4">
        <div class="relative max-w-md">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Поиск по названию..."
                class="w-full pl-10 pr-4 py-2 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white placeholder-gray-600 focus:outline-none focus:border-yellow-500/50 transition">
        </div>
    </div>

    {{-- Campaigns Table --}}
    <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-[#11141b] border-b border-[#2a2e39] text-xs font-bold text-gray-500 uppercase">
                    <th class="px-6 py-4">Кампания</th>
                    <th class="px-6 py-4">ID</th>
                    <th class="px-6 py-4 text-center">Статус</th>
                    <th class="px-6 py-4">Последняя синхронизация</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2e39]">
                @forelse ($campaigns as $campaign)
                    <tr class="hover:bg-[#2a2e39]/20 transition-all">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-yellow-500/10 rounded-lg flex items-center justify-center text-yellow-500">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12.5 12h2.5l-4.5 8v-8h-2.5l4.5-8v8z"></path>
                                    </svg>
                                </div>
                                <div class="text-white font-medium">{{ $campaign->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                            {{ $campaign->external_id }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @php
                                $statusColors = [
                                    'active' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                    'serving' => 'bg-green-500/10 text-green-400 border-green-500/20',
                                    'paused' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                                    'archived' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
                                    'ended' => 'bg-gray-500/10 text-gray-400 border-gray-500/20',
                                ];
                                $st = $campaign->normalized_status;
                                $statusClass = $statusColors[$st] ?? 'bg-gray-500/10 text-gray-400 border-gray-500/20';
                            @endphp
                            <span
                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $statusClass }}">
                                {{ $st }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $campaign->last_synced_at ? $campaign->last_synced_at->format('d.m.Y H:i') : 'Никогда' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-700" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z" />
                            </svg>
                            <p class="text-sm">Вам еще не назначено ни одной кампании.</p>
                            <p class="text-xs text-gray-600 mt-1">Обратитесь к администратору для настройки доступа.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-[#2a2e39]">
            {{ $campaigns->links() }}
        </div>
    </div>
</div>
