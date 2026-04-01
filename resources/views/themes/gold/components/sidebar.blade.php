<div class="left w-1/5 px-5 py-8 flex flex-col justify-between h-full">
    <div class="space-y-8">
        <!-- Main Navigation -->
        <div class="flex flex-col gap-4 text-xl font-bold">
            <a href="{{ route('dashboard') }}" 
               class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ request()->routeIs('dashboard') ? 'sidebar-active' : '' }}">
                <div class="status-circle {{ request()->routeIs('dashboard') ? 'gold-bg' : '' }}"></div>
                <span class="{{ request()->routeIs('dashboard') ? 'gold-text' : 'text-white' }}">Дашборд</span>
            </a>
            
            <a href="{{ route('leads') }}" 
               class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ request()->routeIs('leads') ? 'sidebar-active' : '' }}">
                <div class="status-circle {{ request()->routeIs('leads') ? 'gold-bg' : '' }}"></div>
                <span class="{{ request()->routeIs('leads') ? 'gold-text' : 'text-white' }}">Лиды</span>
            </a>

            <a href="{{ route('integrations') }}" 
               class="flex items-center gap-4 px-4 py-2 cursor-pointer group {{ request()->routeIs('integrations') ? 'sidebar-active' : '' }}">
                <div class="status-circle {{ request()->routeIs('integrations') ? 'gold-bg' : '' }}"></div>
                <span class="{{ request()->routeIs('integrations') ? 'gold-text' : 'text-white' }}">Интеграции</span>
            </a>
        </div>

        <div class="h-[2px] bg-gold/30 w-full"></div>

        <!-- Filter Context (Only on Dashboard) -->
        @if(request()->routeIs('dashboard'))
            <div class="flex flex-col gap-4 text-xl font-medium">
                <div class="flex items-center gap-4 mb-2 px-4 py-2">
                    <svg class="w-6 h-6 text-gold" fill="currentColor" viewBox="0 0 24 24"><path d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    <span>Период</span>
                </div>
                {{-- These should ideally be part of a Livewire component if they need to be interactive --}}
                <div class="text-xs text-gray-400 px-4">Управление периодом доступно на дашборде</div>
            </div>
        @endif
    </div>

    <!-- Bottom Actions -->
    <div class="pr-8 flex flex-col gap-3">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 px-4 py-2 text-white/60 hover:text-white transition-colors group">
                <svg class="w-6 h-6 group-hover:text-gold transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                <span class="font-bold text-lg">Выйти</span>
            </button>
        </form>
    </div>
</div>
