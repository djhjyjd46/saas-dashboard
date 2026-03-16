<?php

namespace App\Services;

use App\Services\Tenancy\TenantManager;
use Illuminate\Support\Facades\View;

class ThemeService
{
    public function __construct(protected TenantManager $tenantManager) {}

    public function getView(string $view): string
    {
        $tenant = $this->tenantManager->getTenant();
        $theme = $tenant ? $tenant->active_theme : 'default';

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
