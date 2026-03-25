<?php

namespace Database\Factories;

use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
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
            'external_id' => (string) $this->faker->unique()->numberBetween(100000, 999999),
            'status' => 'new',
            'created_at_source' => now(),
            'meta_data' => [
                'utm_source' => 'yandex',
                'utm_medium' => 'cpc',
                'utm_campaign' => 'test_campaign',
            ],
        ];
    }
}
