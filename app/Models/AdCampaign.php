<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = ['tenant_id', 'external_id', 'name', 'source', 'status', 'last_synced_at'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function adStats()
    {
        return $this->hasMany(AdStat::class);
    }

    /**
     * Нормализует статус кампании в единый mapped формат.
     * Поддерживает как уже mapped-значения, так и старый формат Яндекс API.
     */
    public function getNormalizedStatusAttribute(): string
    {
        $st = $this->status ?? '';

        $mapped = ['serving', 'active', 'paused', 'suspended', 'archived', 'ended', 'stopped', 'moderation', 'rejected', 'draft', 'unknown'];
        if (in_array($st, $mapped)) {
            return $st;
        }

        $upper = strtoupper($st);
        if (str_starts_with($upper, 'ARCHIVED')) return 'archived';
        if (str_starts_with($upper, 'ENDED'))    return 'ended';
        if (str_starts_with($upper, 'SUSPENDED')) return 'suspended';
        if (str_contains($upper, 'DRAFT'))       return 'draft';
        if (str_starts_with($upper, 'OFF'))      return 'paused';
        if (str_starts_with($upper, 'ON'))       return 'active';

        return 'unknown';
    }
}
