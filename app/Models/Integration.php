<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use App\Models\Scopes\UserScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
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
        'type',
        'credentials',
        'is_active',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
