<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boletin_observaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matricula_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('periodo_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('tipo', ['academica', 'conducta', 'sugerencia', 'general'])
                  ->default('general');
            $table->text('contenido');
            $table->foreignId('docente_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['matricula_id', 'school_year_id', 'periodo_id'], 'obs_boletin');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boletin_observaciones');
    }
};
