<!-- Integrations Center -->
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Центр интеграций</h2>
            <p class="text-gray-400 mt-1">
                @if (auth()->user()->isAdmin())
                    Подключите рекламные кабинеты для автоматического сбора данных.
                @else
                    Подключите вашу CRM для отслеживания лидов и сделок.
                @endif
            </p>
        </div>

        <div class="relative items-center gap-3 flex">
            <button wire:click="$toggle('showAddMenu')"
                class="px-5 py-2.5 bg-[#2a2e39] hover:bg-[#374151] text-gray-300 font-bold rounded-xl transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Добавить интеграцию
            </button>
            @if ($showAddMenu)
                <div
                    class="absolute right-0 top-full mt-2 w-48 bg-[#181b21] border border-[#2a2e39] rounded-xl shadow-2xl z-50 py-2 overflow-hidden">
                    <button wire:click="createAmoIntegration"
                        class="w-full flex items-center gap-3 text-left px-4 py-3 hover:bg-[#2a2e39] text-white text-sm transition font-medium">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                            </path>
                        </svg>
                        AmoCRM
                    </button>
                    <div
                        class="w-full flex items-center gap-3 text-left px-4 py-3 text-gray-600 text-sm transition font-medium cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                            </path>
                        </svg>
                        Bitrix24 (скоро)
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if (session()->has('yandex_status') || $yandexStatus || $statusMessage)
        <div class="p-4 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 rounded-xl animate-fade-in">
            {{ session('yandex_status') ?? ($yandexStatus ?: $statusMessage) }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
        {{-- Yandex.Direct Card --}}
        @if (auth()->user()->isAdmin())
            <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-6 transition hover:border-yellow-500/30">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-12 h-12 bg-yellow-500/10 rounded-xl flex items-center justify-center text-yellow-500">
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
                        <div class="p-4 bg-[#13161b] rounded-xl border border-[#2a2e39] space-y-4">
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-300 mb-1">ID счётчика Метрики</label>
                                    <input type="text" wire:model="yandexMetrikaCounterId" placeholder="Например 106629539"
                                        class="w-full bg-[#0d1017] border border-[#2a2e39] rounded-lg px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-yellow-500/50">
                                    <p class="text-[11px] text-gray-600 mt-1">ID счётчика Яндекс.Метрики (нужен для фильтрации заявок по цели).</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-300 mb-1">ID цели «Заявка»</label>
                                    <div class="flex gap-2">
                                        <input type="text" wire:model="yandexGoalIds" placeholder="ID целей через запятую"
                                            class="flex-1 bg-[#0d1017] border border-[#2a2e39] rounded-lg px-3 py-2 text-white text-sm placeholder-gray-600 focus:outline-none focus:border-yellow-500/50">
                                        <button wire:click="saveYandexGoalIds"
                                            class="px-4 py-2 bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-400 text-xs font-semibold rounded-lg border border-emerald-500/20 transition">
                                            Сохранить
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-gray-600 mt-1">ID цели из Метрики (например, цель «Заявка»). Сохранит оба поля сразу.</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-gray-400 leading-relaxed">
                        Для подключения просто нажмите кнопку ниже и авторизуйтесь в Яндексе. Ваша статистика
                        автоматически
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

                    @if ($isYandexConnected)
                        <button wire:click="syncYandex" wire:loading.attr="disabled"
                            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-[#2a2e39] text-white hover:bg-[#323744] rounded-lg transition text-sm font-semibold border border-white/5">
                            <span wire:loading.remove wire:target="syncYandex">Запустить синхронизацию</span>
                            <span wire:loading wire:target="syncYandex">Синхронизирую...</span>
                        </button>
                    @endif
                </div>
            </div>
        @endif

        {{-- AmoCRM Cards List --}}
        @foreach ($amoIntegrations as $index => $amoIntegration)
            <div wire:key="amo-{{ $amoIntegration['id'] }}"
                class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-6 transition hover:border-blue-500/30 {{ !$amoIntegration['is_connected'] ? 'opacity-90' : '' }}">
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
                            <input wire:model.live.debounce.500ms="amoIntegrations.{{ $index }}.name"
                                type="text"
                                class="bg-transparent text-lg font-bold text-white focus:outline-none focus:border-b focus:border-blue-500/50 w-full"
                                placeholder="Новая интеграция">
                            <p class="text-sm text-gray-500">Сделки и воронка</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if ($amoIntegration['is_connected'])
                            <span
                                class="px-3 py-1 bg-emerald-500/10 text-emerald-400 text-xs font-bold rounded-full border border-emerald-500/20 uppercase tracking-wider text-[10px]">Активно</span>
                        @else
                            <span
                                class="px-3 py-1 bg-gray-500/10 text-gray-400 text-xs font-bold rounded-full border border-gray-500/20 uppercase tracking-wider text-[10px]">Ключи
                                не заданы</span>
                        @endif

                        <button wire:click="deleteAmoIntegration({{ $amoIntegration['id'] }})"
                            wire:confirm="Точно удалить интеграцию?"
                            class="text-red-400 hover:bg-red-500/10 p-1.5 rounded transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    {{-- Credential Inputs --}}
                    <div class="grid grid-cols-1 gap-5 p-6 bg-[#13161b] rounded-xl border border-[#2a2e39]">
                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-500 mb-2 tracking-wider">Домен
                                AmoCRM</label>
                            <input wire:model.defer="amoIntegrations.{{ $index }}.domain" type="text"
                                placeholder="mycompany.amocrm.ru" autocomplete="new-password"
                                class="w-full px-4 py-3 bg-[#181b21] border border-[#2a2e39] rounded-lg text-white text-base focus:outline-none focus:border-blue-500/50 transition">
                        </div>
                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-500 mb-2 tracking-wider">Client
                                ID</label>
                            <input wire:model.defer="amoIntegrations.{{ $index }}.client_id" type="text"
                                placeholder="Укажите Integrator ID" autocomplete="new-password"
                                class="w-full px-4 py-3 bg-[#181b21] border border-[#2a2e39] rounded-lg text-white text-base focus:outline-none focus:border-blue-500/50 transition">
                        </div>
                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-500 mb-2 tracking-wider">Client
                                Secret</label>
                            <input wire:model.defer="amoIntegrations.{{ $index }}.client_secret" type="text"
                                placeholder="Вставьте секрет..." autocomplete="new-password"
                                class="w-full px-4 py-3 bg-[#181b21] border border-[#2a2e39] rounded-lg text-white text-base focus:outline-none focus:border-blue-500/50 transition">
                        </div>
                        <div>
                            <label class="block text-xs uppercase font-bold text-gray-500 mb-2 tracking-wider">Refresh
                                Token
                                (опционально)
                            </label>
                            <input wire:model.defer="amoIntegrations.{{ $index }}.refresh_token"
                                type="text" placeholder="Для ручного обновления..." autocomplete="new-password"
                                class="w-full px-4 py-3 bg-[#181b21] border border-[#2a2e39] rounded-lg text-white text-base focus:outline-none focus:border-blue-500/50 transition">
                        </div>
                        <button wire:click="saveAmoKeys({{ $index }})"
                            class="w-full py-3 bg-blue-600/10 hover:bg-blue-600/20 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-bold uppercase tracking-widest transition">
                            Сохранить настройки
                        </button>
                        @if (session('amo_keys_saved_' . $amoIntegration['id']))
                            <div class="p-3 bg-emerald-500/10 border border-emerald-500/20 rounded-lg">
                                <p class="text-xs text-emerald-400 font-semibold text-center italic">
                                    {{ session('amo_keys_saved_' . $amoIntegration['id']) }}</p>
                            </div>
                        @endif
                    </div>

                    @if ($amoIntegration['is_connected'])
                        <div class="p-4 bg-[#13161b] rounded-xl border border-[#2a2e39] space-y-3">
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400">Загружено лидов:</span>
                                <span class="text-white font-bold">{{ $amoIntegration['leads_count'] }}</span>
                            </div>
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-400">Успешных сделок:</span>
                                <span class="text-emerald-400 font-bold">{{ $amoIntegration['deals_count'] }}</span>
                            </div>
                            <button wire:click="syncAmo({{ $amoIntegration['id'] }})" wire:loading.attr="disabled"
                                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 bg-[#2a2e39] text-white hover:bg-[#323744] rounded-lg transition text-sm font-semibold border border-white/5">
                                <span wire:loading.remove wire:target="syncAmo({{ $amoIntegration['id'] }})">Синхронизировать данные</span>
                                <span wire:loading wire:target="syncAmo({{ $amoIntegration['id'] }})">Загрузка...</span>
                            </button>
                        </div>
                    @endif

                    <p class="text-xs text-gray-400 leading-relaxed">
                        Настройте интеграцию и нажмите кнопку ниже для OAuth-авторизации в вашем кабинете AmoCRM.
                    </p>

                    <a href="{{ route('integrations.amocrm', ['id' => $amoIntegration['id']]) }}"
                        class="w-full inline-flex items-center justify-center gap-2 px-6 py-3 bg-blue-600 hover:bg-blue-500 text-white font-bold rounded-xl transition shadow-lg shadow-blue-500/20">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 16l2.879-2.879m0 0a3 3 0 104.243-4.242 3 3 0 00-4.243 4.242zM21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ $amoIntegration['is_connected'] ? 'Переподключить аккаунт' : 'Авторизовать AmoCRM' }}
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
