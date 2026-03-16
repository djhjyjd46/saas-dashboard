<div class="p-6 rounded-2xl border h-full" style="background-color: #1a1d24; border-color: #2a2e39;">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Источники лидов</h3>
        @if ($crmIsEmpty)
            <span class="text-xs px-2 py-0.5 rounded-full bg-yellow-500/10 text-yellow-500/70">нет данных CRM</span>
        @endif
    </div>

    <div class="space-y-6">
        @foreach ($sources as $source)
            <div>
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-300">{{ $source['name'] }}</span>
                    <span class="text-gray-500">
                        {{ $source['leads'] }}
                        @if (is_numeric($source['leads']))
                            ({{ $source['percentage'] }}%)
                        @endif
                    </span>
                </div>
                <div class="w-full bg-gray-800 rounded-full h-1.5 mb-2">
                    <div class="bg-yellow-500 h-1.5 rounded-full transition-all duration-500"
                        style="width: {{ $source['percentage'] }}%"></div>
                </div>
                <div class="flex justify-between text-xs">
                    <span class="text-gray-500">
                        {{ $source['sales'] }}
                        @if ($source['sales'] !== '—')
                            продаж
                        @endif
                    </span>
                    <span class="text-yellow-500 font-medium">
                        {{ number_format($source['revenue'], 0, ',', ' ') }} ₽
                        @if ($crmIsEmpty)
                            <span class="text-gray-600 font-normal">(расход)</span>
                        @endif
                    </span>
                </div>
            </div>
        @endforeach
    </div>

    @if ($crmIsEmpty)
        <p class="mt-6 text-xs text-gray-700 leading-relaxed">
            Лиды и продажи появятся после синхронизации с amoCRM.
        </p>
    @endif
</div>
