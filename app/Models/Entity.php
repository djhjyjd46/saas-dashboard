<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Entity extends Model
{
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    protected $fillable = ['tenant_id', 'name', 'keyword', 'color', 'is_active', 'parent_id', 'type'];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Entity::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Entity::class, 'parent_id');
    }

    public function campaigns()
    {
        return $this->hasMany(AdCampaign::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }
}
