<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<x-head :title="$title ?? 'Дашборд Яндекс Директ'" />

<body class="text-gray-100 font-sans antialiased flex h-screen overflow-hidden" style="background-color: #13161b;">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="h-16 flex items-center justify-between px-8 flex-shrink-0"
            style="background-color: #181b21; border-bottom: 1px solid #2a2e39;">
            <h2 class="text-xl font-semibold text-white">
                {{ $header ?? 'Дашборд' }}
            </h2>
            <livewire:date-filter />
        </header>

        <!-- Page Content -->
        <div class="flex-1 overflow-y-auto p-8 relative">
            {{ $slot }}
        </div>
    </main>

    @livewireScripts
</body>

</html>
