<?php

namespace Database\Factories;

use App\Models\AdCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdCampaign>
 */
class AdCampaignFactory extends Factory
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
            'external_id' => (string) $this->faker->unique()->numberBetween(10000, 99999),
            'name' => $this->faker->sentence(3),
            'source' => 'yandex',
            'status' => 'active',
            'last_synced_at' => now(),
        ];
    }
}
