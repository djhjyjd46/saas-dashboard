<?php
$tenant = \App\Models\Tenant::first();
\App\Models\User::firstOrCreate(
    ['email' => 'vuz@stashevski.by'],
    [
        'name' => 'ВУЗ',
        'password' => bcrypt('password'),
        'role' => 'client',
        'theme' => 'gold',
        'tenant_id' => $tenant ? $tenant->id : 1
    ]
);
echo "User restored.\n";
