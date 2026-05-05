<?php

namespace App\Services;

use App\Models\Asignatura;
use App\Models\Grado;
use App\Models\Plan;
use App\Models\SchoolYear;
use App\Models\Seccion;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantFeature;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TenantProvisioningService
{
    // ─── Features por plan ───────────────────────────────────────────────────

    private const FREE_FEATURES = [
        'asistencia', 'calificaciones', 'boletines',
        'portal_padre', 'portal_estudiante', 'portal_docente',
        'comunicados', 'calendario', 'reportes',
    ];

    private const BASICO_FEATURES = [
        'asistencia', 'calificaciones', 'boletines',
        'portal_padre', 'portal_estudiante', 'portal_docente',
        'comunicados', 'calendario', 'reportes',
        'horarios', 'classroom', 'disciplina', 'tutorias', 'gamificacion',
    ];

    private const PRO_FEATURES = [
        'asistencia', 'calificaciones', 'boletines',
        'portal_padre', 'portal_estudiante', 'portal_docente',
        'comunicados', 'calendario', 'reportes',
        'horarios', 'classroom', 'disciplina', 'tutorias', 'gamificacion',
        'seguimiento_social', 'competencias',
        'pagos', 'whatsapp', 'admisiones', 'nomina',
        'biblioteca', 'inventario', 'proyectos',
        'reconocimientos', 'evaluaciones_docentes',
        'transporte', 'salud', 'reuniones', 'cafeteria',
    ];

    // ─── Datos base para nuevas instituciones ────────────────────────────────

    private const GRADOS_BASE = [
        ['nombre' => '1ro de Básica',  'nivel' => 1, 'ciclo' => 'primer_ciclo',   'orden' => 1],
        ['nombre' => '2do de Básica',  'nivel' => 2, 'ciclo' => 'primer_ciclo',   'orden' => 2],
        ['nombre' => '3ro de Básica',  'nivel' => 3, 'ciclo' => 'primer_ciclo',   'orden' => 3],
        ['nombre' => '4to de Básica',  'nivel' => 4, 'ciclo' => 'primer_ciclo',   'orden' => 4],
        ['nombre' => '5to de Básica',  'nivel' => 5, 'ciclo' => 'segundo_ciclo',  'orden' => 5],
        ['nombre' => '6to de Básica',  'nivel' => 6, 'ciclo' => 'segundo_ciclo',  'orden' => 6],
        ['nombre' => '7mo de Básica',  'nivel' => 7, 'ciclo' => 'segundo_ciclo',  'orden' => 7],
        ['nombre' => '8vo de Básica',  'nivel' => 8, 'ciclo' => 'segundo_ciclo',  'orden' => 8],
    ];

    private const SECCIONES_BASE = [
        ['nombre' => 'A', 'orden' => 1],
        ['nombre' => 'B', 'orden' => 2],
        ['nombre' => 'C', 'orden' => 3],
    ];

    private const ASIGNATURAS_BASE = [
        ['codigo'=>'LEN','nombre'=>'Lengua Española',       'horas_semanales'=>5,'color'=>'#dc3545','es_basica'=>true,'area'=>'academica'],
        ['codigo'=>'MAT','nombre'=>'Matemática',             'horas_semanales'=>5,'color'=>'#0d6efd','es_basica'=>true,'area'=>'academica'],
        ['codigo'=>'CN', 'nombre'=>'Ciencias Naturales',     'horas_semanales'=>4,'color'=>'#198754','es_basica'=>true,'area'=>'academica'],
        ['codigo'=>'CS', 'nombre'=>'Ciencias Sociales',      'horas_semanales'=>4,'color'=>'#fd7e14','es_basica'=>true,'area'=>'academica'],
        ['codigo'=>'ING','nombre'=>'Inglés',                 'horas_semanales'=>3,'color'=>'#6f42c1','es_basica'=>true,'area'=>'academica'],
        ['codigo'=>'EF', 'nombre'=>'Educación Física',       'horas_semanales'=>2,'color'=>'#20c997','es_basica'=>true,'area'=>'complementaria'],
        ['codigo'=>'ART','nombre'=>'Artes',                  'horas_semanales'=>2,'color'=>'#e83e8c','es_basica'=>true,'area'=>'complementaria'],
        ['codigo'=>'FIH','nombre'=>'Form. Integral Humana',  'horas_semanales'=>2,'color'=>'#fd7e14','es_basica'=>true,'area'=>'complementaria'],
    ];

    // ─── Provisioning principal ───────────────────────────────────────────────

    public function provision(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plan = $data['plan'] ?? 'free';

            // 1. Crear tenant
            $tenant = $this->crearTenant($data, $plan);

            // 2. Activar features del plan
            $this->activarFeatures($tenant, $plan);

            // 3. Crear suscripción (tabla subscriptions)
            $this->crearSuscripcion($tenant, $plan);

            // 4. Crear usuario administrador
            $user = $this->crearUsuarioAdmin($tenant, $data);

            // 5. Año escolar inicial
            $schoolYear = $this->crearAnoEscolar($tenant);

            // 6. Datos base: grados, secciones, materias
            $this->seedGrados($tenant);
            $this->seedSecciones($tenant);
            $this->seedAsignaturas($tenant);

            return [
                'tenant'      => $tenant,
                'user'        => $user,
                'school_year' => $schoolYear,
            ];
        });
    }

    // ─── Pasos internos ───────────────────────────────────────────────────────

    private function crearSuscripcion(Tenant $tenant, string $planSlug): void
    {
        $plan = Plan::where('slug', $planSlug)->first();
        if (! $plan) return;

        Subscription::create([
            'tenant_id'   => $tenant->id,
            'plan_id'     => $plan->id,
            'estado'      => 'prueba',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin'   => now()->addDays(30)->toDateString(),
            'monto_pagado'=> 0,
            'ciclo'       => 'mensual',
        ]);

        // Sincronizar plan_id en tenants
        $tenant->plan_id = $plan->id;
        $tenant->save();
    }

    private function crearTenant(array $data, string $plan): Tenant
    {
        return Tenant::create([
            'nombre_institucion' => $data['nombre_institucion'],
            'dominio'            => $this->generateSubdomain($data['nombre_institucion']),
            'tipo'               => $data['tipo'] ?? 'privado',
            'estado'             => 'prueba',
            'plan'               => $plan,
            'email_contacto'     => $data['email'],
            'fecha_registro'     => now(),
            'fecha_vencimiento'  => now()->addDays(30),
            'max_estudiantes'    => $this->maxEstudiantes($plan),
            'max_docentes'       => $this->maxDocentes($plan),
            'max_usuarios'       => $this->maxUsuarios($plan),
            'color_primario'     => '#1d4ed8',
            'color_secundario'   => '#10b981',
        ]);
    }

    private function activarFeatures(Tenant $tenant, string $plan): void
    {
        $features = match ($plan) {
            'pro'    => self::PRO_FEATURES,
            'basico' => self::BASICO_FEATURES,
            default  => self::FREE_FEATURES,
        };

        foreach ($features as $feature) {
            TenantFeature::create([
                'tenant_id' => $tenant->id,
                'feature'   => $feature,
                'activo'    => true,
            ]);
        }
    }

    private function crearUsuarioAdmin(Tenant $tenant, array $data): User
    {
        // Crear usuario (tenant_id fuera de fillable → asignación directa)
        $user = new User([
            'name'                 => $data['nombre_admin'],
            'email'                => $data['email'],
            'password'             => bcrypt($data['password']),
            'activo'               => true,
            'pendiente_aprobacion' => false,
            'must_change_password' => false,
        ]);
        $user->tenant_id = $tenant->id;
        $user->save();

        // Asignar rol 'Administrador' (nombre real en este sistema)
        // Si no existe por alguna razón, lo crea para no romper el flujo
        $role = Role::firstOrCreate(
            ['name' => 'Administrador', 'guard_name' => 'web']
        );
        $user->assignRole($role);

        return $user;
    }

    private function crearAnoEscolar(Tenant $tenant): SchoolYear
    {
        $year      = now()->year;
        $schoolYear = new SchoolYear([
            'nombre'      => $year . '-' . ($year + 1),
            'fecha_inicio' => now()->startOfYear(),
            'fecha_fin'   => now()->endOfYear(),
            'activo'      => true,
        ]);
        $schoolYear->tenant_id = $tenant->id;
        $schoolYear->save();

        return $schoolYear;
    }

    private function seedGrados(Tenant $tenant): void
    {
        foreach (self::GRADOS_BASE as $data) {
            $grado            = new Grado($data);
            $grado->tenant_id = $tenant->id;
            $grado->save();
        }
    }

    private function seedSecciones(Tenant $tenant): void
    {
        foreach (self::SECCIONES_BASE as $data) {
            $seccion            = new Seccion($data);
            $seccion->tenant_id = $tenant->id;
            $seccion->save();
        }
    }

    private function seedAsignaturas(Tenant $tenant): void
    {
        foreach (self::ASIGNATURAS_BASE as $data) {
            $asignatura            = new Asignatura(array_merge($data, ['activo' => true, 'num_ra' => 0]));
            $asignatura->tenant_id = $tenant->id;
            $asignatura->save();
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function generateSubdomain(string $name): string
    {
        $base = substr(Str::slug($name), 0, 30) ?: 'escuela';

        $candidate = $base;
        $i         = 2;
        while (Tenant::withTrashed()->where('dominio', $candidate)->exists()) {
            $candidate = $base . $i;
            $i++;
        }

        return $candidate;
    }

    private function maxEstudiantes(string $plan): int
    {
        return match ($plan) { 'pro' => 9999, 'basico' => 300, default => 100 };
    }

    private function maxDocentes(string $plan): int
    {
        return match ($plan) { 'pro' => 9999, 'basico' => 15, default => 5 };
    }

    private function maxUsuarios(string $plan): int
    {
        return match ($plan) { 'pro' => 9999, 'basico' => 40, default => 15 };
    }
}
