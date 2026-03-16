<div class="p-6 rounded-2xl border h-full flex flex-col" style="background-color: #1a1d24; border-color: #2a2e39;"
    x-data="{
        buildChart() {
                const canvas = document.getElementById('trendChart');
                if (!canvas) return;
    
                // Chart.getChart finds any chart already bound to this canvas element
                const existing = Chart.getChart(canvas);
                if (existing) existing.destroy();
    
                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels: JSON.parse(this.$wire.labels),
                        datasets: [{
                            label: 'Расход',
                            data: JSON.parse(this.$wire.spendData),
                            borderColor: '#ec4899',
                            backgroundColor: 'rgba(236,72,153,0.05)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4
                        }, {
                            label: 'Доход',
                            data: JSON.parse(this.$wire.incomeData),
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139,92,246,0.05)',
                            fill: true,
                            tension: 0.4,
                            borderWidth: 2,
                            pointRadius: 0,
                            pointHoverRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#111317',
                                borderColor: '#2a2e39',
                                borderWidth: 1,
                                titleColor: '#9ca3af',
                                bodyColor: '#fff',
                                padding: 12,
                                callbacks: {
                                    label: ctx => {
                                        let v = ctx.raw;
                                        return ' ' + ctx.dataset.label + ': ' + (v >= 1000 ? (v / 1000).toFixed(1) + 'к' : Math.round(v)) + ' ₽';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: { display: true, grid: { display: false }, ticks: { color: '#4b5563', font: { size: 10 }, maxRotation: 0, maxTicksLimit: 10 } },
                            y: { display: false }
                        }
                    }
                });
            },
            init() {
                this.$nextTick(() => this.buildChart());
    
                // Watch the Livewire labels property — rebuilds chart whenever period changes
                this.$wire.$watch('labels', () => {
                    this.$nextTick(() => this.buildChart());
                });
            }
    }">
    <div class="flex justify-between items-center mb-6">
        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Тренд: Яндекс.Директ</h3>
        <div class="flex gap-2">
            <span class="px-3 py-1 text-xs rounded-full bg-pink-500/10 text-pink-400 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-pink-500"></span> Расход
            </span>
            <span class="px-3 py-1 text-xs rounded-full bg-purple-500/10 text-purple-400 flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Доход
            </span>
        </div>
    </div>

    {{-- wire:ignore prevents Livewire from touching the canvas DOM (Chart.js owns it) --}}
    <div class="flex-1 min-h-[250px] relative w-full" wire:ignore>
        <canvas id="trendChart"></canvas>
    </div>
</div>
