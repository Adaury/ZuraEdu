<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 60);
            $table->string('slug', 30)->unique()->comment('free | pro | premium');
            $table->decimal('precio_mensual', 8, 2)->default(0);
            $table->decimal('precio_anual',   8, 2)->default(0)->comment('Precio anual con descuento');
            $table->string('moneda', 3)->default('USD');
            $table->integer('limite_estudiantes')->default(100);
            $table->integer('limite_docentes')->default(5);
            $table->integer('limite_usuarios')->default(15);
            $table->integer('almacenamiento_gb')->default(1);
            $table->text('descripcion')->nullable();
            $table->json('caracteristicas')->nullable()->comment('Array de bullets para la landing');
            $table->boolean('es_popular')->default(false);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // ── Planes iniciales ─────────────────────────────────────────────
        DB::table('plans')->insert([
            [
                'nombre'              => 'Free',
                'slug'                => 'free',
                'precio_mensual'      => 0,
                'precio_anual'        => 0,
                'moneda'              => 'USD',
                'limite_estudiantes'  => 100,
                'limite_docentes'     => 5,
                'limite_usuarios'     => 15,
                'almacenamiento_gb'   => 1,
                'descripcion'         => 'Para centros pequeños que están comenzando',
                'caracteristicas'     => json_encode(['Hasta 100 estudiantes','5 docentes','Asistencia y calificaciones','Portal padres y estudiantes','Soporte por email']),
                'es_popular'          => false,
                'activo'              => true,
                'orden'               => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'nombre'              => 'Pro',
                'slug'                => 'pro',
                'precio_mensual'      => 49,
                'precio_anual'        => 470,
                'moneda'              => 'USD',
                'limite_estudiantes'  => 500,
                'limite_docentes'     => 30,
                'limite_usuarios'     => 60,
                'almacenamiento_gb'   => 10,
                'descripcion'         => 'Para colegios en crecimiento con necesidades avanzadas',
                'caracteristicas'     => json_encode(['Hasta 500 estudiantes','30 docentes','Todo en Free +','Horarios inteligentes','ZuraClass LMS','Competencias y tutorias','Soporte prioritario']),
                'es_popular'          => true,
                'activo'              => true,
                'orden'               => 2,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
            [
                'nombre'              => 'Premium',
                'slug'                => 'premium',
                'precio_mensual'      => 99,
                'precio_anual'        => 950,
                'moneda'              => 'USD',
                'limite_estudiantes'  => 9999,
                'limite_docentes'     => 9999,
                'limite_usuarios'     => 9999,
                'almacenamiento_gb'   => 100,
                'descripcion'         => 'Para grandes instituciones con control total',
                'caracteristicas'     => json_encode(['Estudiantes ilimitados','Docentes ilimitados','Todo en Pro +','Módulo de pagos y nómina','WhatsApp integrado','Admisiones digitales','Soporte 24/7 dedicado']),
                'es_popular'          => false,
                'activo'              => true,
                'orden'               => 3,
                'created_at'          => now(),
                'updated_at'          => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
