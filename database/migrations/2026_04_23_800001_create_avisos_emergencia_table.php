<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('avisos_emergencia', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('tipo', ['emergencia', 'suspension', 'actividad', 'informativo'])
                  ->default('informativo');
            $table->foreignId('enviado_por_id')->constrained('users')->cascadeOnDelete();
            $table->enum('destinatarios', ['todos', 'padres', 'docentes', 'grupo'])
                  ->default('todos');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->integer('total_enviados')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos_emergencia');
    }
};
