<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
});

Route::get('/login', \App\Livewire\Auth\Login::class)->name('login')->middleware('guest');
Route::post('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');

Route::middleware(['auth', \App\Http\Middleware\TenantMiddleware::class])->group(function () {
    Route::get('/integrations/yandex', [\App\Http\Controllers\YandexAuthController::class, 'redirect'])->name('integrations.yandex');
    Route::get('/integrations/yandex/callback', [\App\Http\Controllers\YandexAuthController::class, 'callback'])->name('yandex.callback');
    Route::get('/integrations/amocrm', [\App\Http\Controllers\AmoCrmAuthController::class, 'redirect'])->name('integrations.amocrm');
    Route::get('/dashboard', \App\Livewire\Dashboard::class)->name('dashboard');
    Route::get('/campaigns', \App\Livewire\AdsCampaigns::class)->name('campaigns');
    Route::get('/integrations', \App\Livewire\IntegrationsManager::class)->name('integrations');
    Route::get('/settings', \App\Livewire\Settings::class)->name('settings');

    // Admin-only routes
    Route::middleware(\App\Http\Middleware\IsAdmin::class)->group(function () {
        Route::get('/admin/clients', \App\Livewire\Admin\Clients::class)->name('admin.clients');
        Route::get('/admin/ads', \App\Livewire\AdsCampaigns::class)->name('admin.ads');

        // Technical Settings (Strictly for Gold Theme)
        Route::get('/technical-settings', [\App\Http\Controllers\Themes\Gold\TechnicalSettingsController::class, 'index'])->name('technical.settings');
        Route::post('/technical-settings', [\App\Http\Controllers\Themes\Gold\TechnicalSettingsController::class, 'store'])->name('technical.settings.save');
    });
});

Route::get('/integrations/amocrm/callback', [\App\Http\Controllers\AmoCrmAuthController::class, 'callback'])->name('amocrm.callback');

