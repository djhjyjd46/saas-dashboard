<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdStat extends Model
{
    protected $fillable = ['tenant_id', 'ad_campaign_id', 'date', 'spend', 'clicks', 'impressions'];

    public function adCampaign()
    {
        return $this->belongsTo(AdCampaign::class);
    }
}
