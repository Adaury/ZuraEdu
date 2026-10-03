<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Configuración del respaldo automático de la PLATAFORMA (no de un colegio): una sola fila.
 * El respaldo cubre la base compartida completa, por eso esta tabla no lleva tenant_id (igual que backup_runs) y solo la administra
 * el superadministrador. Hora, destinos (carpeta local de sincronización y Google Drive) y retención se eligen desde la pantalla.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backup_configuracion', function (Blueprint $table) {
            $table->id();
            $table->boolean('activo')->default(true);

            // Programación
            $table->string('frecuencia', 10)->default('diaria');          // diaria | semanal
            $table->unsignedTinyInteger('dia_semana')->default(0);         // 0 = domingo … 6 = sábado (solo semanal)
            $table->string('hora', 5)->default('02:30');                   // H:i en la zona horaria elegida
            $table->string('zona_horaria', 60)->default('America/Santo_Domingo');
            $table->unsignedSmallInteger('retencion_dias')->default(7);
            $table->boolean('incluir_archivos')->default(true);

            // Carpeta local de sincronización (p. ej. una carpeta que Google Drive para escritorio o Dropbox ya sincronizan)
            $table->boolean('carpeta_local_activa')->default(false);
            $table->string('carpeta_local_ruta', 500)->nullable();

            // Google Drive (OAuth 2.0). El secreto y el token de actualización se guardan cifrados.
            $table->boolean('drive_activo')->default(false);
            $table->text('drive_client_id')->nullable();
            $table->text('drive_client_secret')->nullable();
            $table->text('drive_refresh_token')->nullable();
            $table->string('drive_cuenta', 190)->nullable();
            $table->string('drive_carpeta_nombre', 120)->default('ZuraEdu Respaldos');
            $table->string('drive_carpeta_id', 120)->nullable();
            $table->timestamp('drive_conectado_en')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_configuracion');
    }
};
