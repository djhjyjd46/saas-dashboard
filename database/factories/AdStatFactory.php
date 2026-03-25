<?php

namespace Database\Factories;

use App\Models\AdStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdStat>
 */
class AdStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => \App\Models\Tenant::factory(),
            'ad_campaign_id' => \App\Models\AdCampaign::factory(),
            'date' => $this->faker->date(),
            'spend' => $this->faker->randomFloat(2, 0, 1000),
            'clicks' => $this->faker->numberBetween(0, 500),
            'impressions' => $this->faker->numberBetween(500, 10000),
        ];
    }
}
