<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plantillas de Comunicación (WhatsApp/Email editables por tenant).
 *
 * A diferencia de pagina_secciones (Portal Público Fase 2), esta tabla NO
 * reemplaza ningún sistema existente -- es una capa opcional encima del
 * hardcode actual de WhatsAppService/Mail. Un tenant sin fila para un
 * evento+canal sigue recibiendo exactamente el mismo texto que hoy. Por
 * eso up() NO ejecuta ningún backfill: crear filas idénticas al hardcode
 * para cada tenant existente sería puro ruido (infla la tabla y rompe la
 * señal "personalizado vs. predeterminado" que la UI necesita mostrar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantillas_comunicacion', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('evento', 60);
            $table->string('canal', 16); // whatsapp | email
            $table->string('asunto', 255)->nullable(); // solo aplica a email
            $table->text('cuerpo')->nullable(); // null/'' = usar el hardcode actual
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'evento', 'canal'], 'plantillas_com_tenant_evento_canal_unq');
            $table->index(['tenant_id', 'canal', 'activa'], 'plantillas_com_tenant_canal_activa_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_comunicacion');
    }
};
