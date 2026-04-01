<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    <!-- Расход -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
            </svg>
            <span class="text-sm font-medium uppercase">Расход</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $spend }} ₽</div>
        <div class="h-8 flex items-end">
            <svg class="w-full h-full text-yellow-500" viewBox="0 0 100 20" preserveAspectRatio="none">
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $spendSparkline }}">
                </polyline>
            </svg>
        </div>
    </div>

    <!-- Лиды -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z">
                </path>
            </svg>
            <span class="text-sm font-medium uppercase">Лиды</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $leads }}</div>
        <div class="h-8 flex items-end">
            <svg class="w-full h-full text-blue-500" viewBox="0 0 100 20" preserveAspectRatio="none">
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $leadsSparkline }}">
                </polyline>
            </svg>
        </div>
    </div>

    <!-- Квал. лиды -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium uppercase">Квал. лиды</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $qualLeads }}</div>
        <div class="h-8 flex items-end">
            <svg class="w-full h-full text-purple-500" viewBox="0 0 100 20" preserveAspectRatio="none">
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $qualLeadsSparkline }}">
                </polyline>
            </svg>
        </div>
    </div>
    
    <!-- Успешные сделки -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="text-sm font-medium uppercase">Продажи</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $success }}</div>
        <div class="h-8 flex items-end text-green-500/50 text-xs font-medium">
            CR в оплату: {{ $salesCr }}%
        </div>
    </div>

    <!-- Цена лида -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            <span class="text-sm font-medium uppercase">CPL</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $cpl }} ₽</div>
        <div class="h-8 flex items-end text-gray-500 text-xs">
            Средняя цена
        </div>
    </div>
</div>
