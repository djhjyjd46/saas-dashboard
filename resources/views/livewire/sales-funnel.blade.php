<div class="p-6 rounded-2xl border" style="background-color: #1a1d24; border-color: #2a2e39;">
    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-6">Воронка продаж</h3>

    <div class="flex items-center justify-between gap-2 overflow-x-auto pb-2">
        <!-- Stage 1: Clicks -->
        <div class="flex-1 min-w-[150px] p-4 rounded-xl border relative"
            style="background-color: #1f2937; border-color: #374151;">
            <div class="text-sm text-gray-400 mb-1">Клики</div>
            <div class="text-2xl font-bold text-blue-400 mb-3">{{ $clicks }}</div>
            <div class="h-1 w-full bg-blue-500 rounded-full"></div>
            <!-- Arrow / Conversion -->
            <div class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 text-xs text-gray-400 font-medium">
                {{ $convClickToLead }}% &rarr;
            </div>
        </div>

        <!-- Stage 2: Leads -->
        <div class="flex-1 min-w-[150px] p-4 rounded-xl border relative ml-4"
            style="background-color: #1f2937; border-color: #374151;">
            <div class="text-sm text-gray-400 mb-1">Лиды</div>
            <div class="text-2xl font-bold text-gray-200 mb-3">{{ $leads }}</div>
            <div class="h-1 w-2/3 bg-gray-500 rounded-full"></div>
            <!-- Arrow / Conversion -->
            <div class="absolute -right-4 top-1/2 -translate-y-1/2 z-10 text-xs text-gray-400 font-medium">
                {{ $convLeadToSale }}% &rarr;
            </div>
        </div>

        <!-- Stage 3: Sales -->
        <div class="flex-1 min-w-[150px] p-4 rounded-xl border ml-4"
            style="background-color: #332d18; border-color: #4d4424;">
            <div class="text-sm text-gray-400 mb-1">Продажи</div>
            <div class="text-2xl font-bold text-yellow-400 mb-3">{{ $sales }}</div>
            <div class="h-1 w-1/4 bg-yellow-500 rounded-full"></div>
        </div>
    </div>
</div>
