<?php

namespace Database\Factories;

use App\Models\Deal;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
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
            'lead_id' => \App\Models\Lead::factory(),
            'status' => 'won',
            'revenue' => $this->faker->randomFloat(2, 1000, 100000),
            'closed_at' => now(),
        ];
    }
}
