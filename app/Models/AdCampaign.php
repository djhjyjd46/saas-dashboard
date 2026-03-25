<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdCampaign extends Model
{
    use HasFactory;
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = [
        'tenant_id',
        'user_id',
        'external_id',
        'name',
        'source',
        'status',
        'last_synced_at',
        'utm_campaign',
        'tracking_params',
    ];
    protected $casts = [
        'last_synced_at' => 'datetime',
    ];

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
        if (str_starts_with($upper, 'ON') || str_contains($upper, 'ACCEPTED') || str_contains($upper, 'SERVING')) return 'active';
        if (str_contains($upper, 'MODERATION'))  return 'moderation';

        return 'unknown';
    }
}
