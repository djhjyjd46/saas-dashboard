@php
    $layout = app(\App\Services\ThemeService::class)->getView('layouts.app');
@endphp

@include($layout, ['slot' => $slot])
