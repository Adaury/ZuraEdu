<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;
use App\Models\Calificacion;
use App\Models\Comunicado;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use App\Services\TenantProvisioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DemoAutoController extends Controller
{
    // ── Datos ficticios ───────────────────────────────────────────────────

    private const ESTUDIANTES = [
        ['nombres' => 'María José',    'apellidos' => 'Ramírez Pérez',    'sexo' => 'F'],
        ['nombres' => 'Carlos Andrés', 'apellidos' => 'Méndez Castillo',  'sexo' => 'M'],
        ['nombres' => 'Valentina',     'apellidos' => 'Torres Guzmán',    'sexo' => 'F'],
        ['nombres' => 'Jean Carlos',   'apellidos' => 'Reyes Ureña',      'sexo' => 'M'],
        ['nombres' => 'Paola Sofía',   'apellidos' => 'Jiménez Cruz',     'sexo' => 'F'],
        ['nombres' => 'Eduardo',       'apellidos' => 'Santos Féliz',     'sexo' => 'M'],
        ['nombres' => 'Adriana',       'apellidos' => 'López de la Cruz', 'sexo' => 'F'],
        ['nombres' => 'Francisco',     'apellidos' => 'Hernández Brito',  'sexo' => 'M'],
        ['nombres' => 'Yanira',        'apellidos' => 'Almonte Ogando',   'sexo' => 'F'],
        ['nombres' => 'Isaías',        'apellidos' => 'Núñez Suero',      'sexo' => 'M'],
        ['nombres' => 'Brianna',       'apellidos' => 'Peralta Medina',   'sexo' => 'F'],
        ['nombres' => 'Luis Manuel',   'apellidos' => 'Sánchez Tapia',    'sexo' => 'M'],
        ['nombres' => 'Génesis',       'apellidos' => 'Féliz Familia',    'sexo' => 'F'],
        ['nombres' => 'Jonathan',      'apellidos' => 'Rosario Marte',    'sexo' => 'M'],
        ['nombres' => 'Camila',        'apellidos' => 'Ureña Polanco',    'sexo' => 'F'],
    ];

    private const DOCENTES = [
        ['nombres' => 'Ana Sofía',  'apellidos' => 'Morales Disla',   'especialidad' => 'Lengua Española',   'sexo' => 'F'],
        ['nombres' => 'Pedro José', 'apellidos' => 'Vargas Montero',  'especialidad' => 'Matemática',         'sexo' => 'M'],
        ['nombres' => 'Lucía',      'apellidos' => 'Beltre Figueroa', 'especialidad' => 'Ciencias Naturales', 'sexo' => 'F'],
        ['nombres' => 'Roberto',    'apellidos' => 'Castillo Disla',  'especialidad' => 'Inglés',             'sexo' => 'M'],
    ];

    // ── Punto de entrada ──────────────────────────────────────────────────

    public function enter(Request $request, TenantProvisioningService $provisioning)
    {
        // Verificar que el modo demo está activo
        $demoActivo = \Illuminate\Support\Facades\DB::table('system_settings')
            ->where('key', 'demo_activo')->value('value');
        if ($demoActivo !== '1') {
            return redirect('/')->with('error', '⚠ El modo demo no está disponible en este momento.');
        }

        // Rate limit: máx 3 demos por IP por hora
        $key = 'demo_enter:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            return redirect('/')->with('error', 'Demasiados accesos demo desde tu IP. Intenta de nuevo en unos minutos.');
        }
        RateLimiter::hit($key, 3600);

        $uid = strtolower(Str::random(8));

        // 1. Crear tenant temporal + estructura base (en una sola transacción)
        $result = $provisioning->provision([
            'nombre_institucion' => 'Colegio Demo ZuraEdu',
            'nombre_admin'       => 'Administrador Demo',
            'email'              => "admin-{$uid}@demo.zuraedu.temp",
            'password'           => Str::random(32),
            'tipo'               => 'privado',
            'plan'               => 'pro',
        ]);

        $tenant     = $result['tenant'];
        $adminUser  = $result['user'];
        $schoolYear = $result['school_year'];

        // Marcar como demo temporal para limpieza automática
        $tenant->update([
            'metadatos'         => ['is_demo_temporal' => true, 'demo_uid' => $uid],
            'fecha_vencimiento' => now()->addDays(1)->toDateString(),
            'max_estudiantes'   => 9999,
            'max_docentes'      => 999,
        ]);

        // 2. Actualizar contexto del tenant en el contenedor
        app()->instance('tenant', $tenant);
        app()->instance(Tenant::class, $tenant);

        // 3. Sembrar datos ficticios ricos
        DB::transaction(function () use ($tenant, $schoolYear, $adminUser) {
            $this->seedDemoData($tenant, $schoolYear, $adminUser);
        });

        // 4. Auto-login
        Auth::login($adminUser);
        $request->session()->regenerate();

        session([
            'demo_mode'        => true,
            'demo_admin'       => true,
            'demo_tenant_id'   => $tenant->id,
            'demo_tenant_name' => $tenant->nombre_institucion,
        ]);

        return redirect('/admin/dashboard');
    }

    // ── Seeder de datos ficticios ─────────────────────────────────────────

    private function seedDemoData(Tenant $tenant, SchoolYear $schoolYear, User $adminUser): void
    {
        $tid = $tenant->id;

        // ── Recuperar estructura base ─────────────────────────────────────
        $grados    = Grado::where('tenant_id', $tid)->orderBy('nivel')->get();
        $secciones = Seccion::where('tenant_id', $tid)->get();
        $asignaturas = \App\Models\Asignatura::where('tenant_id', $tid)->get();
        $seccionA  = $secciones->firstWhere('nombre', 'A');
        $seccionB  = $secciones->firstWhere('nombre', 'B');

        if (! $seccionA || $grados->isEmpty()) return;

        // ── Crear Período académico P1 ────────────────────────────────────
        $periodo = new Periodo([
            'school_year_id' => $schoolYear->id,
            'numero'         => 1,
            'nombre'         => 'Primer Período',
            'fecha_inicio'   => $schoolYear->fecha_inicio,
            'fecha_fin'      => $schoolYear->fecha_inicio->addMonths(3),
            'activo'         => true,
            'cerrado'        => false,
        ]);
        $periodo->tenant_id = $tid;
        $periodo->save();

        // ── Docentes (usuarios + perfiles) ───────────────────────────────
        $docentes = [];
        foreach (self::DOCENTES as $i => $data) {
            $email = strtolower(
                mb_substr($data['nombres'], 0, 1) .
                Str::slug($data['apellidos'], '') . "@demo.zuraedu.temp"
            );

            $docenteUser = new User([
                'name'                 => $data['nombres'] . ' ' . $data['apellidos'],
                'email'                => $email . '_' . $tenant->id,
                'password'             => bcrypt('demo123456'),
                'activo'               => true,
                'pendiente_aprobacion' => false,
                'must_change_password' => false,
            ]);
            $docenteUser->tenant_id = $tid;
            $docenteUser->save();

            $roleDoc = Role::firstOrCreate(['name' => 'Docente', 'guard_name' => 'web']);
            $docenteUser->assignRole($roleDoc);

            $docente = new Docente([
                'user_id'      => $docenteUser->id,
                'nombres'      => $data['nombres'],
                'apellidos'    => $data['apellidos'],
                'cedula'       => '402-9900' . str_pad($tid, 2, '0', STR_PAD_LEFT) . $i . '-0',
                'sexo'         => $data['sexo'],
                'especialidad' => $data['especialidad'],
                'email'        => $docenteUser->email,
                'estado'       => 'activo',
            ]);
            $docente->tenant_id = $tid;
            $docente->save();

            $docentes[] = $docente;
        }

        // ── Grupos: 5to A, 6to A, 7mo A, 8vo B ──────────────────────────
        $gradosSel = $grados->whereIn('nivel', [5, 6, 7, 8])->values();
        $grupos = [];

        foreach ($gradosSel as $idx => $grado) {
            $seccion = $idx < 3 ? $seccionA : $seccionB;
            $tutorUser = $docentes[$idx % count($docentes)]->user_id;
            $grupo = new Grupo([
                'school_year_id' => $schoolYear->id,
                'grado_id'       => $grado->id,
                'seccion_id'     => $seccion->id,
                'tutor_id'       => $tutorUser,
                'capacidad'      => 30,
                'activo'         => true,
            ]);
            $grupo->tenant_id = $tid;
            $grupo->save();
            $grupos[] = $grupo;
        }

        if (empty($grupos)) return;

        $grupoMain = $grupos[0]; // 5to A — grupo principal para los estudiantes

        // ── Asignaciones (docente → asignatura → grupo) ───────────────────
        $asignacionesPorGrupo = [];
        foreach ($grupos as $grupo) {
            $asignacionesPorGrupo[$grupo->id] = [];
            foreach ($asignaturas as $idx => $asig) {
                $docente = $docentes[$idx % count($docentes)];
                $asignacion = new Asignacion([
                    'school_year_id' => $schoolYear->id,
                    'grupo_id'       => $grupo->id,
                    'asignatura_id'  => $asig->id,
                    'docente_id'     => $docente->id,
                    'activo'         => true,
                    'horas_semana'   => $asig->horas_semanales ?? 4,
                ]);
                $asignacion->tenant_id = $tid;
                $asignacion->save();
                $asignacionesPorGrupo[$grupo->id][] = $asignacion;
            }
        }

        // ── Estudiantes + Matrículas + Calificaciones ──────────────────────
        $orden = 0;
        foreach (self::ESTUDIANTES as $i => $data) {
            $orden++;
            $estudiante = new Estudiante([
                'numero_matricula' => 'DEMO-' . $tenant->id . '-' . str_pad($i + 1, 3, '0', STR_PAD_LEFT),
                'cedula'           => '402-88' . str_pad($tid, 3, '0', STR_PAD_LEFT) . str_pad($i + 1, 3, '0', STR_PAD_LEFT) . '-0',
                'nombres'          => $data['nombres'],
                'apellidos'        => $data['apellidos'],
                'sexo'             => $data['sexo'],
                'fecha_nacimiento' => now()->subYears(rand(11, 14))->subMonths(rand(0, 11))->format('Y-m-d'),
                'estado'           => 'activo',
                'nacionalidad'     => 'Dominicana',
                'provincia'        => 'Santo Domingo',
                'municipio'        => 'Santo Domingo Este',
                'tutor_nombre'     => 'Representante ' . $data['apellidos'],
                'tutor_parentesco' => $data['sexo'] === 'F' ? 'Madre' : 'Padre',
                'tutor_telefono'   => '809-' . rand(200, 999) . '-' . rand(1000, 9999),
            ]);
            $estudiante->tenant_id = $tid;
            $estudiante->save();

            // Distribuir estudiantes entre grupos (mayoría en el primero)
            $grupoAsig = $i < 10 ? $grupoMain : $grupos[array_key_last($grupos)];

            $matricula = new Matricula([
                'school_year_id'  => $schoolYear->id,
                'estudiante_id'   => $estudiante->id,
                'grupo_id'        => $grupoAsig->id,
                'fecha_matricula' => now()->subDays(rand(5, 30))->format('Y-m-d'),
                'numero_orden'    => $orden,
                'estado'          => 'activa',
            ]);
            $matricula->tenant_id = $tid;
            $matricula->save();

            // Calificaciones P1 para todas las asignaturas
            foreach ($asignacionesPorGrupo[$grupoAsig->id] as $asig) {
                $nota = rand(70, 98);
                $calificacion = new Calificacion([
                    'matricula_id'   => $matricula->id,
                    'asignacion_id'  => $asig->id,
                    'periodo_id'     => $periodo->id,
                    'ra1'            => rand(65, 100) / 10 * 10,
                    'ra2'            => rand(65, 100) / 10 * 10,
                    'ra3'            => rand(65, 100) / 10 * 10,
                    'nota_final'     => $nota,
                    'publicado'      => true,
                ]);
                $calificacion->tenant_id = $tid;
                $calificacion->save();
            }
        }

        // ── Comunicados ───────────────────────────────────────────────────
        $year = now()->year;
        foreach ([
            [
                'titulo'  => "📚 Bienvenidos al Año Escolar {$year}",
                'cuerpo'  => "Estimada comunidad educativa, les damos la más cordial bienvenida al nuevo año escolar {$year}-" . ($year + 1) . ". Estamos comprometidos con la excelencia académica de nuestros estudiantes.",
                'dias'    => 20,
            ],
            [
                'titulo'  => '📋 Entrega de boletines — Primer Período',
                'cuerpo'  => 'Se informa a padres y representantes que la entrega de boletines del Primer Período se realizará el próximo viernes de 8:00 AM a 12:00 PM. Es obligatoria la presencia del representante.',
                'dias'    => 5,
            ],
            [
                'titulo'  => '🏆 Acto de Reconocimiento — Cuadro de Honor',
                'cuerpo'  => 'Nos complace invitar a toda la comunidad educativa al acto de reconocimiento del Cuadro de Honor correspondiente al Primer Período. Los estudiantes homenajeados serán notificados individualmente.',
                'dias'    => 2,
            ],
        ] as $c) {
            $comunicado = new Comunicado([
                'titulo'             => $c['titulo'],
                'cuerpo'             => $c['cuerpo'],
                'autor_id'           => $adminUser->id,
                'tipo_destinatarios' => 'todos',
                'published_at'       => now()->subDays($c['dias']),
                'activo'             => true,
            ]);
            $comunicado->tenant_id = $tid;
            $comunicado->save();
        }
    }
}
