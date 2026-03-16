<?php

namespace App\Http\Controllers\Themes\Gold;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantManager;
use App\Services\ThemeService;
use Illuminate\Http\Request;

class TechnicalSettingsController extends Controller
{
    public function index(TenantManager $tenantManager, ThemeService $themeService)
    {
        $user = auth()->user();

        // Strictly Gold theme only
        if (!$user || $user->theme !== 'gold') {
            abort(404);
        }

        $tenant = $tenantManager->getTenant();
        $uiMapping = $tenant->settings['category_mapping_ui'] ?? [];

        $view = $themeService->getView('technical-settings');
        $layout = $themeService->getView('layouts.app');

        return view($view, [
            'ui_mapping' => $uiMapping,
            'layout' => $layout
        ]);
    }

    public function store(Request $request, TenantManager $tenantManager)
    {
        $user = auth()->user();
        if (!$user || $user->theme !== 'gold') {
            abort(404);
        }

        $tenant = $tenantManager->getTenant();
        $settings = $tenant->settings ?? [];

        $names = $request->input('category_names', []);
        $campaignsLists = $request->input('category_campaigns', []);

        $mapping = [];
        $externalIds = [];
        $uiMapping = [];

        foreach ($names as $index => $name) {
            $name = trim($name);
            $idsStr = trim($campaignsLists[$index] ?? '');

            if ($name === '' || $idsStr === '') continue;

            // Split by comma, tab, or newlines to be safe, then trim
            $ids = array_filter(array_map('trim', preg_split('/[\s,]+/', $idsStr)));

            if (!empty($ids)) {
                $mapping[$name] = $ids;
                $externalIds = array_merge($externalIds, $ids);
                $uiMapping[$name] = implode(', ', $ids);
            }
        }

        $settings['category_mapping_ui'] = $uiMapping;
        $settings['category_mapping'] = $mapping;
        $settings['allowed_external_ids'] = array_unique($externalIds);

        $tenant->update(['settings' => $settings]);

        return back()->with('message', 'Настройки успешно сохранены. ' . count(array_unique($externalIds)) . ' кампаний настроено.');
    }
}
