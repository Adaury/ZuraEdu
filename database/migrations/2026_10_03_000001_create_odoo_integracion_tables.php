<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Una conexión a Odoo por centro educativo: el administrador solo pone las credenciales y el sistema vincula los datos.
        Schema::create('odoo_conexiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->unique();
            $table->string('url', 255);                    // https://mi-colegio.odoo.com
            $table->string('base_datos', 100);             // nombre de la base de datos de Odoo
            $table->string('usuario', 150);                // login del usuario de Odoo (normalmente su correo)
            $table->text('api_key');                       // clave de API de Odoo, CIFRADA (cast 'encrypted'); nunca se devuelve al navegador
            $table->boolean('activo')->default(false);
            $table->boolean('sync_contactos')->default(true);    // representantes -> contactos (res.partner)
            $table->boolean('sync_facturas')->default(false);    // cuotas -> facturas de cliente (account.move)
            $table->boolean('publicar_facturas')->default(false); // false = quedan en borrador en Odoo; true = se publican
            $table->unsignedBigInteger('diario_id')->nullable();  // diario de ventas de Odoo (opcional; si no, el que Odoo elija)
            $table->unsignedBigInteger('uid_odoo')->nullable();
            $table->string('version_odoo', 40)->nullable();
            $table->timestamp('ultimo_test_at')->nullable();
            $table->boolean('ultimo_test_ok')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamp('ultima_sync_at')->nullable();
            $table->timestamps();
        });

        // Qué registro del sistema corresponde a qué registro de Odoo (y la "huella" de lo último enviado para no reenviar lo que no cambió).
        Schema::create('odoo_vinculos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('entidad_tipo', 40);            // 'representante' | 'pago'
            $table->unsignedBigInteger('entidad_id');
            $table->string('odoo_modelo', 60);             // 'res.partner' | 'account.move'
            $table->unsignedBigInteger('odoo_id');
            $table->string('huella', 64)->nullable();
            $table->timestamp('sincronizado_at')->nullable();
            $table->text('ultimo_error')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'entidad_tipo', 'entidad_id', 'odoo_modelo'], 'odoo_vinculo_unico');
            $table->index(['tenant_id', 'odoo_modelo', 'odoo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odoo_vinculos');
        Schema::dropIfExists('odoo_conexiones');
    }
};
