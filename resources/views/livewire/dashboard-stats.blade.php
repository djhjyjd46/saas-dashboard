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
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $spendSparkline }}"></polyline>
            </svg>
        </div>
    </div>

    <!-- Доход -->
    <div class="p-6 rounded-2xl border flex flex-col justify-between"
        style="background-color: #1a1d24; border-color: #2a2e39;">
        <div class="flex items-center gap-2 text-gray-400 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
            </svg>
            <span class="text-sm font-medium uppercase">Доход</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $income }} ₽</div>
        <div class="h-8 flex items-end">
            <svg class="w-full h-full text-green-500" viewBox="0 0 100 20" preserveAspectRatio="none">
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $incomeSparkline }}"></polyline>
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
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $leadsSparkline }}"></polyline>
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
            <span class="text-sm font-medium uppercase">Квал-лиды</span>
        </div>
        <div class="text-3xl font-bold text-white mb-4">{{ $qualLeads }}</div>
        <div class="h-8 flex items-end">
            <svg class="w-full h-full text-purple-500" viewBox="0 0 100 20" preserveAspectRatio="none">
                <polyline fill="none" stroke="currentColor" stroke-width="2" points="{{ $qualLeadsSparkline }}"></polyline>
            </svg>
        </div>
    </div>
</div>
