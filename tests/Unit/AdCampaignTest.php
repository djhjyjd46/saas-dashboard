<?php

namespace Tests\Unit;

use App\Models\AdCampaign;
use Tests\TestCase;

class AdCampaignTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function it_normalizes_yandex_statuses_correctly()
    {
        $campaign = new AdCampaign();

        // Тестируем маппинг специфичных для Яндекса статусов
        $campaign->status = 'ACCEPTED';
        $this->assertEquals('active', $campaign->normalized_status);

        $campaign->status = 'SUSPENDED';
        $this->assertEquals('suspended', $campaign->normalized_status);

        $campaign->status = 'ENDED';
        $this->assertEquals('ended', $campaign->normalized_status);

        $campaign->status = 'ARCHIVED_YES';
        $this->assertEquals('archived', $campaign->normalized_status);

        $campaign->status = 'DRAFT_NEW';
        $this->assertEquals('draft', $campaign->normalized_status);

        $campaign->status = 'SERVING';
        $this->assertEquals('active', $campaign->normalized_status);

        $campaign->status = 'MODERATION';
        $this->assertEquals('moderation', $campaign->normalized_status);

        $campaign->status = 'UNKNOWN_STUFF';
        $this->assertEquals('unknown', $campaign->normalized_status);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_keeps_already_mapped_statuses()
    {
        $campaign = new AdCampaign();

        $campaign->status = 'paused';
        $this->assertEquals('paused', $campaign->normalized_status);

        $campaign->status = 'active';
        $this->assertEquals('active', $campaign->normalized_status);
    }
}
