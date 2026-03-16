@php
    $view = app(\App\Services\ThemeService::class)->getView('components.head');
@endphp

@include($view, $attributes->all())
