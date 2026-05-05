<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('feature')->comment('Nombre del módulo/característica');
            $table->boolean('activo')->default(true);
            $table->json('config')->nullable()->comment('Configuración específica del módulo');
            $table->timestamps();
            $table->unique(['tenant_id', 'feature']);
            $table->index('tenant_id');
        });

        // Features por defecto para el tenant 1
        $features = [
            'pagos', 'classroom', 'whatsapp', 'admisiones',
            'area_tecnica', 'competencias', 'horarios', 'gamificacion',
            'portal_padre', 'portal_estudiante', 'portal_docente',
            'boletines', 'asistencia', 'calificaciones', 'reportes',
            'comunicados', 'calendario', 'nomina', 'cafeteria',
            'biblioteca', 'inventario', 'transporte', 'salud',
            'disciplina', 'tutorias', 'seguimiento_social', 'reuniones',
            'evaluaciones_docentes', 'proyectos', 'reconocimientos',
            'modo_publico',
        ];

        foreach ($features as $feature) {
            DB::table('tenant_features')->insert([
                'tenant_id'  => 1,
                'feature'    => $feature,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_features');
    }
};
