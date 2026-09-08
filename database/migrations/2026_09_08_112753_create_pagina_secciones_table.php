<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

/**
 * Portal Público Fase 2 — constructor visual de bloques. Reemplaza el
 * sistema anterior de 7 secciones fijas (una instancia cada una, controladas
 * por claves hp_* en ConfigInstitucional) por filas: cualquier cantidad de
 * bloques, del mismo tipo o no, reordenables por el propio centro.
 *
 * up() ejecuta el backfill al final para que "php artisan migrate" sea el
 * único paso de deploy — convierte la configuración hp_* existente de cada
 * tenant en filas equivalentes aquí. Las claves hp_* NO se borran (quedan
 * como red de seguridad de rollback), ver app/Console/Commands/
 * MigrarSeccionesSitio.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagina_secciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('tipo', 30)->index();
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->json('contenido')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'activo', 'orden']);
        });

        Artisan::call('sitio:migrar-secciones');
    }

    public function down(): void
    {
        Schema::dropIfExists('pagina_secciones');
    }
};
