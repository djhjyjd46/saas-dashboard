<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Аналитика Яндекс Директ | analytics.stashevski.by</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-[#13161b] text-gray-100 font-sans overflow-x-hidden">
    <!-- Background subtle gradient -->
    <div
        class="fixed inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,rgba(234,179,8,0.05),transparent_40%),radial-gradient(circle_at_bottom_left,rgba(30,58,138,0.05),transparent_40%)]">
    </div>

    <!-- Header / Nav -->
    <header class="max-w-7xl mx-auto px-6 py-8 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-yellow-500 rounded-xl flex items-center justify-center text-black font-bold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <span class="text-xl font-bold tracking-tight">StashevskiStudio</span>
        </div>
        <div class="flex gap-4">
            @auth
                <a href="{{ route('dashboard') }}"
                    class="px-5 py-2.5 bg-yellow-500 hover:bg-yellow-400 text-black border border-yellow-500 rounded-xl transition font-bold">
                    Панель управления
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="px-5 py-2.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition font-medium">
                    Войти
                </a>
            @endauth
        </div>
    </header>

    <!-- Hero Section -->
    <main class="max-w-7xl mx-auto px-6 py-20 lg:py-32">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
            <div>
                <span
                    class="inline-block px-4 py-1.5 bg-yellow-500/10 text-yellow-500 rounded-full text-sm font-bold uppercase tracking-widest mb-6">
                    Professional Dashboard v2.0
                </span>
                <h1 class="text-5xl lg:text-7xl font-extrabold text-white leading-tight mb-8">
                    Управляйте рекламой <br />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-500 to-amber-500">как
                        профессионал</span>
                </h1>
                <p class="text-xl text-gray-400 max-w-lg mb-12 leading-relaxed">
                    Единая панель управления для анализа кампаний Яндекс Директ, отслеживания лидов и автоматизации
                    отчетности.
                </p>
                <div class="flex flex-wrap gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="px-8 py-4 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-2xl transition shadow-xl shadow-yellow-500/20 text-center">
                            В личный кабинет
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="px-8 py-4 bg-yellow-500 hover:bg-yellow-400 text-black font-bold rounded-2xl transition shadow-xl shadow-yellow-500/20 text-center">
                            Открыть дашборд
                        </a>
                    @endauth
                    <a href="#features"
                        class="px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-bold rounded-2xl transition border border-white/5 text-center">
                        Подробнее
                    </a>
                </div>
            </div>

            <!-- Dashboard Preview Mockup -->
            <div class="relative">
                <div class="absolute -inset-4 bg-yellow-500/20 blur-3xl rounded-full opacity-20"></div>
                <div
                    class="relative bg-[#181b21] rounded-3xl border border-[#2a2e39] overflow-hidden shadow-2xl skew-y-3 transform hover:skew-y-0 transition-transform duration-700">
                    <div class="flex items-center gap-2 p-4 border-b border-[#2a2e39]">
                        <div class="flex gap-1.5">
                            <div class="w-3 h-3 rounded-full bg-red-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-yellow-500/50"></div>
                            <div class="w-3 h-3 rounded-full bg-green-500/50"></div>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="h-20 bg-[#13161b] rounded-xl border border-[#2a2e39]"></div>
                            <div class="h-20 bg-[#13161b] rounded-xl border border-[#2a2e39]"></div>
                            <div class="h-20 bg-[#13161b] rounded-xl border border-[#2a2e39]"></div>
                        </div>
                        <div class="h-48 bg-[#13161b] rounded-xl border border-[#2a2e39] w-full"></div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Features Section -->
    <section id="features" class="max-w-7xl mx-auto px-6 py-20">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div
                class="p-8 bg-[#181b21] rounded-3xl border border-[#2a2e39] hover:border-yellow-500/30 transition group">
                <div
                    class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-400 mb-6 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Мгновенная статистика</h3>
                <p class="text-gray-400">Синхронизация данных с Яндекс Директ в реальном времени.</p>
            </div>
            <div
                class="p-8 bg-[#181b21] rounded-3xl border border-[#2a2e39] hover:border-yellow-500/30 transition group">
                <div
                    class="w-12 h-12 bg-green-500/10 rounded-2xl flex items-center justify-center text-green-400 mb-6 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Воронка продаж</h3>
                <p class="text-gray-400">Наглядная визуализация конверсии от клика до сделки в AmoCRM.</p>
            </div>
            <div
                class="p-8 bg-[#181b21] rounded-3xl border border-[#2a2e39] hover:border-yellow-500/30 transition group">
                <div
                    class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-400 mb-6 group-hover:scale-110 transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m12 0a2 2 0 100-4m0 4a2 2 0 110-4m-6 0a2 2 0 100 4m0-4a2 2 0 110 4m-6 0v2m0-6V4m6 6V4m6 2v2" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Гибкие настройки</h3>
                <p class="text-gray-400">Настраивайте фильтры по датам и типам кампаний в пару кликов.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="border-t border-[#2a2e39] py-12 text-center text-gray-500">
        <p>© 2026 analytics.stashevski.by. Сделано для эффективного маркетинга.</p>
    </footer>
</body>

</html>
