<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CrmStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'name',
        'color',
        'pipeline_id',
        'tenant_id'
    ];
}
