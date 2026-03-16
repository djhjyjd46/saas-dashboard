<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-bold text-white">Управление клиентами</h2>
            <p class="text-gray-400 mt-1">Добавляйте пользователей и настраивайте их уровни доступа.</p>
        </div>
        <button wire:click="openModal"
            class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-xl transition shadow-lg shadow-yellow-500/10 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Добавить клиента
        </button>
    </div>

    <!-- Search and Filters -->
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

    <!-- Users Table -->
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
                    <tr class="hover:bg-[#2a2e39]/20 transition-all group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-[#eab308]/10 rounded-lg flex items-center justify-center font-bold text-yellow-500">
                                    {{ substr($user->name, 0, 1) }}
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
                            <span class="px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border
                                {{ ($user->theme ?? 'default') === 'gold' ? 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20' : 'bg-gray-500/10 text-gray-400 border-gray-500/20' }}">
                                {{ ($user->theme ?? 'default') === 'gold' ? 'Gold' : 'Default' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $user->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="openModal({{ $user->id }})"
                                class="p-2 text-gray-400 hover:text-yellow-500 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>
                            @if ($user->id !== auth()->id())
                                <button wire:confirm="Вы уверены, что хотите удалить этого пользователя?"
                                    wire:click="delete({{ $user->id }})"
                                    class="p-2 text-gray-400 hover:text-red-500 transition">
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

    <!-- Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-black/70 backdrop-blur-sm transition-opacity"
                    wire:click="$set('showModal', false)"></div>

                <div
                    class="relative bg-[#181b21] border border-[#2a2e39] rounded-2xl max-w-lg w-full overflow-hidden shadow-2xl">
                    <div class="p-8">
                        <h3 class="text-xl font-bold text-white mb-6">
                            {{ $editingUserId ? 'Редактировать клиента' : 'Добавить клиента' }}</h3>

                        <form wire:submit="save" class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Имя</label>
                                <input wire:model="name" type="text"
                                    class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                                @error('name')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Email</label>
                                <input wire:model="email" type="email"
                                    class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                                @error('email')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
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
                                        <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase mb-2">Пароль
                                    {{ $editingUserId ? '(оставьте пустым для сохранения текущего)' : '' }}</label>
                                <input wire:model="password" type="password"
                                    class="w-full px-4 py-2.5 bg-[#13161b] border border-[#2a2e39] rounded-xl text-white focus:outline-none focus:border-yellow-500/50 transition">
                                @error('password')
                                    <span class="text-red-500 text-xs mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="pt-6 flex justify-end gap-3">
                                <button type="button" wire:click="$set('showModal', false)"
                                    class="px-6 py-2.5 bg-gray-800 text-gray-400 font-bold rounded-xl hover:bg-gray-700 transition">Отмена</button>
                                <button type="submit"
                                    class="px-6 py-2.5 bg-yellow-500 text-black font-bold rounded-xl hover:bg-yellow-400 transition">Сохранить</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
