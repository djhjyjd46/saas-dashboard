@props(['activeBrand' => ''])
@php
    $navActive = 'bg-[#2a2e39] text-[#eab308]';
    $navInactive = 'text-gray-400 hover:text-white hover:bg-[#2a2e39]';
    $isAdmin = auth()->check() && auth()->user()->role === 'admin';
@endphp

<aside class="w-64 border-r flex-shrink-0 flex flex-col h-full overflow-y-auto custom-scrollbar"
    style="background-color: #181b21; border-color: #2a2e39;">
    <div class="p-6">
        <h1 class="text-xl font-bold text-white tracking-wider flex items-center gap-2">
            <div class="w-8 h-8 bg-yellow-500 rounded-lg flex items-center justify-center text-black font-black">S</div>
            Stashevski
        </h1>
    </div>

    @if ($isAdmin)
        <nav class="mt-4 px-4 space-y-2 flex-1">
            @if (Route::has('dashboard'))
                <a href="{{ route('dashboard') }}"
                    class="{{ request()->routeIs('dashboard') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            </path>
                        </svg>
                        Сводка
                    </span>
                </a>
            @endif

            @if (Route::has('admin.clients'))
                <a href="{{ route('admin.clients') }}"
                    class="{{ request()->routeIs('admin.clients') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        Клиенты
                    </span>
                </a>
            @endif

            @if (Route::has('admin.ads'))
                <a href="{{ route('admin.ads') }}"
                    class="{{ request()->routeIs('admin.ads') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                            </path>
                        </svg>
                        Реклама
                    </span>
                </a>
            @endif

            @if (Route::has('integrations'))
                <a href="{{ route('integrations') }}"
                    class="{{ request()->routeIs('integrations') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                            </path>
                        </svg>
                        Интеграции
                    </span>
                </a>
            @endif
        </nav>
    @else
        <nav class="mt-4 px-4 space-y-2 flex-1">
            @if (Route::has('dashboard'))
                <a href="{{ route('dashboard') }}"
                    class="{{ request()->routeIs('dashboard') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                            </path>
                        </svg>
                        Сводка
                    </span>
                </a>
            @endif

            @if (Route::has('campaigns'))
                <a href="{{ route('campaigns') }}"
                    class="{{ request()->routeIs('campaigns') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z">
                            </path>
                        </svg>
                        Реклама
                    </span>
                </a>
            @endif

            @if (Route::has('integrations'))
                <a href="{{ route('integrations') }}"
                    class="{{ request()->routeIs('integrations') ? $navActive : $navInactive }} block px-4 py-2 rounded-lg font-medium transition">
                    <span class="flex items-center gap-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 011-1h1a2 2 0 100-4H7a1 1 0 01-1-1V7a1 1 0 011-1h3a1 1 0 001-1V4z">
                            </path>
                        </svg>
                        Интеграции
                    </span>
                </a>
            @endif
        </nav>
    @endif

    <div class="mt-auto p-4 border-t border-[#2a2e39] bg-[#181b21]/50 backdrop-blur-sm">
        @auth
            <div class="flex items-center gap-3">
                <div
                    class="w-9 h-9 bg-gradient-to-tr from-yellow-500 to-amber-600 rounded-lg flex items-center justify-center font-bold text-black border border-white/10 shadow-lg">
                    {{ mb_substr(auth()->user()->name ?? 'A', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-gray-500 truncate">{{ auth()->user()->email }}</p>
                </div>
            </div>

            <div class="mt-4 flex justify-between gap-1">
                @if (Route::has('settings'))
                    <a href="{{ route('settings') }}"
                        class="text-xs text-gray-500 hover:text-yellow-500 transition px-2 py-1">
                        Настройки
                    </a>
                @endif
                @if (Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full text-left text-xs text-red-500/60 hover:text-red-500 transition px-2 py-1">
                            Выйти
                        </button>
                    </form>
                @endif
            </div>
        @else
            <div class="flex flex-col gap-2">
                <a href="{{ Route::has('login') ? route('login') : '/login' }}"
                    class="w-full py-2 px-4 rounded-lg bg-yellow-500 text-black font-bold text-center text-sm">
                    Войти
                </a>
            </div>
        @endauth
    </div>
</aside>
