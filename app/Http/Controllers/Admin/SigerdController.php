<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\SchoolYear;
use App\Models\SigerdConfig;
use App\Models\SigerdExportLog;
use App\Services\SigerdExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SigerdController extends Controller
{
    public function __construct(private SigerdExportService $service) {}

    public function index()
    {
        $schoolYear = SchoolYear::actual();
        $tid        = tenant_id() ?? 0;
        $syId       = $schoolYear?->id ?? 0;

        $config = Cache::remember("t{$tid}_sigerd_config", 300, fn() => SigerdConfig::first());

        $grupos = Cache::remember("t{$tid}_sigerd_grupos_{$syId}", 300,
            fn() => Grupo::with(['grado', 'seccion'])->where('school_year_id', $syId)->get()
        );
        $periodos = Cache::remember("t{$tid}_periodos_{$syId}", 600,
            fn() => Periodo::where('school_year_id', $syId)->orderBy('numero')->get()
        );
        $ultimosLogs = SigerdExportLog::with(['user', 'grupo', 'schoolYear'])->latest('created_at')->take(8)->get();
        $statsValidacion = null;
        if (request('grupo_id') && $schoolYear) {
            $statsValidacion = $this->service->validarNomina($schoolYear, request('grupo_id'));
        }
        return view('admin.sigerd.index', compact('schoolYear', 'config', 'grupos', 'periodos', 'ultimosLogs', 'statsValidacion'));
    }

    public function configuracion()
    {
        $tid    = tenant_id() ?? 0;
        $config = Cache::remember("t{$tid}_sigerd_config", 300, fn() => SigerdConfig::first());
        return view('admin.sigerd.configuracion', compact('config'));
    }

    public function guardarConfiguracion(Request $request)
    {
        $validated = $request->validate([
            'codigo_centro' => 'required|string|max:50',
            'nombre_centro' => 'nullable|string',
            'distrito'      => 'nullable|string',
            'regional'      => 'nullable|string',
            'modalidad'     => 'nullable|string',
            'sector'        => 'nullable|string',
            'anio_sigerd'   => 'nullable|string',
        ]);
        $tenantId = auth()->user()->tenant_id;
        SigerdConfig::updateOrCreate(
            ['tenant_id' => $tenantId],
            array_merge($validated, ['tenant_id' => $tenantId])
        );
        Cache::forget("t{$tenantId}_sigerd_config");
        return redirect()->route('admin.sigerd.configuracion')->with('success', 'Configuracion SIGERD guardada correctamente.');
    }

    public function exportar(Request $request)
    {
        $request->validate([
            'tipo'    => 'required|in:nomina_matricula,calificaciones,docentes,asistencia',
            'formato' => 'required|in:excel,csv,pdf',
        ]);
        try {
            $sy        = SchoolYear::actual() ?? abort(404, 'No hay año escolar activo.');
            $grupoId   = $request->grupo_id   ? (int) $request->grupo_id   : null;
            $periodoId = $request->periodo_id  ? (int) $request->periodo_id : null;
            $desde     = $request->desde ?? now()->startOfYear()->toDateString();
            $hasta     = $request->hasta ?? now()->toDateString();
            $formato   = $request->formato;
            $tipo      = $request->tipo;

            // Contar registros antes de exportar para el log
            $totalRegistros = $this->contarRegistros($tipo, $sy, $grupoId);

            $response = match ($tipo) {
                'nomina_matricula' => $this->service->exportarNomina($sy, $grupoId, $formato),
                'calificaciones'   => $this->service->exportarCalificaciones($sy, (int) $grupoId, $periodoId, $formato),
                'docentes'         => $this->service->exportarDocentes($sy, $formato),
                'asistencia'       => $this->service->exportarAsistencia($sy, $grupoId, $desde, $hasta, $formato),
                default            => throw new \InvalidArgumentException('Tipo no válido'),
            };

            SigerdExportLog::create([
                'tenant_id'       => auth()->user()->tenant_id,
                'user_id'         => auth()->id(),
                'tipo'            => $tipo,
                'grupo_id'        => $grupoId,
                'periodo_id'      => $periodoId,
                'school_year_id'  => $sy->id,
                'formato'         => $formato,
                'total_registros' => $totalRegistros,
                'created_at'      => now(),
            ]);

            // Notificar al usuario por WhatsApp (async)
            $this->notificarExportacion($tipo, $totalRegistros, $formato);

            return $response;
        } catch (\Exception $e) {
            return back()->with('error', 'Error al exportar: ' . $e->getMessage());
        }
    }

    private function contarRegistros(string $tipo, SchoolYear $sy, ?int $grupoId): int
    {
        return match ($tipo) {
            'nomina_matricula', 'asistencia' => \App\Models\Matricula::where('school_year_id', $sy->id)
                ->where('estado', 'activa')
                ->when($grupoId, fn($q) => $q->where('grupo_id', $grupoId))
                ->count(),
            'calificaciones' => \App\Models\Matricula::where('school_year_id', $sy->id)
                ->where('estado', 'activa')
                ->when($grupoId, fn($q) => $q->where('grupo_id', $grupoId))
                ->count(),
            'docentes' => \App\Models\Asignacion::where('school_year_id', $sy->id)
                ->distinct('docente_id')->count('docente_id'),
            default => 0,
        };
    }

    private function notificarExportacion(string $tipo, int $total, string $formato): void
    {
        try {
            $user = auth()->user();
            if (empty($user->telefono)) return;

            $labels = [
                'nomina_matricula' => 'Nómina de Matrícula',
                'calificaciones'   => 'Libro de Calificaciones',
                'docentes'         => 'Nómina de Docentes',
                'asistencia'       => 'Registro de Asistencia',
            ];

            $school = \App\Helpers\Setting::get('system_name', 'El centro educativo');
            $label  = $labels[$tipo] ?? $tipo;

            \App\Services\WhatsAppService::send(
                $user->telefono,
                "✅ *{$school}* — SIGERD\n\n*{$label}* exportado correctamente.\n📊 {$total} registros en formato " . strtoupper($formato) . ".\n\nEl archivo está listo para cargar en el portal SIGERD/MINERD."
            );
        } catch (\Throwable) {}
    }

    public function validar(Request $request)
    {
        $sy = SchoolYear::actual();
        $grupoId = request('grupo_id') ? (int)request('grupo_id') : null;
        if (!$sy) return response()->json(['ok' => false, 'errores' => ['No hay ano escolar activo'], 'total' => 0]);
        if (request('tipo') === 'calificaciones' && $grupoId) {
            return response()->json($this->service->validarCalificaciones($sy, $grupoId, request('periodo_id')));
        }
        return response()->json($this->service->validarNomina($sy, $grupoId));
    }

    public function historial()
    {
        $logs = SigerdExportLog::with(['user', 'grupo', 'schoolYear', 'periodo'])
            ->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.sigerd.historial', compact('logs'));
    }
}
