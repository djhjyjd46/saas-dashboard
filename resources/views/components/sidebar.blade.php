@php
    $view = app(\App\Services\ThemeService::class)->getView('components.sidebar');
@endphp

@include($view, $attributes->all())
