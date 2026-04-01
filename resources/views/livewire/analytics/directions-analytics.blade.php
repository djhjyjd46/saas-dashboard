<div>
    {{-- Header + Search (Optional here) --}}
    <div class="flex items-center justify-between mb-8 gap-4">
        <div>
            <h2 class="text-xl font-black text-white uppercase tracking-tighter">Аналитика по направлениям</h2>
            <p class="text-[10px] text-gray-500 mt-1 uppercase tracking-widest">AmoCRM + Яндекс.Директ · Полная воронка продаж</p>
        </div>
        <div class="flex items-center gap-3">
             <span class="text-[10px] text-gray-600 font-mono uppercase">Авто-склейка Active</span>
             <div class="w-1.5 h-1.5 rounded-full bg-green-500 animate-pulse"></div>
        </div>
    </div>

    {{-- Totals Row (Ads-style) --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-8">
        @php
            $statsCards = [
                ['label' => 'Расход', 'val' => '₽' . number_format($totals['spend'], 0, ',', ' '), 'color' => 'text-yellow-400'],
                ['label' => 'Лиды', 'val' => number_format($totals['leads'], 0, ',', ' '), 'color' => 'text-blue-400'],
                ['label' => 'Квал (CR)', 'val' => number_format($totals['qualified'], 0, ',', ' ') . ' (' . ($totals['leads'] > 0 ? round(($totals['qualified'] / $totals['leads']) * 100, 1) : 0) . '%)', 'color' => 'text-green-400'],
                ['label' => 'Успех', 'val' => number_format($totals['won'], 0, ',', ' '), 'color' => 'text-indigo-400'],
                ['label' => 'ЗиН', 'val' => number_format($totals['lost'], 0, ',', ' '), 'color' => 'text-red-500/50'],
            ];
        @endphp
        @foreach($statsCards as $s)
            <div class="p-5 rounded-2xl border bg-[#1a1d24] border-[#2a2e39] group hover:border-white/10 transition shadow-xl">
                <div class="text-[9px] text-gray-600 uppercase font-black tracking-[0.2em] mb-1.5">{{ $s['label'] }}</div>
                <div class="text-xl font-black {{ $s['color'] }} tracking-tight">{{ $s['val'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Main Analytics Table (Ads-style) --}}
    <div class="overflow-x-auto rounded-3xl border border-[#2a2e39] bg-[#1a1d24] shadow-2xl overflow-hidden">
        <table class="w-full text-left border-collapse text-[11px]">
            <thead>
                <tr class="text-[10px] uppercase font-black text-gray-500 border-b border-[#2a2e39] tracking-widest bg-white/5">
                    <th class="p-5 w-12 text-center text-gray-800">#</th>
                    <th class="p-5">Направление / Набор РК</th>
                    <th class="p-5 text-right">Расход</th>
                    <th class="p-5 text-right">Лиды (CPL)</th>
                    <th class="p-5 text-right text-green-400/80">Квал (CR/CPL)</th>
                    <th class="p-5 text-right text-indigo-400/80">Успех (CR/CPS)</th>
                    <th class="p-5 text-right text-red-500/40">ЗиН (ZiN)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                @forelse ($directionSummary as $i => $row)
                    @php 
                        $cpl = $row['leads'] > 0 ? $row['spend'] / $row['leads'] : 0;
                        $cpq = $row['qualified'] > 0 ? $row['spend'] / $row['qualified'] : 0;
                        $cps = $row['won'] > 0 ? $row['spend'] / $row['won'] : 0;
                        $crQual = $row['leads'] > 0 ? round(($row['qualified'] / $row['leads']) * 100, 1) : 0;
                        $crWon = $row['leads'] > 0 ? round(($row['won'] / $row['leads']) * 100, 1) : 0;
                    @endphp
                    <tr class="hover:bg-white/[0.02] transition border-b border-white/5 group">
                        <td class="p-5 text-center text-gray-700 font-mono text-[10px]">{{ $i + 1 }}</td>
                        <td class="p-5">
                            <div class="flex items-center gap-3">
                                <div class="w-1.5 h-1.5 rounded-full bg-blue-500 shadow-[0_0_8px_rgba(59,130,246,0.5)]"></div>
                                <div>
                                    <div class="font-black text-gray-100 uppercase text-xs tracking-tighter leading-none group-hover:text-blue-400 transition">{{ $row['name'] }}</div>
                                    <div class="text-[9px] text-gray-600 mt-1 uppercase font-mono tracking-widest">{{ $row['count'] }} активных РК</div>
                                </div>
                            </div>
                        </td>
                        <td class="p-5 text-right font-black text-yellow-400/90 font-mono">₽{{ number_format($row['spend'], 0, ',', ' ') }}</td>
                        <td class="p-5 text-right">
                            <div class="flex flex-col">
                                <span class="text-blue-400 font-black text-xs">{{ $row['leads'] }}</span>
                                <span class="text-white/20 text-[9px] font-mono">CPL: ₽{{ number_format($cpl, 0, ',', ' ') }}</span>
                            </div>
                        </td>
                        <td class="p-5 text-right">
                            <div class="flex flex-col">
                                <span class="text-green-500 font-black text-xs">₽{{ number_format($cpq, 0, ',', ' ') }}</span>
                                <span class="text-white/20 text-[9px] font-mono">CR: {{ $crQual }}%</span>
                            </div>
                        </td>
                        <td class="p-5 text-right">
                            <div class="flex flex-col">
                                <span class="text-indigo-400 font-black text-xs">₽{{ number_format($cps, 0, ',', ' ') }}</span>
                                <span class="text-white/20 text-[9px] font-mono">CR: {{ $crWon }}% ({{ $row['won'] }} шт)</span>
                            </div>
                        </td>
                        <td class="p-5 text-right">
                            <div class="flex flex-col text-red-500/30">
                                <span class="font-bold font-mono">{{ $row['lost'] }}</span>
                                <span class="text-[9px] uppercase tracking-tighter">Слив</span>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-20 text-center">
                            <div class="text-4xl mb-4">📉</div>
                            <div class="text-gray-500 uppercase text-[10px] tracking-[0.3em]">Нет данных для распределения</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(count($directionSummary) > 0)
                <tfoot class="bg-black/30">
                    <tr class="text-[10px] uppercase font-black text-gray-500 tracking-[0.2em] border-t border-[#2a2e39]">
                        <td class="p-5" colspan="2">Итоговые показатели</td>
                        <td class="p-5 text-right text-yellow-400 font-mono">₽{{ number_format($totals['spend'], 0, ',', ' ') }}</td>
                        <td class="p-5 text-right text-blue-400 font-mono">{{ $totals['leads'] }}</td>
                        <td class="p-5 text-right text-green-500 font-mono">{{ $totals['qualified'] }}</td>
                        <td class="p-5 text-right text-indigo-400 font-mono">{{ $totals['won'] }}</td>
                        <td class="p-5 text-right text-red-500/40 font-mono">{{ $totals['lost'] }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    {{-- Info banner --}}
    <div class="mt-8 p-6 rounded-2xl bg-blue-500/5 border border-blue-500/10 flex items-start gap-4">
        <div class="w-10 h-10 rounded-xl bg-blue-500/10 flex items-center justify-center shrink-0">
            <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <h4 class="text-xs font-bold text-gray-200 uppercase tracking-tight mb-1">Как работает склейка?</h4>
            <p class="text-[11px] text-gray-500 leading-relaxed max-w-2xl">
                Система «Hyper-Greedy» автоматически сопоставляет лиды из AmoCRM с кампаниями Яндекс.Директа по ID, UTM-меткам и ключевым словам в названиях. Затем лиды распределяются по вашим бизнес-направлениям согласно настроенному маппингу. Если цифры не сходятся — проверьте «Маркеры» в настройках.
            </p>
        </div>
    </div>
</div>
