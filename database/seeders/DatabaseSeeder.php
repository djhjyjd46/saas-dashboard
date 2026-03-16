<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $tenant = \App\Models\Tenant::create([
            'name' => 'gold',
            'domain' => 'analytics.stashevski.by',
            'active_theme' => 'gold',
            'settings' => [
                'category_mapping' => [
                    'Поиск' => ['12345'],
                    'РСЯ' => ['67890'],
                ]
            ],
        ]);

        \App\Models\User::create([
            'name' => 'Admin User',
            'email' => 'admin@stashevski.by',
            'password' => bcrypt('root'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'theme' => 'default',
        ]);

        // No sample data — real data comes from syncing integrations
    }
}
