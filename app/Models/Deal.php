<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deal extends Model
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
        'integration_id',
        'lead_id',
        'status',
        'revenue',
        'closed_at'
    ];

    protected $casts = [
        'closed_at' => 'datetime',
        'revenue' => 'float',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
