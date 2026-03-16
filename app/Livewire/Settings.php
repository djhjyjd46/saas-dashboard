<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ThemeService;

class Settings extends Component
{
    protected ThemeService $themeService;

    public function boot(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function logout()
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect()->route('login');
    }

    public function render()
    {
        $layout = $this->themeService->getView('layouts.app');
        return view($this->themeService->getView('components.settings'))
            ->layout($layout, ['header' => 'Настройки']);
    }
}
