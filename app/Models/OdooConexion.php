<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Credenciales y opciones de la conexión a Odoo de UN centro educativo. La api_key se guarda cifrada y nunca se serializa. */
class OdooConexion extends Model
{
    use BelongsToTenant;

    protected $table = 'odoo_conexiones';

    protected $fillable = [
        'tenant_id', 'url', 'base_datos', 'usuario', 'api_key', 'activo', 'sync_contactos', 'sync_facturas',
        'publicar_facturas', 'diario_id', 'uid_odoo', 'version_odoo', 'ultimo_test_at', 'ultimo_test_ok', 'ultimo_error', 'ultima_sync_at',
    ];

    /** Nunca sale en toArray()/JSON, ni siquiera cifrada. */
    protected $hidden = ['api_key'];

    protected $casts = [
        'api_key'           => 'encrypted',
        'activo'            => 'boolean',
        'sync_contactos'    => 'boolean',
        'sync_facturas'     => 'boolean',
        'publicar_facturas' => 'boolean',
        'ultimo_test_ok'    => 'boolean',
        'ultimo_test_at'    => 'datetime',
        'ultima_sync_at'    => 'datetime',
    ];

    public function vinculos()
    {
        return $this->hasMany(OdooVinculo::class, 'tenant_id', 'tenant_id');
    }

    /** URL base normalizada (sin barra final). */
    public function urlBase(): string
    {
        return rtrim($this->url, '/');
    }
}
