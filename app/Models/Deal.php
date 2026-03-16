<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = ['tenant_id', 'lead_id', 'status', 'revenue', 'closed_at'];

    protected $casts = [
        'closed_at' => 'datetime',
        'revenue' => 'float',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
