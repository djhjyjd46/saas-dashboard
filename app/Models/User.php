<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'theme',
        'tenant_id',
        'settings',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /**
     * Returns effective campaign settings for the user with tenant fallback.
     */
    public function campaignSettings(): array
    {
        $userSettings = is_array($this->settings) ? $this->settings : [];
        $tenantSettings = is_array($this->tenant?->settings) ? $this->tenant->settings : [];

        return [
            'allowed_external_ids' => $userSettings['allowed_external_ids'] ?? ($tenantSettings['allowed_external_ids'] ?? []),
            'category_mapping' => $userSettings['category_mapping'] ?? ($tenantSettings['category_mapping'] ?? []),
            'category_mapping_ui' => $userSettings['category_mapping_ui'] ?? ($tenantSettings['category_mapping_ui'] ?? []),
        ];
    }
}
