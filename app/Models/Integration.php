<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }
    protected $fillable = ['tenant_id', 'type', 'credentials', 'is_active'];

    protected $casts = [
        'credentials' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
