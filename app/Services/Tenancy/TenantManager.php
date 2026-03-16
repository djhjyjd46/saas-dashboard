<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantManager
{
    protected ?Tenant $currentTenant = null;

    public function setTenant(Tenant $tenant): void
    {
        $this->currentTenant = $tenant;
    }

    public function getTenant(): ?Tenant
    {
        if (!$this->currentTenant && Auth::check()) {
            $this->currentTenant = Auth::user()->tenant;
        }

        return $this->currentTenant;
    }

    public function getTenantId(): ?int
    {
        return $this->getTenant()?->id;
    }
}
