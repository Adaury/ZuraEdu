<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Cafetería: recargar y vender (`operar-cafeteria`) y corregir saldos (`ajustar-saldo-cafeteria`) pasan a ser permisos propios.
 * Antes bastaba «ver-servicios» (Biblioteca y Recepción podían mover dinero).
 *
 * Esta migración los crea y se los da a los roles que YA recargaban, para que al actualizar no desaparezca el botón de recarga
 * (el seeder solo corre al sembrar) y sin tocar ningún otro permiso de ningún rol (un seeder re-sincronizaría todos).
 * Biblioteca conserva «ver-servicios» (puede ver) pero ya no mueve dinero.
 */
return new class extends Migration
{
    private const ASIGNACION = [
        'operar-cafeteria'        => ['Administrador', 'Director', 'Recepción'],
        'ajustar-saldo-cafeteria' => ['Administrador', 'Director'],
    ];

    public function up(): void
    {
        foreach (self::ASIGNACION as $permiso => $roles) {
            $p = Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
            foreach ($roles as $rol) {
                $r = Role::where('name', $rol)->where('guard_name', 'web')->first();
                if ($r && ! $r->hasPermissionTo($p)) {
                    $r->givePermissionTo($p);
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (array_keys(self::ASIGNACION) as $permiso) {
            Permission::where('name', $permiso)->where('guard_name', 'web')->first()?->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
