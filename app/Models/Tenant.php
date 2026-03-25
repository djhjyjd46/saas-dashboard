<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'domain', 'active_theme', 'settings'];

    protected $casts = [
        'settings' => 'array',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function integrations()
    {
        return $this->hasMany(Integration::class);
    }

    public function adCampaigns()
    {
        return $this->hasMany(AdCampaign::class);
    }
}
