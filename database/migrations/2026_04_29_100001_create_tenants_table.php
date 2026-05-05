<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_institucion');
            $table->string('dominio')->unique()->comment('Subdominio: colegio1 → colegio1.zuraedu.com');
            $table->string('dominio_personalizado')->nullable()->unique()->comment('Dominio propio: miescuela.edu.do');
            $table->string('logo')->nullable();
            $table->enum('tipo', ['publico', 'privado', 'instituto', 'tecnico'])->default('privado');
            $table->enum('estado', ['activo', 'suspendido', 'prueba', 'cancelado'])->default('prueba');
            $table->enum('plan', ['free', 'pro', 'premium'])->default('free');
            $table->string('email_contacto')->nullable();
            $table->string('telefono_contacto')->nullable();
            $table->string('pais')->default('DO');
            $table->string('ciudad')->nullable();
            $table->text('direccion')->nullable();
            $table->string('color_primario')->default('#1d4ed8');
            $table->string('color_secundario')->default('#0f172a');
            $table->date('fecha_registro')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->integer('max_estudiantes')->default(500);
            $table->integer('max_docentes')->default(50);
            $table->integer('max_usuarios')->default(100);
            $table->json('metadatos')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Insertar tenant por defecto (el sistema actual)
        DB::table('tenants')->insert([
            'id'                  => 1,
            'nombre_institucion'  => 'ZuraEdu Demo',
            'dominio'             => 'demo',
            'tipo'                => 'privado',
            'estado'              => 'activo',
            'plan'                => 'premium',
            'fecha_registro'      => now()->toDateString(),
            'max_estudiantes'     => 9999,
            'max_docentes'        => 999,
            'max_usuarios'        => 999,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
