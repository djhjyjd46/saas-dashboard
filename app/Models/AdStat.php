<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdStat extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = [
        'tenant_id',
        'user_id',
        'ad_campaign_id',
        'date',
        'spend',
        'clicks',
        'impressions',
        'conversions',
        'revenue',
    ];

    public function adCampaign()
    {
        return $this->belongsTo(AdCampaign::class);
    }
}
