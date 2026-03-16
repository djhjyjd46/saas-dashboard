<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<x-head :title="$title ?? 'Войти'" />

<body class="font-sans antialiased min-h-screen flex items-center justify-center" style="background-color: #13161b;">
    {{ $slot }}

    @livewireScripts
</body>

</html>
