<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_reconocimiento', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();
            $table->string('icono', 60)->nullable()->comment('Nombre icono HeroIcons / emoji');
            $table->string('color', 30)->nullable()->comment('Clase Tailwind p.ej. bg-yellow-400');
            $table->timestamps();
        });

        // Tipos por defecto
        \DB::table('tipos_reconocimiento')->insert([
            ['nombre' => 'Excelencia Académica',  'descripcion' => 'Reconocimiento al rendimiento académico sobresaliente.', 'icono' => '🏆', 'color' => 'bg-yellow-400', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Mejor Compañero',        'descripcion' => 'Reconocimiento al estudiante más solidario y colaborativo.', 'icono' => '🤝', 'color' => 'bg-blue-400',   'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Liderazgo',              'descripcion' => 'Reconocimiento al estudiante que demuestra cualidades de liderazgo.', 'icono' => '⭐', 'color' => 'bg-purple-400', 'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Asistencia Perfecta',   'descripcion' => 'Reconocimiento al estudiante sin ausencias durante el período.', 'icono' => '📅', 'color' => 'bg-green-400',  'created_at' => now(), 'updated_at' => now()],
            ['nombre' => 'Valores y Conducta',    'descripcion' => 'Reconocimiento al estudiante ejemplar en valores y disciplina.', 'icono' => '🌟', 'color' => 'bg-rose-400',   'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_reconocimiento');
    }
};
