<div class="relative" x-data="{
    open: false,
    fpStart: null,
    fpEnd: null,
    initPickers() {
        this.fpStart = flatpickr(this.$refs.startInput, {
            locale: 'ru',
            dateFormat: 'Y-m-d',
            defaultDate: '{{ $startDate }}',
            disableMobile: true,
            onChange: (dates, dateStr) => {
                if (dateStr) {
                    @this.set('startDate', dateStr);
                }
            }
        });
        this.fpEnd = flatpickr(this.$refs.endInput, {
            locale: 'ru',
            dateFormat: 'Y-m-d',
            defaultDate: '{{ $endDate }}',
            disableMobile: true,
            onChange: (dates, dateStr) => {
                if (dateStr) {
                    @this.set('endDate', dateStr);
                }
            }
        });
    }
}" x-init="initPickers()">

    <button @click="open = !open"
        class="flex items-center gap-4 text-sm text-gray-400 border rounded-lg px-3 py-1.5 hover:bg-[#2a2e39] transition"
        style="border-color: #2a2e39;">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
            </path>
        </svg>
        <span>{{ $this->formattedDate }}</span>
    </button>

    <div x-show="open" @mousedown.away="open = false" x-transition
        class="absolute right-0 mt-2 w-72 bg-[#1a1d24] border border-[#2a2e39] rounded-xl shadow-2xl z-50 p-4 space-y-4"
        style="display: none; box-shadow: 0 10px 30px -5px rgba(0,0,0,0.5);">

        {{-- Quick presets --}}
        <div>
            <label class="text-[10px] text-gray-500 uppercase font-bold tracking-tight">Предустановки</label>
            <div class="grid grid-cols-1 gap-1 mt-2">
                <button type="button"
                    wire:click="updatePeriod('{{ now()->subDays(6)->format('Y-m-d') }}', '{{ now()->format('Y-m-d') }}')"
                    @click="open = false"
                    class="text-left px-3 py-2 text-sm text-gray-300 hover:bg-[#2a2e39] rounded-lg transition-colors">Последние
                    7 дней</button>
                <button type="button"
                    wire:click="updatePeriod('{{ now()->subDays(29)->format('Y-m-d') }}', '{{ now()->format('Y-m-d') }}')"
                    @click="open = false"
                    class="text-left px-3 py-2 text-sm text-gray-300 hover:bg-[#2a2e39] rounded-lg transition-colors">Последние
                    30 дней</button>
                <button type="button"
                    wire:click="updatePeriod('{{ now()->startOfMonth()->format('Y-m-d') }}', '{{ now()->format('Y-m-d') }}')"
                    @click="open = false"
                    class="text-left px-3 py-2 text-sm text-gray-300 hover:bg-[#2a2e39] rounded-lg transition-colors">Этот
                    месяц</button>
                <button type="button"
                    wire:click="updatePeriod('{{ now()->subMonth()->startOfMonth()->format('Y-m-d') }}', '{{ now()->subMonth()->endOfMonth()->format('Y-m-d') }}')"
                    @click="open = false"
                    class="text-left px-3 py-2 text-sm text-gray-300 hover:bg-[#2a2e39] rounded-lg transition-colors">Прошлый
                    месяц</button>
            </div>
        </div>

        {{-- Custom range with flatpickr --}}
        <div class="border-t border-[#2a2e39] pt-4">
            <label class="text-[10px] text-gray-500 uppercase font-bold tracking-tight">Свой период</label>
            <div class="grid grid-cols-1 gap-3 mt-2" @mousedown.stop @click.stop>
                <div>
                    <span class="text-[9px] text-gray-500 uppercase block mb-1">Начало</span>
                    <input type="text" x-ref="startInput" placeholder="Выберите дату"
                        class="w-full bg-[#13161b] border border-[#2a2e39] rounded-lg px-3 py-2 text-xs text-gray-300
                            focus:outline-none focus:ring-1 focus:ring-yellow-500/50 cursor-pointer">
                </div>
                <div>
                    <span class="text-[9px] text-gray-500 uppercase block mb-1">Конец</span>
                    <input type="text" x-ref="endInput" placeholder="Выберите дату"
                        class="w-full bg-[#13161b] border border-[#2a2e39] rounded-lg px-3 py-2 text-xs text-gray-300
                            focus:outline-none focus:ring-1 focus:ring-yellow-500/50 cursor-pointer">
                </div>
            </div>
        </div>
    </div>
</div>
