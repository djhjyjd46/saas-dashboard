<?php

namespace App\Services;

use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\View;

class ThemeService
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function getView(string $view): string
    {
        $user = auth()->user();
        if ($user && $user->role === 'admin') {
            $theme = 'default';
        } else {
            $theme = ($user && !empty($user->theme)) ? $user->theme : 'default';
        }

        $themedView = "themes.{$theme}.{$view}";

        if (View::exists($themedView)) {
            return $themedView;
        }

        // Fallback to default theme if it exists
        $defaultThemeView = "themes.default.{$view}";
        if (View::exists($defaultThemeView)) {
            return $defaultThemeView;
        }

        return $view; // Final fallback to root views
    }
}
