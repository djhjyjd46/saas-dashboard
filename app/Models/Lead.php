<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = ['tenant_id', 'external_id', 'status', 'created_at_source', 'meta_data'];

    protected $casts = [
        'created_at_source' => 'datetime',
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
}
