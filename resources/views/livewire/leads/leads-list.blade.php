<div>
    {{-- Lead Detail Slide-over --}}
    @if ($selectedLeadId)
        <div class="fixed inset-0 z-40 flex justify-end" x-data x-on:keydown.escape.window="$wire.closeLead()">
            <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" wire:click="closeLead"></div>
            <div class="relative w-full max-w-2xl bg-[#181b21] border-l border-[#2a2e39] h-full overflow-y-auto z-50 shadow-2xl"
                x-data x-init="$el.animate([{ transform: 'translateX(100%)' }, { transform: 'translateX(0)' }], { duration: 250, easing: 'ease-out' })">
                
                {{-- Panel header --}}
                <div class="sticky top-0 bg-[#181b21] border-b border-[#2a2e39] p-6 flex items-start justify-between z-10">
                    <div>
                        <p class="text-[10px] text-gray-500 uppercase tracking-widest mb-1">Детали лида AmoCRM</p>
                        <h2 class="text-white font-bold text-lg leading-snug">{{ $this->selectedLead->lead_name ?? 'Заявка' }}</h2>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 text-xs px-2.5 py-1 rounded-full bg-blue-500/10 text-blue-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                {{ $this->selectedLead->crmStatus->name ?? 'Без статуса' }}
                            </span>
                            <span class="text-[10px] text-gray-600 font-mono">ID: {{ $this->selectedLead->external_id }}</span>
                        </div>
                    </div>
                    <button wire:click="closeLead" class="text-gray-500 hover:text-white transition p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-8">
                    @if($this->selectedLead)
                        {{-- Meta Data Breakdown --}}
                        <section>
                            <h3 class="text-[11px] uppercase text-gray-500 font-bold mb-4 tracking-widest border-b border-white/5 pb-2">Детали из CRM</h3>
                            <div class="grid grid-cols-2 gap-4">
                                @foreach($this->selectedLead->meta_data as $key => $val)
                                    @if(is_array($val)) @continue @endif
                                    <div class="p-3 rounded-xl bg-white/[0.02] border border-white/5">
                                        <div class="text-[9px] text-gray-500 uppercase mb-1">{{ $key }}</div>
                                        <div class="text-xs text-gray-300 break-all">{{ $val ?: '—' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{-- Filters & Top Row --}}
    <div class="flex flex-col lg:flex-row items-stretch justify-between mb-8 gap-4">
        <div class="flex items-center gap-4 flex-1">
            <div class="relative flex-1 max-w-sm">
                <input wire:model.live.debounce.300ms="search" type="text" 
                    placeholder="Имя, телефон, ID или статус..."
                    class="w-full pl-4 pr-10 py-2.5 text-sm bg-[#1a1d24] border border-[#2a2e39] rounded-xl text-gray-200 placeholder-gray-600 focus:outline-none focus:border-blue-500/50 transition shadow-lg">
                <div class="absolute right-3 top-2.5 text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/></svg>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <select wire:model.live="statusFilter" class="px-4 py-2 text-xs bg-[#1a1d24] border border-[#2a2e39] rounded-xl text-gray-400 focus:outline-none focus:border-blue-500/50 transition">
                    <option value="">Все статусы</option>
                    @foreach($availableStatuses as $st)
                        <option value="{{ $st->name }}">{{ $st->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="phoneFilter" class="px-4 py-2 text-xs bg-[#1a1d24] border border-[#2a2e39] rounded-xl text-gray-400 focus:outline-none focus:border-blue-500/50 transition font-medium">
                    <option value="">Телефон</option>
                    <option value="with">С номером</option>
                    <option value="without">Без номера</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
             <div class="px-5 py-3 bg-[#1a1d24] border border-[#2a2e39] rounded-2xl shadow-xl flex flex-col justify-center min-w-[140px]">
                <div class="text-[9px] text-gray-600 uppercase font-bold tracking-widest mb-1">Лидов за период</div>
                <div class="text-lg font-black text-blue-400 font-mono">{{ number_format($leads->total(), 0, ',', ' ') }}</div>
            </div>
        </div>
    </div>

    {{-- Main Leads Table --}}
    <div class="overflow-x-auto rounded-3xl border border-[#2a2e39] bg-[#1a1d24] shadow-2xl">
        <table class="w-full text-left border-collapse text-[11px]">
            <thead>
                <tr class="bg-[#111317] border-b border-[#2a2e39] text-[10px] uppercase font-black text-gray-600 tracking-widest select-none">
                    <th class="p-5 w-12 text-center text-gray-800">#</th>
                    <th class="p-5 cursor-pointer hover:text-white transition" wire:click="sort('lead_name')">Клиент / Контакт</th>
                    <th class="p-5">Статус AmoCRM</th>
                    <th class="p-5 text-right cursor-pointer hover:text-white transition" wire:click="sort('budget')">Бюджет</th>
                    <th class="p-5 text-center cursor-pointer hover:text-white transition" wire:click="sort('created_at_source')">Дата</th>
                    <th class="p-5 text-right text-yellow-400/80">Источник / РК</th>
                    <th class="p-5 text-right text-green-400/80">Квал</th>
                    <th class="p-5 text-right text-indigo-400/80">Успех</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                @foreach ($leads as $i => $lead)
                    <tr class="hover:bg-white/[0.02] transition cursor-pointer group border-b border-white/[0.01]" 
                        wire:click="selectLead({{ $lead->id }})">
                        <td class="p-5 text-center text-gray-700 font-mono text-[10px]">{{ $i + 1 + ($leads->currentPage() - 1) * $leads->perPage() }}</td>
                        <td class="p-5">
                            <div class="text-gray-200 font-bold group-hover:text-blue-400 transition leading-tight text-xs">{{ $lead->lead_name ?: 'Без имени' }}</div>
                            <div class="text-[10px] text-gray-600 mt-1 font-mono tracking-tighter">{{ $lead->phone ?? 'Телефон не указан' }}</div>
                        </td>
                        <td class="p-5">
                             @if($lead->crmStatus)
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-tight" 
                                    style="background: {{ $lead->crmStatus->color ?? '#3b82f6' }}10; color: {{ $lead->crmStatus->color ?? '#3b82f6' }}">
                                    {{ $lead->crmStatus->name }}
                                </span>
                            @else
                                <span class="text-gray-700 text-[10px]">Неизвестно</span>
                            @endif
                        </td>
                        <td class="p-5 text-right font-mono text-gray-400">
                            {{ $lead->deal && $lead->deal->revenue > 0 ? '₽' . number_format($lead->deal->revenue, 0, ',', ' ') : '—' }}
                        </td>
                        <td class="p-5 text-center text-gray-600 font-mono text-xs">
                            {{ $lead->created_at_source->format('d.m.Y') }}
                        </td>
                        <td class="p-5 text-right max-w-[150px]">
                             @php $sc = $lead->attributed_campaign_id ? ($campaignStatsMap[$lead->attributed_campaign_id] ?? null) : null; @endphp
                             @if($sc)
                                <div class="flex flex-col items-end">
                                    <span class="text-white/60 font-black text-[9px] leading-tight text-right truncate uppercase tracking-tighter">{{ $sc['name'] }}</span>
                                    <span class="text-yellow-600/60 text-[9px] font-mono mt-0.5">MATCHED</span>
                                </div>
                             @else
                                <span class="text-gray-800 text-[10px] uppercase font-bold tracking-widest opacity-20">Organic</span>
                             @endif
                        </td>
                        <td class="p-5 text-right">
                            @if($lead->qualified_at)
                                <span class="text-green-500 font-black text-sm">✓</span>
                            @else
                                <span class="text-gray-800 opacity-20">—</span>
                            @endif
                        </td>
                        <td class="p-5 text-right">
                             @if($lead->status == 142 || ($lead->deal && $lead->deal->status === 'won'))
                                <span class="text-indigo-400 font-black text-sm">🏆</span>
                             @elseif($lead->status == 143 || ($lead->deal && $lead->deal->status === 'lost'))
                                <span class="text-red-900/40 text-xs italic">Lost</span>
                             @else
                                <span class="text-gray-800 opacity-20">—</span>
                             @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if ($leads->hasPages())
            <div class="px-8 py-5 border-t border-[#2a2e39]">
                {{ $leads->links() }}
            </div>
        @endif
    </div>
</div>
