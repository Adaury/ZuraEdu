<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sigerd_config', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->string('codigo_centro');
            $table->string('nombre_centro')->nullable();
            $table->string('distrito')->nullable();
            $table->string('regional')->nullable();
            $table->string('modalidad')->default('Regular');
            $table->string('sector')->default('Privado');
            $table->string('anio_sigerd')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('sigerd_export_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained('periodos')->nullOnDelete();
            $table->string('formato');
            $table->integer('total_registros')->default(0);
            $table->json('errores_validacion')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->text('notas')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sigerd_export_logs');
        Schema::dropIfExists('sigerd_config');
    }
};
