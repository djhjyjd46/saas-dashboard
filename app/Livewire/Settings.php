<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\ThemeService;

class Settings extends Component
{
    protected ThemeService $themeService;
    public $logs = "";

    public function boot(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function loadLogs()
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) {
            $this->logs = "Log file not found at: " . $logPath;
            return;
        }
        
        // Grep CRM related logs first if requested? No, just full tail for now as requested.
        $output = shell_exec("tail -n 200 " . escapeshellarg($logPath));
        $this->logs = $output ?: "Log file is empty or unreachable.";
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
