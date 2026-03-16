<!-- Integrations Center -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Центр интеграций</h2>
            <p class="text-gray-400 mt-1">Подключите рекламные кабинеты для автоматического сбора данных.</p>
        </div>
    </div>

    @if (session()->has('yandex_status') || $yandexStatus || $statusMessage)
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl animate-fade-in">
            {{ session('yandex_status') ?? ($yandexStatus ?: $statusMessage) }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Yandex Direct -->
        <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-6 transition hover:border-yellow-500/30">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-yellow-500/10 rounded-xl flex items-center justify-center text-yellow-500">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.5 12h2.5l-4.5 8v-8h-2.5l4.5-8v8z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">Яндекс Директ</h3>
                        <p class="text-sm text-gray-500">Расходы, клики, показы</p>
                    </div>
                </div>
                @if ($isYandexConnected)
                    <span
                        class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-full border border-emerald-500/20 uppercase tracking-wider text-[10px]">Активно</span>
                @else
                    <span
                        class="px-3 py-1 bg-gray-500/10 text-gray-400 text-xs font-bold rounded-full border border-gray-500/20 uppercase tracking-wider text-[10px]">Не
                        подключено</span>
                @endif
            </div>

            <div class="space-y-4">
                @if ($isYandexConnected)
                    <div class="p-4 bg-[#13161b] rounded-xl border border-[#2a2e39]">
                        <button wire:click="syncYandex" wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-[#2a2e39] text-white hover:bg-[#323744] rounded-lg transition text-sm font-semibold border border-white/5">
                            <span wire:loading.remove wire:target="syncYandex">Запустить синхронизацию</span>
                            <span wire:loading wire:target="syncYandex">Синхронизирую...</span>
                        </button>
                    </div>
                @endif

                <p class="text-xs text-gray-400 leading-relaxed">
                    Для подключения просто нажмите кнопку ниже и авторизуйтесь в Яндексе. Ваша статистика автоматически
                    начнет синхронизироваться через доверенный аккаунт.
                </p>

                <a href="{{ route('integrations.yandex') }}"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-xl transition shadow-lg shadow-yellow-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                        </path>
                    </svg>
                    {{ $isYandexConnected ? 'Переподключить аккаунт' : 'Подключить Яндекс.Директ' }}
                </a>
            </div>
        </div>

        <!-- AmoCRM Card -->
        <div
            class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-6 transition hover:border-blue-500/30 {{ !$isAmoConnected ? 'opacity-60' : '' }}">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">AmoCRM</h3>
                        <p class="text-sm text-gray-500">Сделки и воронка</p>
                    </div>
                </div>
                @if ($isAmoConnected)
                    <span
                        class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-full border border-emerald-500/20 uppercase tracking-wider text-[10px]">Активно</span>
                @else
                    <span
                        class="px-3 py-1 bg-gray-500/10 text-gray-400 text-xs font-bold rounded-full border border-gray-500/20 uppercase tracking-wider text-[10px]">Не
                        подключено</span>
                @endif
            </div>

            <div class="space-y-4">
                @if ($isAmoConnected)
                    <div class="p-4 bg-[#13161b] rounded-xl border border-[#2a2e39] space-y-3">
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">Загружено лидов:</span>
                            <span class="text-white font-bold">{{ $amoLeadsCount }}</span>
                        </div>
                        <div class="flex justify-between text-xs">
                            <span class="text-gray-400">Успешных сделок:</span>
                            <span class="text-emerald-400 font-bold">{{ $amoDealsCount }}</span>
                        </div>
                        <button wire:click="syncAmo" wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-[#2a2e39] text-white hover:bg-[#323744] rounded-lg transition text-sm font-semibold border border-white/5">
                            <span wire:loading.remove wire:target="syncAmo">Синхронизировать данные</span>
                            <span wire:loading wire:target="syncAmo">Загрузка...</span>
                        </button>
                    </div>
                @endif

                <p class="text-xs text-gray-400 leading-relaxed">
                    Подключите вашу воронку AmoCRM для отслеживания конверсии из рекламных кликов в реальные сделки и
                    выручку.
                </p>

                <a href="{{ route('integrations.amocrm') }}"
                    class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $isAmoConnected ? 'Переподключить аккаунт' : 'Подключить AmoCRM' }}
                </a>
            </div>
        </div>
    </div>
</div>
