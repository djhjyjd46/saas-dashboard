<?php

namespace App\Livewire\Client;

use Livewire\Component;
use App\Models\AdCampaign;
use App\Models\User;
use App\Services\ThemeService;
use Livewire\WithPagination;

class Campaigns extends Component
{
    use WithPagination;

    public string $search = '';

    protected ThemeService $themeService;

    public function boot(ThemeService $themeService): void
    {
        $this->themeService = $themeService;
    }

    public function render()
    {
        /** @var User|null $user */
        $user = auth()->user();
        $settings = $user?->campaignSettings() ?? [];
        $allowedExternalIds = $settings['allowed_external_ids'] ?? [];

        $campaigns = AdCampaign::withoutGlobalScopes()
            ->whereIn('external_id', $allowedExternalIds)
            ->where('name', 'like', '%' . $this->search . '%')
            ->orderBy('name')
            ->paginate(15);

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.client.campaigns'), [
            'campaigns' => $campaigns
        ])->layout($layout, ['header' => 'Мои кампании']);
    }
}
