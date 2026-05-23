<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carnet_accesos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->foreignId('carnet_identidad_id')->constrained('carnet_identidades')->cascadeOnDelete();
            $table->enum('tipo_evento', ['entrada', 'salida', 'biblioteca', 'comedor', 'laboratorio', 'evento', 'prestamo'])->default('entrada');
            $table->enum('estado', ['presente', 'tardanza', 'salida_anticipada', 'denegado'])->default('presente');
            $table->foreignId('zona_id')->nullable()->constrained('carnet_zonas')->nullOnDelete();
            $table->string('dispositivo', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'carnet_identidad_id']);
            $table->index(['tenant_id', 'tipo_evento', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carnet_accesos');
    }
};
