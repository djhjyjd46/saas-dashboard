<?php

namespace App\Services\Integrations\Contracts;

use App\Models\Integration;

interface CrmProviderInterface
{
    public function setIntegration(Integration $integration): self;
    public function syncLeads(int $days = 7): void;
    public function syncDeals(int $days = 7): void;
}
