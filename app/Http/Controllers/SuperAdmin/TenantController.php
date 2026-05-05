<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TenantController extends Controller
{
    private const ALL_FEATURES = [
        'pagos'              => 'Pagos y Financiero',
        'classroom'          => 'Classroom Virtual',
        'whatsapp'           => 'Notificaciones WhatsApp',
        'admisiones'         => 'Portal Admisiones',
        'area_tecnica'       => 'Área Técnica / Vocacional',
        'competencias'       => 'Competencias y RA',
        'horarios'           => 'Módulo de Horarios',
        'gamificacion'       => 'Gamificación',
        'portal_padre'       => 'Portal del Padre',
        'portal_estudiante'  => 'Portal del Estudiante',
        'portal_docente'     => 'Portal del Docente',
        'boletines'          => 'Boletines',
        'asistencia'         => 'Control de Asistencia',
        'calificaciones'     => 'Calificaciones',
        'reportes'           => 'Reportes y PDF',
        'comunicados'        => 'Comunicados',
        'calendario'         => 'Calendario Académico',
        'nomina'             => 'Nómina',
        'cafeteria'          => 'Cafetería',
        'biblioteca'         => 'Biblioteca',
        'inventario'         => 'Inventario',
        'transporte'         => 'Transporte Escolar',
        'salud'              => 'Salud Escolar',
        'disciplina'         => 'Disciplina',
        'tutorias'           => 'Tutorías',
        'seguimiento_social' => 'Seguimiento Social',
        'reuniones'          => 'Actas de Reuniones',
        'evaluaciones_docentes'=> 'Evaluación Docentes',
        'proyectos'          => 'Proyectos Escolares',
        'reconocimientos'    => 'Reconocimientos',
        'modo_publico'       => 'Acceso Público (sin login)',
    ];

    public function index(Request $request)
    {
        $tenants = Tenant::withTrashed()
            ->withCount('features')
            ->when($request->search, fn($q) =>
                $q->where('nombre_institucion', 'LIKE', "%{$request->search}%")
                  ->orWhere('dominio', 'LIKE', "%{$request->search}%")
            )
            ->when($request->estado, fn($q) => $q->where('estado', $request->estado))
            ->when($request->plan, fn($q) => $q->where('plan', $request->plan))
            ->latest()
            ->paginate(20);

        $stats = [
            'total'     => Tenant::count(),
            'activos'   => Tenant::where('estado', 'activo')->count(),
            'prueba'    => Tenant::where('estado', 'prueba')->count(),
            'suspendidos'=> Tenant::where('estado', 'suspendido')->count(),
            'premium'   => Tenant::where('plan', 'premium')->count(),
            'pro'       => Tenant::where('plan', 'pro')->count(),
            'free'      => Tenant::where('plan', 'free')->count(),
        ];

        return view('superadmin.tenants.index', compact('tenants', 'stats'));
    }

    public function create()
    {
        $features = self::ALL_FEATURES;
        return view('superadmin.tenants.create', compact('features'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre_institucion'    => 'required|string|max:200',
            'dominio'               => 'required|string|max:100|unique:tenants,dominio|regex:/^[a-z0-9\-]+$/',
            'dominio_personalizado' => 'nullable|string|max:200|unique:tenants,dominio_personalizado',
            'tipo'                  => 'required|in:publico,privado,instituto,tecnico',
            'estado'                => 'required|in:activo,suspendido,prueba,cancelado',
            'plan'                  => 'required|in:free,basico,pro',
            'email_contacto'        => 'nullable|email|max:200',
            'telefono_contacto'     => 'nullable|string|max:30',
            'ciudad'                => 'nullable|string|max:100',
            'color_primario'        => 'nullable|string|max:7',
            'color_secundario'      => 'nullable|string|max:7',
            'fecha_registro'        => 'nullable|date',
            'fecha_vencimiento'     => 'nullable|date|after_or_equal:fecha_registro',
            'max_estudiantes'       => 'required|integer|min:1',
            'max_docentes'          => 'required|integer|min:1',
            'features'              => 'nullable|array',
        ]);

        $tenant = Tenant::create($data);

        // Guardar features seleccionados
        $selectedFeatures = $request->input('features', []);
        foreach (self::ALL_FEATURES as $key => $_) {
            TenantFeature::create([
                'tenant_id' => $tenant->id,
                'feature'   => $key,
                'activo'    => in_array($key, $selectedFeatures),
            ]);
        }

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', "Institución «{$tenant->nombre_institucion}» creada correctamente.");
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['features', 'subscriptions.plan']);
        $features   = self::ALL_FEATURES;
        $featureMap = $tenant->features->keyBy('feature');

        $stats = [
            'estudiantes' => \App\Models\Estudiante::withoutTenant()->where('tenant_id', $tenant->id)->count(),
            'docentes'    => \App\Models\Docente::withoutTenant()->where('tenant_id', $tenant->id)->count(),
            'usuarios'    => \App\Models\User::withoutTenant()->where('tenant_id', $tenant->id)->count(),
        ];

        return view('superadmin.tenants.show', compact('tenant', 'features', 'featureMap', 'stats'));
    }

    public function edit(Tenant $tenant)
    {
        $tenant->load('features');
        $features   = self::ALL_FEATURES;
        $featureMap = $tenant->features->keyBy('feature');
        return view('superadmin.tenants.edit', compact('tenant', 'features', 'featureMap'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'nombre_institucion'    => 'required|string|max:200',
            'dominio'               => "required|string|max:100|unique:tenants,dominio,{$tenant->id}|regex:/^[a-z0-9\-]+$/",
            'dominio_personalizado' => "nullable|string|max:200|unique:tenants,dominio_personalizado,{$tenant->id}",
            'tipo'                  => 'required|in:publico,privado,instituto,tecnico',
            'estado'                => 'required|in:activo,suspendido,prueba,cancelado',
            'plan'                  => 'required|in:free,basico,pro',
            'email_contacto'        => 'nullable|email|max:200',
            'telefono_contacto'     => 'nullable|string|max:30',
            'ciudad'                => 'nullable|string|max:100',
            'color_primario'        => 'nullable|string|max:7',
            'color_secundario'      => 'nullable|string|max:7',
            'fecha_registro'        => 'nullable|date',
            'fecha_vencimiento'     => 'nullable|date',
            'max_estudiantes'       => 'required|integer|min:1',
            'max_docentes'          => 'required|integer|min:1',
            'features'              => 'nullable|array',
        ]);

        $tenant->update($data);

        // Actualizar features
        $selectedFeatures = $request->input('features', []);
        foreach (self::ALL_FEATURES as $key => $_) {
            TenantFeature::updateOrCreate(
                ['tenant_id' => $tenant->id, 'feature' => $key],
                ['activo' => in_array($key, $selectedFeatures)]
            );
        }

        // Limpiar caché de features del tenant
        foreach (array_keys(self::ALL_FEATURES) as $key) {
            Cache::forget("tenant_{$tenant->id}_feature_{$key}");
        }
        Cache::forget("tenant_host_{$tenant->dominio}");

        return redirect()->route('superadmin.tenants.show', $tenant)
            ->with('success', 'Institución actualizada correctamente.');
    }

    public function toggleEstado(Request $request, Tenant $tenant)
    {
        if ($tenant->id === 1) {
            return back()->withErrors(['error' => 'No se puede suspender el tenant principal.']);
        }

        $nuevoEstado = $tenant->estado === 'activo' ? 'suspendido' : 'activo';
        $tenant->update(['estado' => $nuevoEstado]);
        Cache::forget("tenant_host_{$tenant->dominio}");

        $label = $nuevoEstado === 'activo' ? 'activada' : 'suspendida';
        return back()->with('success', "Institución {$label} correctamente.");
    }

    public function destroy(Tenant $tenant)
    {
        if ($tenant->id === 1) {
            return back()->withErrors(['error' => 'No se puede eliminar el tenant principal.']);
        }
        $tenant->delete();
        return redirect()->route('superadmin.tenants.index')
            ->with('success', 'Institución eliminada.');
    }

    /** Entra al panel admin de una institución como SuperAdmin */
    public function enterPanel(Tenant $tenant, \Illuminate\Http\Request $request)
    {
        session(['sa_tenant_id' => $tenant->id, 'sa_tenant_nombre' => $tenant->nombre_institucion]);
        $destino = $request->input('destino', '/admin/dashboard');
        return redirect($destino)
            ->with('info', "Estás administrando «{$tenant->nombre_institucion}» como SuperAdmin.");
    }

    /** Sale del panel de la institución y regresa al panel de la plataforma */
    public function exitPanel()
    {
        $nombre = session('sa_tenant_nombre', 'la institución');
        session()->forget(['sa_tenant_id', 'sa_tenant_nombre']);
        return redirect()->route('superadmin.tenants.index')
            ->with('success', "Saliste del panel de «{$nombre}».");
    }
}
