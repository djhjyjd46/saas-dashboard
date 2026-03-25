<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\AdCampaign;
use App\Models\Lead;
use App\Models\Integration;
use App\Models\Tenant;
use App\Services\ThemeService;
use Illuminate\Support\Facades\Hash;

class Clients extends Component
{
    use WithPagination;

    public string $search = '';

    // Off-canvas state
    public bool $showPanel = false;
    public string $activeTab = 'profile';

    // Profile form
    public ?int $editingUserId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $role = 'client';
    public string $theme = 'default';

    public $bulkIds = ''; // For bulk pasting IDs
    public string $sortBy = 'name';
    public string $sortDir = 'asc';

    public array $uiMapping = []; // [['name' => '...', 'campaigns' => '...']]

    public function toggleSort(string $field)
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }
    }

    protected ThemeService $themeService;

    public function boot(ThemeService $themeService): void
    {
        $this->themeService = $themeService;
    }

    public function rules(): array
    {
        return [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . ($this->editingUserId ?? 'NULL'),
            'role'     => 'required|in:admin,client',
            'theme'    => 'required|in:default,gold',
            'password' => $this->editingUserId ? 'nullable|min:6' : 'required|min:6',
        ];
    }

    public function applyBulkIds()
    {
        if (empty($this->bulkIds)) return;

        // Space or comma separated
        $ids = preg_split('/[\s,]+/', $this->bulkIds, -1, PREG_SPLIT_NO_EMPTY);
        $newIds = array_map('strval', array_map('trim', $ids));

        // Check if ALL these IDs are already in the assigned list
        $currentSelection = array_map('strval', $this->assignedCampaignIds);
        $allPresent = true;
        foreach ($newIds as $id) {
            if (!in_array($id, $currentSelection)) {
                $allPresent = false;
                break;
            }
        }

        if ($allPresent) {
            // Deselect all from the list
            $this->assignedCampaignIds = array_diff($currentSelection, $newIds);
            session()->flash('bulk_status', 'Снято!');
        } else {
            // Select all from the list (merge with existing)
            $this->assignedCampaignIds = array_unique(array_merge($currentSelection, $newIds));
            session()->flash('bulk_status', 'Выбрано!');
        }
    }

    public function openPanel(?int $userId = null): void
    {
        $this->reset(['name', 'email', 'password', 'role', 'theme', 'editingUserId', 'uiMapping', 'bulkIds']);
        $this->theme = 'default';
        $this->activeTab = 'profile';
        $this->editingUserId = $userId;

        if ($userId) {
            $user = User::findOrFail($userId);
            $this->name  = $user->name;
            $this->email = $user->email;
            $this->role  = $user->role;
            $this->theme = $user->theme ?? 'default';

            // Load category mapping UI
            $settings = $user->campaignSettings();
            $settingsUiMapping = $settings['category_mapping_ui'] ?? [];

            // Fallback: if category_mapping_ui is empty but category_mapping has data, rebuild from it
            if (empty($settingsUiMapping) && !empty($settings['category_mapping'])) {
                foreach ($settings['category_mapping'] as $catName => $ids) {
                    $settingsUiMapping[$catName] = implode(', ', (array) $ids);
                }
            }

            $this->uiMapping = [];
            foreach ($settingsUiMapping as $catName => $campaignsStr) {
                $this->uiMapping[] = ['name' => $catName, 'campaigns' => $campaignsStr];
            }
            if (empty($this->uiMapping)) {
                $this->uiMapping[] = ['name' => '', 'campaigns' => ''];
            }
        }

        $this->showPanel = true;
    }

    public function closePanel(): void
    {
        $this->showPanel = false;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
            'theme' => $this->theme,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->editingUserId) {
            User::findOrFail($this->editingUserId)->update($data);
        } else {
            if ($this->role === 'client') {
                // Each client gets their own isolated tenant for CRM integrations
                $tenant = Tenant::create([
                    'name'         => $this->name,
                    'active_theme' => $this->theme,
                    'settings'     => [],
                ]);
                $data['tenant_id'] = $tenant->id;
            } else {
                // Admins share the current admin's tenant
                $data['tenant_id'] = auth()->user()->tenant_id;
            }
            User::create($data);
        }

        $this->showPanel = false;
        $this->reset(['name', 'email', 'password', 'role', 'theme', 'editingUserId']);
        $this->theme = 'default';
    }

    public function saveCampaigns(): void
    {
        if (!$this->editingUserId) return;

        $user = User::findOrFail($this->editingUserId);
        $tenant = $user->tenant;

        if (!$tenant) return;

        $settings = is_array($user->settings) ? $user->settings : [];
        $mapping = [];
        $externalIds = [];
        $settingsUiMapping = [];

        foreach ($this->uiMapping as $row) {
            $name = trim($row['name'] ?? '');
            $idsStr = trim($row['campaigns'] ?? '');

            if ($name === '' || $idsStr === '') continue;

            // Keep the UI row exactly as entered, even if marker expansion finds no IDs.
            $settingsUiMapping[$name] = $idsStr;

            $ids = $this->expandCampaignTokensToIds($tenant->id, $idsStr);

            $mapping[$name] = $ids;

            if (!empty($ids)) {
                $externalIds = array_merge($externalIds, $ids);
            }
        }

        $settings['category_mapping_ui'] = $settingsUiMapping;
        $settings['category_mapping'] = $mapping;
        $settings['allowed_external_ids'] = array_unique($externalIds);

        $user->update(['settings' => $settings]);

        session()->flash('campaigns_saved', 'Кампании успешно сохранены. (' . now()->format('H:i:s') . ')');
    }

    public function addCategoryRow()
    {
        $this->uiMapping[] = ['name' => '', 'campaigns' => ''];
    }

    public function removeCategoryRow($index)
    {
        unset($this->uiMapping[$index]);
        $this->uiMapping = array_values($this->uiMapping); // re-index
    }

    public function moveCategoryRowUp(int $index): void
    {
        if ($index <= 0 || !isset($this->uiMapping[$index], $this->uiMapping[$index - 1])) {
            return;
        }

        [$this->uiMapping[$index - 1], $this->uiMapping[$index]] = [$this->uiMapping[$index], $this->uiMapping[$index - 1]];
    }

    public function moveCategoryRowDown(int $index): void
    {
        if (!isset($this->uiMapping[$index], $this->uiMapping[$index + 1])) {
            return;
        }

        [$this->uiMapping[$index], $this->uiMapping[$index + 1]] = [$this->uiMapping[$index + 1], $this->uiMapping[$index]];
    }

    public function delete(int $userId): void
    {
        if ($userId === auth()->id()) {
            session()->flash('error', 'Вы не можете удалить самого себя.');
            return;
        }

        User::findOrFail($userId)->delete();
        $this->resetPage();
    }

    /**
     * Parses IDs and marker tokens from UI input.
     * Marker token format: _something (matches campaign name substring, case-insensitive).
     */
    private function expandCampaignTokensToIds(int $tenantId, string $input): array
    {
        $tokens = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', $input))));
        $externalIds = [];
        $markers = [];

        foreach ($tokens as $token) {
            if (str_starts_with($token, '_') && mb_strlen($token) > 1) {
                // If every symbol is important, we don't cut the leading underscore.
                $marker = mb_strtolower($token);
                if ($marker !== '') {
                    $markers[] = $marker;
                }
                continue;
            }

            $externalIds[] = $token;
        }

        $campaigns = AdCampaign::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->get(['external_id', 'name']);

        $campaignIdsSet = [];
        foreach ($campaigns as $campaign) {
            $campaignIdsSet[(string) $campaign->external_id] = true;
        }

        // Fetch leads to map UTMs to campaigns
        $leads = Lead::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('meta_data')
            ->get(['meta_data']);

        $utmToCampIds = [];
        $allLeadRefs = [];
        foreach ($leads as $lead) {
            $meta = is_array($lead->meta_data) ? $lead->meta_data : [];
            $utm = trim((string)($meta['utm_campaign'] ?? ''));
            $cid = trim((string)($meta['campaign_id'] ?? ''));

            if ($utm !== '') {
                $allLeadRefs[] = $utm;
                $utmToCampIds[$utm][] = $cid;
            }
            if ($cid !== '') $allLeadRefs[] = $cid;
        }

        // 1. Process markers
        if (!empty($markers)) {
            // Match campaign names (case-insensitive substring with normalization)
            foreach ($campaigns as $campaign) {
                $campName = mb_strtolower((string)$campaign->name);
                $campNameNormalized = $this->normalizeMarkerText($campName);
                foreach ($markers as $marker) {
                    $markerNormalized = $this->normalizeMarkerText($marker);
                    if ($markerNormalized !== '' && str_contains($campNameNormalized, $markerNormalized)) {
                        $externalIds[] = (string) $campaign->external_id;
                        break;
                    }
                }
            }

            // Match UTM tags from leads (normalized substring)
            foreach (array_unique($allLeadRefs) as $ref) {
                $refLower = mb_strtolower($ref);
                $refNormalized = $this->normalizeMarkerText($refLower);
                foreach ($markers as $marker) {
                    $markerNormalized = $this->normalizeMarkerText($marker);
                    if ($markerNormalized !== '' && str_contains($refNormalized, $markerNormalized)) {
                        if (isset($utmToCampIds[$ref])) {
                            foreach ($utmToCampIds[$ref] as $mappedCid) {
                                $externalIds[] = $mappedCid;
                            }
                        }
                        break;
                    }
                }
            }
        }

        // 2. Process non-marker tokens
        foreach ($tokens as $token) {
            // 1. Exact match via leads mapping (Atomic Match)
            if (isset($utmToCampIds[$token])) {
                foreach ($utmToCampIds[$token] as $mappedCid) {
                    $externalIds[] = $mappedCid;
                }
            }

            if (str_starts_with($token, '_')) continue;

            // Direct match as ID
            if (isset($campaignIdsSet[$token])) {
                $externalIds[] = $token;
            }
        }

        return array_values(array_unique(array_filter($externalIds)));
    }

    private function normalizeMarkerText(string $value): string
    {
        $value = mb_strtolower($value);
        return preg_replace('/[\s_\-]+/u', '', $value) ?? '';
    }

    public function render()
    {
        $users = User::where(function ($q) {
            $q->where('name', 'like', '%' . $this->search . '%')
                ->orWhere('email', 'like', '%' . $this->search . '%');
        })
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // All campaigns visible to the admin (for assignment UI)
        $allCampaigns = AdCampaign::withoutGlobalScopes()->get();


        $clientIntegrations = null;
        if ($this->editingUserId) {
            $editingUser = User::find($this->editingUserId);
            if ($editingUser?->tenant) {
                $clientIntegrations = Integration::withoutGlobalScopes()
                    ->where('tenant_id', $editingUser->tenant->id)
                    ->get();
            }
        }

        $layout = $this->themeService->getView('layouts.app');

        return view($this->themeService->getView('components.admin.clients'), [
            'users'              => $users,
            'clientIntegrations' => $clientIntegrations,
        ])->layout($layout, ['header' => 'Клиенты']);
    }
}
