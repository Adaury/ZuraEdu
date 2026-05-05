<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicados', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 255);
            $table->longText('cuerpo');
            $table->foreignId('autor_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tipo_destinatarios', ['todos', 'docentes', 'coordinadores', 'grupo'])->default('todos');
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tipo_destinatarios', 'published_at']);
            $table->index('autor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicados');
    }
};
