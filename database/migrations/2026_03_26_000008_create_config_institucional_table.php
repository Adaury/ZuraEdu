<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('config_institucional', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();
            $table->text('valor')->nullable();
            $table->string('tipo')->default('string'); // 'string','boolean','integer','json'
            $table->string('grupo')->default('general'); // 'general','pagos','horarios','notificaciones'
            $table->string('descripcion')->nullable();
            $table->timestamps();
        });

        // Seed datos básicos
        DB::table('config_institucional')->insert([
            ['clave' => 'tipo_institucion',       'valor' => 'publico', 'tipo' => 'string',  'grupo' => 'general',   'descripcion' => 'Tipo de institución: publico o privado', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'nombre_institucion',      'valor' => 'PSAC',   'tipo' => 'string',  'grupo' => 'general',   'descripcion' => null,                                     'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'modulo_pagos_activo',     'valor' => '0',      'tipo' => 'boolean', 'grupo' => 'pagos',     'descripcion' => 'Activar módulo de pagos',                'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'modulo_horarios_activo',  'valor' => '1',      'tipo' => 'boolean', 'grupo' => 'horarios',  'descripcion' => 'Activar módulo de horarios',             'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'horario_dias',            'valor' => '["lunes","martes","miercoles","jueves","viernes"]', 'tipo' => 'json', 'grupo' => 'horarios', 'descripcion' => 'Días de clase', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'max_horas_dia_docente',   'valor' => '6',      'tipo' => 'integer', 'grupo' => 'horarios',  'descripcion' => 'Máximo de horas que un docente puede dar en un día', 'created_at' => now(), 'updated_at' => now()],
            ['clave' => 'max_horas_dia_grupo',     'valor' => '8',      'tipo' => 'integer', 'grupo' => 'horarios',  'descripcion' => 'Máximo de horas de clase por día por grupo',         'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('config_institucional');
    }
};
