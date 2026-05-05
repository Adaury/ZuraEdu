<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantFeature extends Model
{
    protected $table = 'tenant_features';

    protected $fillable = ['tenant_id', 'feature', 'activo', 'config'];

    protected $casts = [
        'activo' => 'boolean',
        'config' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
