<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = [
        'tenant_id',
        'user_id',
        'integration_id',
        'external_id',
        'lead_name',
        'phone',
        'status',
        'created_at_source',
        'qualified_at',
        'meta_data'
    ];

    protected $casts = [
        'created_at_source' => 'datetime',
        'qualified_at' => 'datetime',
        'meta_data' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deal()
    {
        return $this->hasOne(Deal::class);
    }

    public function statusHistories()
    {
        return $this->hasMany(LeadStatusHistory::class);
    }

    public function crmStatus()
    {
        return $this->belongsTo(CrmStatus::class, 'status', 'external_id');
    }
}
