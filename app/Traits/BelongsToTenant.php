<?php

namespace App\Traits;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        // Scope automático en todas las queries
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenant = app()->bound('tenant') ? app('tenant') : null;

            if ($tenant instanceof Tenant) {
                $table = (new static)->getTable();
                $builder->where("{$table}.tenant_id", $tenant->id);
            }
        });

        // Auto-asignar tenant_id al crear
        static::creating(function ($model) {
            if (empty($model->tenant_id)) {
                $tenant = app()->bound('tenant') ? app('tenant') : null;
                if ($tenant instanceof Tenant) {
                    $model->tenant_id = $tenant->id;
                }
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Ignorar el scope de tenant (para super admins o migraciones) */
    public static function withoutTenant(): Builder
    {
        return static::withoutGlobalScope('tenant');
    }

    /** Buscar en un tenant específico */
    public static function forTenant(int|Tenant $tenant): Builder
    {
        $tenantId = $tenant instanceof Tenant ? $tenant->id : $tenant;
        return static::withoutGlobalScope('tenant')->where(
            (new static)->getTable() . '.tenant_id', $tenantId
        );
    }
}
