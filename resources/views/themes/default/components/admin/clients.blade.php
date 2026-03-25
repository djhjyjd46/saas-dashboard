<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Управление клиентами</h2>
            <p class="text-gray-400 mt-1">Добавляйте пользователей и настраивайте их уровни доступа.</p>
        </div>
        <button wire:click="openPanel"
            class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-xl transition shadow-lg shadow-yellow-500/10 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Добавить клиента
        </button>
    </div>

    {{-- Search --}}
    <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl p-4">
        <div class="relative max-w-md">
            <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0" />
            </svg>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Поиск по имени или email..."
                class="w-full pl-10 pr-4 py-2 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white placeholder-gray-600 focus:outline-none focus:border-yellow-500/50 transition">
        </div>
    </div>

    {{-- Users Table --}}
    <div class="bg-[#181b21] border border-[#2a2e39] rounded-2xl overflow-hidden">
        <table class="w-full text-left">
            <thead>
                <tr class="bg-[#11141b] border-b border-[#2a2e39] text-xs font-bold text-gray-500 uppercase">
                    <th class="px-6 py-4">Клиент</th>
                    <th class="px-6 py-4 text-center">Роль</th>
                    <th class="px-6 py-4 text-center">Тема</th>
                    <th class="px-6 py-4">Дата регистрации</th>
                    <th class="px-6 py-4 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#2a2e39]">
                @foreach ($users as $user)
                    <tr class="hover:bg-[#2a2e39]/20 transition-all group cursor-pointer"
                        wire:click="openPanel({{ $user->id }})">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-[#eab308]/10 rounded-lg flex items-center justify-center font-bold text-yellow-500">
                                    {{ mb_substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <div class="text-white font-medium">{{ $user->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border {{ $user->role === 'admin' ? 'bg-purple-500/10 text-purple-400 border-purple-500/20' : 'bg-blue-500/10 text-blue-400 border-blue-500/20' }}">
                                {{ $user->role === 'admin' ? 'Админ' : 'Клиент' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span
                                class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                {{ ($user->theme ?? 'default') === 'gold' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/20' }}">
                                {{ ($user->theme ?? 'default') === 'gold' ? 'Gold' : 'Default' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $user->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-1" wire:click.stop>
                            <button wire:click="openPanel({{ $user->id }})"
                                class="p-2 text-gray-400 hover:text-yellow-500 transition" title="Редактировать">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            @if ($user->id !== auth()->id())
                                <button wire:confirm="Вы уверены, что хотите удалить этого пользователя?"
                                    wire:click="delete({{ $user->id }})"
                                    class="p-2 text-gray-400 hover:text-red-500 transition" title="Удалить">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="px-6 py-4 border-t border-[#2a2e39]">
            {{ $users->links() }}
        </div>
    </div>

    {{-- ============================================================
         OFF-CANVAS SLIDE-OVER PANEL
    ============================================================ --}}
    @if ($showPanel)
        {{-- Backdrop --}}
        <div class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm transition-opacity" wire:click="closePanel"></div>

        {{-- Panel --}}
        <div class="fixed inset-y-0 right-0 z-50 flex flex-col w-full max-w-2xl bg-[#181b21] border-l border-[#2a2e39] shadow-2xl"
            style="animation: slideIn 0.28s cubic-bezier(0.4,0,0.2,1);">

            {{-- Panel Header --}}
            <div class="flex items-center justify-between px-8 py-6 border-b border-[#2a2e39] flex-shrink-0">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-yellow-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-white">
                            {{ $editingUserId ? ($name ?: 'Редактировать клиента') : 'Новый клиент' }}
                        </h3>
                        <p class="text-xs text-gray-500">{{ $editingUserId ? $email : 'Заполните данные ниже' }}</p>
                    </div>
                </div>
                <button wire:click="closePanel"
                    class="p-2 text-gray-500 hover:text-white hover:bg-[#2a2e39] rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Tabs & Actions --}}
            <div class="flex items-center justify-between px-8 border-b border-[#2a2e39] flex-shrink-0">
                <div class="flex gap-0">
                    @php
                        $tabs = [
                            'profile' => [
                                'label' => 'Профиль',
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                            ],
                            'campaigns' => [
                                'label' => 'Кампании',
                                'icon' =>
                                    'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
                            ],
                            'integrations' => [
                                'label' => 'Интеграции',
                                'icon' =>
                                    'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z',
                            ],
                        ];
                        if (!$editingUserId) {
                            $tabs = ['profile' => $tabs['profile']];
                        }
                    @endphp

                    @foreach ($tabs as $key => $tab)
                        <button wire:click="setTab('{{ $key }}')"
                            class="flex items-center gap-2 px-4 py-4 text-xs font-semibold transition border-b-2
                            {{ $activeTab === $key
                                ? 'text-yellow-400 border-yellow-500'
                                : 'text-gray-500 border-transparent hover:text-gray-300' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="{{ $tab['icon'] }}" />
                            </svg>
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>

                @if ($activeTab === 'campaigns')
                    <button wire:click="saveCampaigns"
                        class="px-4 py-2 bg-yellow-500 hover:bg-yellow-400 text-black text-[11px] font-bold rounded-lg transition active:translate-y-0.5 whitespace-nowrap">
                        Сохранить изменения
                    </button>
                @endif
            </div>

            {{-- Panel Body (scrollable) --}}
            <div class="flex-1 overflow-y-auto p-8">

                {{-- ── TAB: PROFILE ── --}}
                @if ($activeTab === 'profile')
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Имя</label>
                            <input wire:model="name" type="text"
                                class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                            @error('name')
                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email</label>
                            <input wire:model="email" type="email"
                                class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                            @error('email')
                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Роль</label>
                                <select wire:model="role"
                                    class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                                    <option value="client">Клиент</option>
                                    <option value="admin">Админ</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Тема</label>
                                <select wire:model="theme"
                                    class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                                    <option value="default">Default</option>
                                    <option value="gold">Gold</option>
                                </select>
                                @error('theme')
                                    <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase mb-2">
                                Пароль {{ $editingUserId ? '(оставьте пустым для сохранения текущего)' : '' }}
                            </label>
                            <input wire:model="password" type="password"
                                class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                            @error('password')
                                <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="pt-4 flex justify-end gap-3">
                            <button type="button" wire:click="closePanel"
                                class="px-6 py-2.5 bg-[#2a2e39] text-gray-400 font-bold rounded-xl hover:bg-[#374151] transition">
                                Отмена
                            </button>
                            <button type="submit"
                                class="px-6 py-2.5 bg-yellow-500 text-black font-bold rounded-xl hover:bg-yellow-400 transition">
                                Сохранить
                            </button>
                        </div>
                    </form>
                @endif

                {{-- ── TAB: CAMPAIGNS ── --}}
                @if ($activeTab === 'campaigns')
                    <div class="relative">
                        <div class="space-y-6">
                            @if (session('campaigns_saved'))
                                <div x-data="{ show: true }" x-key="{{ session('campaigns_saved') }}"
                                    x-init="show = true;
                                    setTimeout(() => show = false, 3000)" x-show="show" x-transition.opacity
                                    class="fixed top-20 left-1/2 -translate-x-1/2 z-[60] px-5 py-2.5 rounded-xl bg-emerald-600 text-white text-[12px] font-medium shadow-2xl flex items-center gap-2 border border-emerald-400/20">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    {{ session('campaigns_saved') }}
                                </div>
                            @endif

                            <div class="mb-6">
                                <div
                                    class="flex gap-4 mb-3 text-[10px] font-bold text-gray-500 uppercase tracking-widest px-2">
                                    <div class="w-1/4">Название категории</div>
                                    <div class="w-2/4">ID кампаний или метки (_glavnaya_vuz)</div>
                                    <div class="w-1/4"></div>
                                </div>

                                <div class="space-y-3">
                                    @foreach ($uiMapping as $index => $row)
                                        <div class="flex gap-4 items-start" wire:key="cat-row-{{ $index }}">
                                            <input type="text" wire:model="uiMapping.{{ $index }}.name"
                                                class="w-1/4 bg-[#13161b] border border-[#2a2e39] rounded-xl px-4 py-2.5 text-sm text-white focus:border-yellow-500/50 focus:outline-none transition placeholder-gray-700"
                                                placeholder="Название...">

                                            <input type="text"
                                                wire:model="uiMapping.{{ $index }}.campaigns"
                                                class="w-2/4 bg-[#13161b] border border-[#2a2e39] rounded-xl px-4 py-2.5 text-sm text-white focus:border-yellow-500/50 focus:outline-none transition placeholder-gray-700"
                                                placeholder="707572867, _glavnaya_vuz...">

                                            <button type="button"
                                                wire:click="removeCategoryRow({{ $index }})"
                                                class="text-red-400 hover:text-red-300 hover:bg-red-500/10 px-4 py-2.5 rounded-xl text-xs font-bold transition-colors">
                                                Удалить
                                            </button>

                                            <div class="flex flex-col gap-1">
                                                <button type="button"
                                                    wire:click="moveCategoryRowUp({{ $index }})"
                                                    class="text-gray-300 hover:text-white hover:bg-white/10 px-2 py-1.5 rounded-lg text-xs transition-colors"
                                                    title="Поднять выше">
                                                    ▲
                                                </button>
                                                <button type="button"
                                                    wire:click="moveCategoryRowDown({{ $index }})"
                                                    class="text-gray-300 hover:text-white hover:bg-white/10 px-2 py-1.5 rounded-lg text-xs transition-colors"
                                                    title="Опустить ниже">
                                                    ▼
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="flex items-center pb-2">
                                <button type="button" wire:click="addCategoryRow"
                                    class="px-5 py-2.5 bg-[#2a2e39] hover:bg-[#374151] text-gray-300 text-[11px] font-bold rounded-xl transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Добавить строку
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- ── TAB: INTEGRATIONS ── --}}
                @if ($activeTab === 'integrations')
                    <div class="space-y-4">
                        <div class="flex items-start gap-3 p-4 rounded-xl bg-[#13161b] border border-[#2a2e39]">
                            <svg class="w-5 h-5 text-gray-400 flex-shrink-0 mt-0.5" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-sm text-gray-400">
                                Интеграции, подключённые к тенанту этого клиента. Яндекс.Директ управляется глобально.
                            </p>
                        </div>

                        @if (!$clientIntegrations || $clientIntegrations->isEmpty())
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-700" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                </svg>
                                <p class="text-sm">Нет подключённых интеграций</p>
                                <p class="text-xs text-gray-600 mt-1">Клиент может подключить amoCRM в своём личном
                                    кабинете</p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($clientIntegrations as $integration)
                                    <div
                                        class="flex items-center justify-between p-4 rounded-xl border border-[#2a2e39] bg-[#13161b]">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-9 h-9 rounded-lg flex items-center justify-center
                                                {{ $integration->type === 'yandex' ? 'bg-red-500/10' : 'bg-purple-500/10' }}">
                                                <svg class="w-4 h-4 {{ $integration->type === 'yandex' ? 'text-red-400' : 'text-purple-400' }}"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-semibold text-white">
                                                    {{ $integration->type === 'yandex' ? 'Яндекс.Директ' : 'amoCRM' }}
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    Подключено {{ $integration->created_at->format('d.m.Y') }}
                                                </p>
                                            </div>
                                        </div>
                                        <span
                                            class="flex items-center gap-1.5 text-xs font-semibold
                                            {{ $integration->is_active ? 'text-green-400' : 'text-gray-500' }}">
                                            <span
                                                class="w-1.5 h-1.5 rounded-full {{ $integration->is_active ? 'bg-green-400' : 'bg-gray-600' }}"></span>
                                            {{ $integration->is_active ? 'Активно' : 'Неактивно' }}
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endif

            </div>
        </div>

        <style>
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                }

                to {
                    transform: translateX(0);
                }
            }
        </style>
    @endif
</div>
