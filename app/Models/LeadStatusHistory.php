<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeadStatusHistory extends Model
{
    use HasFactory;

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
        static::addGlobalScope(new UserScope);
    }

    protected $fillable = [
        'tenant_id',
        'user_id',
        'lead_id',
        'status_id',
        'pipeline_id',
        'changed_at',
        'meta_data'
    ];

    protected $casts = [
        'changed_at' => 'datetime',
        'meta_data' => 'array',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function crmStatus()
    {
        return $this->belongsTo(CrmStatus::class, 'status_id', 'external_id');
    }
}
