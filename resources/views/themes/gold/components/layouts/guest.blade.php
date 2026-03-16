<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<x-head :title="$title ?? 'Вход | Дашборд Яндекс Директ'" />

<body
    class="antialiased bg-[#13161b] text-gray-100 font-sans min-h-screen flex flex-col justify-center items-center p-6">
    {{ $slot }}
    @livewireScripts
</body>

</html>
