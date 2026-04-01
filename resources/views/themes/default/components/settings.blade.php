<div class="max-w-4xl mx-auto">
    <div class="bg-[#181b21] rounded-2xl border border-[#2a2e39] overflow-hidden">
        <div class="p-8 border-b border-[#2a2e39]">
            <h3 class="text-xl font-bold text-white">Настройки профиля</h3>
            <p class="text-gray-400 mt-1">Управление вашей учетной записью и предпочтениями.</p>
        </div>

        <div class="p-8 space-y-8">
            <!-- User Info Section -->
            <div class="flex items-center gap-6">
                <div class="w-20 h-20 bg-gradient-to-br from-[#eab308] to-[#f59e0b] rounded-2xl flex items-center justify-center text-3xl font-bold text-black shadow-lg shadow-yellow-500/10">
                    {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div>
                    <h4 class="text-lg font-semibold text-white">{{ auth()->user()->name }}</h4>
                    <p class="text-gray-400">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Notifications (Placeholder) -->
                <div class="p-6 bg-[#13161b] rounded-xl border border-[#2a2e39] group hover:border-gray-600 transition">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-blue-500/10 rounded-lg text-blue-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <div class="w-10 h-5 bg-[#2a2e39] rounded-full relative cursor-not-allowed">
                            <div class="absolute left-1 top-1 w-3 h-3 bg-gray-500 rounded-full"></div>
                        </div>
                    </div>
                    <h5 class="text-white font-medium">Уведомления</h5>
                    <p class="text-sm text-gray-500 mt-1">Получать отчеты о кампаниях в Telegram.</p>
                </div>

                <!-- Theme (Placeholder) -->
                <div class="p-6 bg-[#13161b] rounded-xl border border-[#2a2e39] group hover:border-gray-600 transition">
                    <div class="flex items-center justify-between mb-4">
                        <div class="p-3 bg-purple-500/10 rounded-lg text-purple-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        </div>
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Dark Mode</span>
                    </div>
                    <h5 class="text-white font-medium">Тема оформления</h5>
                    <p class="text-sm text-gray-500 mt-1">Всегда использовать темную тему.</p>
                </div>
            </div>

            <!-- System Logs Section -->
            <div class="pt-8 border-t border-[#2a2e39]">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h4 class="text-white font-semibold uppercase text-xs tracking-widest">Логи системы</h4>
                        <p class="text-gray-500 text-sm mt-1">Просмотр последних событий и отладка.</p>
                    </div>
                    <button wire:click="loadLogs" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-yellow-500/10 hover:bg-yellow-500/20 text-yellow-500 rounded-lg transition duration-300 font-medium flex items-center gap-2">
                        <svg wire:loading.class="animate-spin" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Обновить
                    </button>
                </div>
                
                @if($logs)
                <div class="relative group">
                    <textarea readonly class="w-full h-64 bg-[#13161b] text-gray-400 font-mono text-[10px] p-4 rounded-xl border border-[#2a2e39] focus:border-yellow-500/50 focus:ring-0 resize-none overflow-y-auto"
                        id="logViewer">{{ $logs }}</textarea>
                    <button onclick="copyLogs()" class="absolute top-4 right-4 p-2 bg-[#2a2e39] hover:bg-gray-700 text-gray-300 rounded-lg transition opacity-100 sm:opacity-0 group-hover:opacity-100 shadow-xl">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3" />
                        </svg>
                    </button>
                </div>
                <script>
                    function copyLogs() {
                        const el = document.getElementById('logViewer');
                        el.select();
                        document.execCommand('copy');
                        alert('Логи скопированы в буфер обмена');
                    }
                </script>
                @else
                <div class="p-8 bg-[#13161b] rounded-xl border border-[#2a2e39] text-center group cursor-pointer" wire:click="loadLogs">
                    <p class="text-gray-500 group-hover:text-gray-300 transition">Нажмите «Обновить» или сюда для загрузки последних логов.</p>
                </div>
                @endif
            </div>

            <!-- Danger Zone -->
            <div class="pt-8 border-t border-[#2a2e39]">
                <h4 class="text-red-500 font-semibold mb-4 uppercase text-xs tracking-widest">Безопасность</h4>
                <button wire:click="logout" 
                    class="flex items-center gap-3 px-6 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 rounded-xl transition duration-300 font-medium group">
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    Выйти из системы
                </button>
            </div>
        </div>
    </div>
</div>
