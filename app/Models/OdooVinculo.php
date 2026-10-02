<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/** Correspondencia entre un registro del sistema y su registro en Odoo, con la huella de lo último enviado. */
class OdooVinculo extends Model
{
    use BelongsToTenant;

    protected $table = 'odoo_vinculos';

    protected $fillable = ['tenant_id', 'entidad_tipo', 'entidad_id', 'odoo_modelo', 'odoo_id', 'huella', 'sincronizado_at', 'ultimo_error'];

    protected $casts = ['sincronizado_at' => 'datetime'];
}
