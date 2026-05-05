<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80);
            $table->string('clave', 40)->unique()->comment('Identificador usado en código: classroom, pagos, etc.');
            $table->text('descripcion')->nullable();
            $table->string('icono', 40)->nullable()->comment('Nombre de icono Bootstrap Icons');
            $table->enum('categoria', ['academico', 'comunicacion', 'financiero', 'administracion', 'lms', 'bienestar'])
                  ->default('academico');
            $table->enum('plan_minimo', ['free', 'pro', 'premium'])->default('free')
                  ->comment('Plan mínimo para acceder a este módulo');
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // ── Módulos del sistema ──────────────────────────────────────────
        $modules = [
            // Académicos - Free
            ['nombre'=>'Asistencia',            'clave'=>'asistencia',           'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'calendar-check',     'orden'=>1],
            ['nombre'=>'Calificaciones',         'clave'=>'calificaciones',        'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'journal-text',       'orden'=>2],
            ['nombre'=>'Boletines',              'clave'=>'boletines',             'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'file-earmark-text',  'orden'=>3],
            ['nombre'=>'Reportes',               'clave'=>'reportes',              'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'bar-chart-line',     'orden'=>4],
            ['nombre'=>'Calendario Académico',   'clave'=>'calendario',            'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'calendar3',          'orden'=>5],
            // Portales - Free
            ['nombre'=>'Portal Docente',         'clave'=>'portal_docente',        'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'person-badge',       'orden'=>6],
            ['nombre'=>'Portal Estudiante',      'clave'=>'portal_estudiante',     'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'mortarboard',        'orden'=>7],
            ['nombre'=>'Portal Padre',           'clave'=>'portal_padre',          'categoria'=>'academico',       'plan_minimo'=>'free',    'icono'=>'people',             'orden'=>8],
            // Comunicación - Free
            ['nombre'=>'Comunicados',            'clave'=>'comunicados',           'categoria'=>'comunicacion',    'plan_minimo'=>'free',    'icono'=>'megaphone',          'orden'=>9],
            // Académicos - Pro
            ['nombre'=>'Horarios',               'clave'=>'horarios',              'categoria'=>'academico',       'plan_minimo'=>'pro',     'icono'=>'clock',              'orden'=>10],
            ['nombre'=>'Competencias y RA',      'clave'=>'competencias',          'categoria'=>'academico',       'plan_minimo'=>'pro',     'icono'=>'trophy',             'orden'=>11],
            ['nombre'=>'Tutorias',               'clave'=>'tutorias',              'categoria'=>'bienestar',       'plan_minimo'=>'pro',     'icono'=>'chat-heart',         'orden'=>12],
            ['nombre'=>'Disciplina',             'clave'=>'disciplina',            'categoria'=>'bienestar',       'plan_minimo'=>'pro',     'icono'=>'exclamation-circle', 'orden'=>13],
            ['nombre'=>'Seguimiento Social',     'clave'=>'seguimiento_social',    'categoria'=>'bienestar',       'plan_minimo'=>'pro',     'icono'=>'heart-pulse',        'orden'=>14],
            ['nombre'=>'Gamificación',           'clave'=>'gamificacion',          'categoria'=>'academico',       'plan_minimo'=>'pro',     'icono'=>'stars',              'orden'=>15],
            // LMS - Pro
            ['nombre'=>'ZuraClass (LMS)',        'clave'=>'classroom',             'categoria'=>'lms',             'plan_minimo'=>'pro',     'icono'=>'display',            'orden'=>16],
            // Premium
            ['nombre'=>'Pagos y Cobros',         'clave'=>'pagos',                 'categoria'=>'financiero',      'plan_minimo'=>'premium', 'icono'=>'credit-card',        'orden'=>17],
            ['nombre'=>'WhatsApp Business',      'clave'=>'whatsapp',              'categoria'=>'comunicacion',    'plan_minimo'=>'premium', 'icono'=>'whatsapp',           'orden'=>18],
            ['nombre'=>'Admisiones Digitales',   'clave'=>'admisiones',            'categoria'=>'administracion',  'plan_minimo'=>'premium', 'icono'=>'clipboard-plus',     'orden'=>19],
            ['nombre'=>'Nómina Docentes',        'clave'=>'nomina',                'categoria'=>'financiero',      'plan_minimo'=>'premium', 'icono'=>'cash-coin',          'orden'=>20],
            ['nombre'=>'Biblioteca',             'clave'=>'biblioteca',            'categoria'=>'administracion',  'plan_minimo'=>'premium', 'icono'=>'book',               'orden'=>21],
            ['nombre'=>'Inventario',             'clave'=>'inventario',            'categoria'=>'administracion',  'plan_minimo'=>'premium', 'icono'=>'boxes',              'orden'=>22],
            ['nombre'=>'Proyectos Escolares',    'clave'=>'proyectos',             'categoria'=>'academico',       'plan_minimo'=>'premium', 'icono'=>'kanban',             'orden'=>23],
            ['nombre'=>'Reconocimientos',        'clave'=>'reconocimientos',       'categoria'=>'academico',       'plan_minimo'=>'premium', 'icono'=>'award',              'orden'=>24],
            ['nombre'=>'Evaluaciones Docentes',  'clave'=>'evaluaciones_docentes', 'categoria'=>'administracion',  'plan_minimo'=>'premium', 'icono'=>'person-check',       'orden'=>25],
        ];

        foreach ($modules as &$m) {
            $m['activo']     = true;
            $m['created_at'] = now();
            $m['updated_at'] = now();
        }

        DB::table('modules')->insert($modules);
    }

    public function down(): void
    {
        Schema::dropIfExists('modules');
    }
};
