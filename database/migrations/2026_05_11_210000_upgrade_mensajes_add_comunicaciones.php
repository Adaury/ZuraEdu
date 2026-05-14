<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Agregar columnas nuevas a mensajes (estructura multi-destinatario)
        Schema::table('mensajes', function (Blueprint $table) {
            if (!Schema::hasColumn('mensajes', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->index()->after('id');
            }
            if (!Schema::hasColumn('mensajes', 'tipo')) {
                $table->enum('tipo', ['individual', 'grupal', 'circular'])->default('individual')->after('cuerpo');
            }
            if (!Schema::hasColumn('mensajes', 'adjunto_path')) {
                $table->string('adjunto_path', 500)->nullable()->after('tipo');
            }
            if (!Schema::hasColumn('mensajes', 'adjunto_nombre')) {
                $table->string('adjunto_nombre', 255)->nullable()->after('adjunto_path');
            }
        });

        // Crear tabla de destinatarios (muchos destinatarios por mensaje)
        if (!Schema::hasTable('mensaje_destinatarios')) {
            Schema::create('mensaje_destinatarios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('mensaje_id');
                $table->unsignedBigInteger('destinatario_id');
                $table->timestamp('leido_at')->nullable();
                $table->boolean('archivado')->default(false);
                $table->boolean('eliminado')->default(false);
                $table->timestamps();

                $table->unique(['mensaje_id', 'destinatario_id']);
                $table->foreign('mensaje_id')->references('id')->on('mensajes')->onDelete('cascade');
                $table->index('destinatario_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mensaje_destinatarios');

        Schema::table('mensajes', function (Blueprint $table) {
            $table->dropColumnIfExists('tipo');
            $table->dropColumnIfExists('adjunto_path');
            $table->dropColumnIfExists('adjunto_nombre');
        });
    }
};
