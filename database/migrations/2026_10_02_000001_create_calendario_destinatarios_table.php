<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personas a las que el administrador avisó de un evento del calendario (y quedan con acceso a verlo/descargarlo
        // aunque su rol no esté en `aplica_a`: p. ej. un representante elegido para un evento de "docentes").
        Schema::create('calendario_destinatarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();
            $table->foreignId('calendario_id')->constrained('calendario_academico')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('notificado_at')->nullable();
            $table->timestamp('correo_enviado_at')->nullable();
            $table->timestamps();
            $table->unique(['calendario_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::table('calendario_academico', function (Blueprint $table) {
            // El .ics usa SEQUENCE: al editar el evento se sube para que Google/Outlook actualicen la copia ya agregada.
            $table->unsignedSmallInteger('ics_sequence')->default(0)->after('activo');
        });
    }

    public function down(): void
    {
        Schema::table('calendario_academico', function (Blueprint $table) {
            $table->dropColumn('ics_sequence');
        });
        Schema::dropIfExists('calendario_destinatarios');
    }
};
