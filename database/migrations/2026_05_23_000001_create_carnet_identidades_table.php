<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carnet_identidades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->index();
            $table->enum('tipo', ['estudiante', 'docente', 'empleado'])->default('estudiante');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('matricula_id')->nullable()->constrained('matriculas')->nullOnDelete();
            $table->string('numero_carnet', 20)->unique();        // ZR-2026-00001
            $table->string('qr_token', 80)->unique();             // token firmado
            $table->enum('estado', ['activo', 'suspendido', 'vencido'])->default('activo');
            $table->date('vigencia_hasta')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'tipo']);
            $table->index(['tenant_id', 'estado']);
            $table->index(['tenant_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carnet_identidades');
    }
};
