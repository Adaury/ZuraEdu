<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SistemaController extends Controller
{
    private function getSetting(string $key, $default = null)
    {
        $row = DB::table('system_settings')->where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    private function setSetting(string $key, $value): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => $value, 'updated_at' => now()]
        );
    }

    public function index()
    {
        $settings = DB::table('system_settings')->pluck('value', 'key');
        return view('admin.sistema.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'system_name'        => 'nullable|string|max:200',
            'system_abbr'        => 'nullable|string|max:10',
            'system_sub'         => 'nullable|string|max:80',
            'session_timeout'    => 'nullable|integer|min:15|max:480',
            'max_login_attempts' => 'nullable|integer|min:3|max:20',
            'codigo_registro'    => 'nullable|string|max:50',
        ]);

        foreach ($data as $key => $value) {
            if ($value !== null) {
                $this->setSetting($key, $value);
            }
        }

        Cache::forget('system_settings_branding');

        return back()->with('success', 'Configuración del sistema actualizada.');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:512',
        ]);

        $path = $request->file('logo')->storeAs('sistema', 'logo.' . $request->file('logo')->extension(), 'public');
        $this->setSetting('system_logo', $path);

        return back()->with('success', 'Logotipo actualizado correctamente.');
    }

    public function deleteLogo()
    {
        $logo = $this->getSetting('system_logo');
        if ($logo && \Illuminate\Support\Facades\Storage::disk('public')->exists($logo)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($logo);
        }
        $this->setSetting('system_logo', null);
        return back()->with('success', 'Logotipo eliminado.');
    }

    public function uploadFavicon(Request $request)
    {
        $request->validate([
            'favicon' => 'required|file|mimes:png,jpg,jpeg,ico,svg|max:256',
        ]);

        // Delete previous favicon if exists
        $old = $this->getSetting('system_favicon');
        if ($old && \Illuminate\Support\Facades\Storage::disk('public')->exists($old)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($old);
        }

        $ext  = $request->file('favicon')->getClientOriginalExtension();
        $path = $request->file('favicon')->storeAs('sistema', 'favicon.' . $ext, 'public');
        $this->setSetting('system_favicon', $path);
        Cache::forget('system_favicon');

        return back()->with('success', 'Favicon actualizado correctamente.');
    }

    // ── Limpiar Datos ───────────────────────────────────────────────────────

    public function limpiarDatos(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'confirmacion' => 'required|in:CONFIRMAR',
            'scope'        => 'required|in:estudiantes,todo',
        ]);

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        if ($request->scope === 'todo') {
            // Limpiar absolutamente todo (datos académicos + estudiantes)
            $tablas = [
                'evaluaciones_indicadores',
                'calificaciones_academicas',
                'calificaciones',
                'asistencias',
                'observaciones',
                'indicadores_aprendizaje',
                'resultados_aprendizaje',
                'horario_detalles',
                'horarios',
                'sch_horario_detalles',
                'sch_horarios',
                'sch_asignaciones',
                'asignaciones',
                'matriculas',
                'estudiante_representante',
                'grupos',
                'secciones',
            ];
            foreach ($tablas as $tabla) {
                if (\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                    DB::table($tabla)->truncate();
                }
            }
            // Eliminar users de estudiantes/representantes
            $estudianteUserIds = DB::table('estudiantes')->whereNotNull('user_id')->pluck('user_id');
            if ($estudianteUserIds->isNotEmpty()) {
                \App\Models\User::whereIn('id', $estudianteUserIds->toArray())
                    ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Administrador','Director','Coordinador','Docente']))
                    ->delete();
            }
            DB::table('estudiantes')->truncate();
            $mensaje = 'Todos los datos académicos y estudiantes han sido eliminados.';

        } else {
            // Solo estudiantes + sus datos
            $tablas = [
                'evaluaciones_indicadores',
                'calificaciones_academicas',
                'calificaciones',
                'asistencias',
                'observaciones',
                'matriculas',
                'estudiante_representante',
            ];
            foreach ($tablas as $tabla) {
                if (\Illuminate\Support\Facades\Schema::hasTable($tabla)) {
                    DB::table($tabla)->truncate();
                }
            }
            $estudianteUserIds = DB::table('estudiantes')->whereNotNull('user_id')->pluck('user_id');
            if ($estudianteUserIds->isNotEmpty()) {
                \App\Models\User::whereIn('id', $estudianteUserIds->toArray())
                    ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['Administrador','Director','Coordinador','Docente']))
                    ->delete();
            }
            DB::table('estudiantes')->truncate();
            $mensaje = 'Todos los estudiantes y sus datos han sido eliminados.';
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // Log actividad
        try {
            \App\Models\ActivityLog::create([
                'user_id'     => auth()->id(),
                'accion'      => 'LIMPIAR_DATOS',
                'descripcion' => "Scope: {$request->scope} — {$mensaje}",
                'ip'          => $request->ip(),
            ]);
        } catch (\Exception $e) {}

        return back()->with('success_danger', $mensaje);
    }

    // ── Landing Editor ──────────────────────────────────────────────────────

    public function landingIndex()
    {
        $settings = DB::table('system_settings')->pluck('value', 'key');
        return view('admin.sistema.landing', compact('settings'));
    }

    public function landingUpdate(Request $request)
    {
        $data = $request->validate([
            'landing_hero_badge'    => 'nullable|string|max:120',
            'landing_hero_title'    => 'nullable|string|max:120',
            'landing_hero_title_em' => 'nullable|string|max:80',
            'landing_hero_sub'      => 'nullable|string|max:300',
            'landing_stat1_n'       => 'nullable|string|max:20',
            'landing_stat1_s'       => 'nullable|string|max:5',
            'landing_stat1_d'       => 'nullable|string|max:60',
            'landing_stat2_n'       => 'nullable|string|max:20',
            'landing_stat2_s'       => 'nullable|string|max:5',
            'landing_stat2_d'       => 'nullable|string|max:60',
            'landing_stat3_n'       => 'nullable|string|max:20',
            'landing_stat3_s'       => 'nullable|string|max:5',
            'landing_stat3_d'       => 'nullable|string|max:60',
            'landing_stat4_n'       => 'nullable|string|max:20',
            'landing_stat4_s'       => 'nullable|string|max:5',
            'landing_stat4_d'       => 'nullable|string|max:60',
            'landing_cta_primary'        => 'nullable|string|max:60',
            'landing_cta_secondary'      => 'nullable|string|max:60',
            'landing_testimonio_cita'    => 'nullable|string|max:500',
            'landing_testimonio_nombre'  => 'nullable|string|max:80',
            'landing_testimonio_cargo'   => 'nullable|string|max:100',
        ]);

        foreach ($data as $key => $value) {
            $this->setSetting($key, $value ?? '');
        }

        Cache::forget('system_settings_all');

        return back()->with('success', 'Página de inicio actualizada correctamente.');
    }

    // ── Login Config ────────────────────────────────────────────────────────

    public function loginIndex()
    {
        $settings = DB::table('system_settings')->pluck('value', 'key');
        return view('admin.sistema.login-config', compact('settings'));
    }

    public function loginUpdate(Request $request)
    {
        $data = $request->validate([
            'login_titulo'      => 'nullable|string|max:120',
            'login_subtitulo'   => 'nullable|string|max:300',
            'login_allow_reg'   => 'nullable|in:0,1',
            'login_color_bg1'   => 'nullable|string|max:7',
            'login_color_bg2'   => 'nullable|string|max:7',
            'login_color_bg3'   => 'nullable|string|max:7',
            'login_color_acc'   => 'nullable|string|max:7',
        ]);

        $this->setSetting('login_titulo',    $data['login_titulo']    ?? '');
        $this->setSetting('login_subtitulo', $data['login_subtitulo'] ?? '');
        $this->setSetting('login_allow_reg', $request->has('login_allow_reg') ? '1' : '0');
        $this->setSetting('login_color_bg1', $data['login_color_bg1'] ?? '#0a0f2e');
        $this->setSetting('login_color_bg2', $data['login_color_bg2'] ?? '#1e3a8a');
        $this->setSetting('login_color_bg3', $data['login_color_bg3'] ?? '#1d4ed8');
        $this->setSetting('login_color_acc', $data['login_color_acc'] ?? '#10b981');

        Cache::forget('system_settings_all');

        return back()->with('success', 'Configuración del login actualizada.');
    }

    public function activityLog(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ActivityLog::with('user')->latest();

        if ($request->filled('accion')) {
            $query->where('accion', 'like', '%' . $request->accion . '%');
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }

        $logs    = $query->paginate(50)->withQueryString();
        $users   = \App\Models\User::orderBy('name')->get(['id', 'name']);
        $acciones = \App\Models\ActivityLog::select('accion')->distinct()->pluck('accion');

        return view('admin.sistema.activity_log', compact('logs', 'users', 'acciones'));
    }

    // ── Exportar log de actividad Excel ──────────────────────────────────
    public function activityLogExcel(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ActivityLog::with('user')->latest();

        if ($request->filled('accion'))  $query->where('accion', 'like', '%' . $request->accion . '%');
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('desde'))   $query->whereDate('created_at', '>=', $request->desde);
        if ($request->filled('hasta'))   $query->whereDate('created_at', '<=', $request->hasta);

        $logs = $query->limit(5000)->get();

        $ss    = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $ss->getActiveSheet();
        $sheet->setTitle('Log Actividad');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        ];

        $headers = ['#', 'Fecha/Hora', 'Usuario', 'Rol', 'Acción', 'Descripción', 'IP', 'Módulo'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(chr(65 + $i) . '1', $h);
        }
        $sheet->getStyle('A1:H1')->applyFromArray($hdrStyle);

        foreach ($logs as $i => $log) {
            $row = $i + 2;
            $sheet->setCellValue("A{$row}", $i + 1);
            $sheet->setCellValue("B{$row}", $log->created_at?->format('d/m/Y H:i:s') ?? '');
            $sheet->setCellValue("C{$row}", $log->user?->name ?? '—');
            $sheet->setCellValue("D{$row}", $log->user?->getRoleNames()->first() ?? '—');
            $sheet->setCellValue("E{$row}", $log->accion ?? '');
            $sheet->setCellValue("F{$row}", $log->descripcion ?? '');
            $sheet->setCellValue("G{$row}", $log->ip ?? '');
            $sheet->setCellValue("H{$row}", $log->modulo ?? '');

            if ($i % 2 === 1) {
                $sheet->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range('A', 'H') as $col) $sheet->getColumnDimension($col)->setAutoSize(true);
        $sheet->freezePane('A2');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'log_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'log_actividad_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Exportar log de actividad PDF ─────────────────────────────────────
    public function activityLogPdf(\Illuminate\Http\Request $request)
    {
        $query = \App\Models\ActivityLog::with('user')->latest();

        if ($request->filled('accion'))  $query->where('accion', 'like', '%' . $request->accion . '%');
        if ($request->filled('user_id')) $query->where('user_id', $request->user_id);
        if ($request->filled('desde'))   $query->whereDate('created_at', '>=', $request->desde);
        if ($request->filled('hasta'))   $query->whereDate('created_at', '<=', $request->hasta);

        $logs = $query->limit(500)->get();
        $inst = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.sistema.activity_log_pdf',
            compact('logs', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('log_actividad_' . now()->format('Ymd') . '.pdf');
    }

    public function estadisticas()
    {
        $sy = \App\Models\SchoolYear::actual();

        $stats = [
            'usuarios_activos'   => \App\Models\User::where('activo', true)->count(),
            'usuarios_roles'     => \App\Models\User::select('id')
                ->with('roles')->get()
                ->groupBy(fn($u) => $u->getRoleNames()->first() ?? 'Sin rol')
                ->map(fn($g) => $g->count()),
            'logins_hoy'         => \App\Models\ActivityLog::where('accion', 'login')
                ->whereDate('created_at', today())->count(),
            'logins_semana'      => \App\Models\ActivityLog::where('accion', 'login')
                ->where('created_at', '>=', now()->subDays(7))->count(),
            'estudiantes'        => \App\Models\Estudiante::activos()->count(),
            'docentes'           => \App\Models\Docente::activos()->count(),
            'grupos'             => $sy ? \App\Models\Grupo::where('school_year_id', $sy->id)->count() : 0,
            'calificaciones'     => $sy ? \App\Models\CalificacionAcademica::where('school_year_id', $sy->id)->count() : 0,
            'asistencias'        => \App\Models\Asistencia::whereDate('created_at', today())->count(),
            'comunicados'        => \App\Models\Comunicado::whereDate('published_at', today())->count(),
            'notificaciones_hoy' => \App\Models\Notificacion::whereDate('created_at', today())->count(),
            'alertas_activas'    => \App\Models\AlertaSistema::where('leida', false)->count(),
            'pagos_pendientes'   => \App\Models\ConfigInstitucional::moduloActivo('pagos')
                ? \App\Models\Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $sy?->id))
                    ->whereIn('estado', ['pendiente', 'vencido'])->count()
                : null,
        ];

        // Actividad por día de la semana (últimos 7 días)
        $actividadPorDia = \App\Models\ActivityLog::where('created_at', '>=', now()->subDays(6))
            ->selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia');

        return view('admin.sistema.estadisticas', compact('stats', 'actividadPorDia', 'sy'));
    }

    // ── Reporte Anual Global PDF ──────────────────────────────────────────
    public function reporteAnualPdf()
    {
        $sy = \App\Models\SchoolYear::actual();
        if (! $sy) abort(404, 'Sin año escolar activo.');

        $totalMat    = \App\Models\Matricula::where('school_year_id', $sy->id)->where('estado', 'activa')->count();
        $totalDoc    = \App\Models\Docente::activos()->count();
        $totalGrupos = \App\Models\Grupo::where('school_year_id', $sy->id)->activos()->count();

        $porGrado = \App\Models\Matricula::join('grupos','matriculas.grupo_id','=','grupos.id')
            ->join('grados','grupos.grado_id','=','grados.id')
            ->where('matriculas.school_year_id', $sy->id)->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre','grados.id')->orderBy('grados.id')
            ->pluck('total','grado');

        $califs     = \App\Models\CalificacionAcademica::where('school_year_id', $sy->id)->whereNotNull('nota_final')->get();
        $promGlobal = $califs->avg('nota_final') ? round($califs->avg('nota_final'), 1) : null;
        $aprobados  = $califs->where('situacion','A')->count();
        $reprobados = $califs->where('situacion','R')->count();
        $tasaApro   = ($aprobados + $reprobados) > 0 ? round($aprobados / ($aprobados + $reprobados) * 100, 1) : null;
        $asistGlobal = \App\Models\CalificacionAcademica::where('school_year_id', $sy->id)->whereNotNull('pct_asistencia')->avg('pct_asistencia');

        $periodos = \App\Models\Periodo::where('school_year_id', $sy->id)->orderBy('numero')->get();

        $topGrupos    = \App\Models\RendimientoCache::where('school_year_id', $sy->id)->whereNull('periodo_id')->with(['grupo.grado','grupo.seccion'])->orderByDesc('promedio_grupo')->limit(5)->get();
        $bottomGrupos = \App\Models\RendimientoCache::where('school_year_id', $sy->id)->whereNull('periodo_id')->with(['grupo.grado','grupo.seccion'])->orderBy('promedio_grupo')->limit(5)->get();

        $planificaciones = \App\Models\Planificacion::where('school_year_id', $sy->id)->count();
        $planesClase     = \App\Models\PlanClase::where('school_year_id', $sy->id)->count();
        $observaciones   = \App\Models\Observacion::whereHas('asignacion', fn($q) => $q->where('school_year_id', $sy->id))->count();
        $comunicados     = \App\Models\Comunicado::where('activo', true)->count();

        $moduloPagos = \App\Models\ConfigInstitucional::moduloActivo('pagos');
        $pagos = null;
        if ($moduloPagos) {
            $pagosQ = \App\Models\Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $sy->id))->get();
            $pagos  = ['cobrado' => $pagosQ->where('estado','pagado')->sum('monto'), 'pendiente' => $pagosQ->whereIn('estado',['pendiente','vencido'])->sum('monto'), 'deudores' => $pagosQ->where('estado','vencido')->unique('matricula_id')->count()];
        }

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $config = \App\Models\BoletinConfig::getOrCreate($sy->id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sistema.reporte_anual_pdf', compact(
            'sy','inst','dir','config','totalMat','totalDoc','totalGrupos','porGrado',
            'promGlobal','aprobados','reprobados','tasaApro','asistGlobal',
            'periodos','topGrupos','bottomGrupos','planificaciones','planesClase',
            'observaciones','comunicados','moduloPagos','pagos'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('reporte_anual_' . now()->format('Ymd') . '.pdf');
    }

    // ── Reporte ejecutivo del Director PDF ───────────────────────────────
    public function reporteEjecutivoPdf()
    {
        $sy = \App\Models\SchoolYear::actual();

        $totalEstudiantes = \App\Models\Matricula::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->where('estado', 'activa')->count();
        $totalDocentes    = \App\Models\Docente::activos()->count();
        $totalGrupos      = $sy ? \App\Models\Grupo::where('school_year_id', $sy->id)->activos()->count() : 0;

        // Matrícula por grado
        $porGrado = \App\Models\Matricula::join('grupos', 'matriculas.grupo_id', '=', 'grupos.id')
            ->join('grados', 'grupos.grado_id', '=', 'grados.id')
            ->when($sy, fn($q) => $q->where('matriculas.school_year_id', $sy->id))
            ->where('matriculas.estado', 'activa')
            ->selectRaw('grados.nombre as grado, COUNT(*) as total')
            ->groupBy('grados.nombre', 'grados.id')
            ->orderBy('grados.id')
            ->pluck('total', 'grado');

        // Rendimiento global
        $califs = \App\Models\CalificacionAcademica::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->whereNotNull('nota_final')->get();
        $promedioGlobal = $califs->avg('nota_final') ? round($califs->avg('nota_final'), 1) : null;
        $aprobados      = $califs->where('situacion', 'A')->count();
        $reprobados     = $califs->where('situacion', 'R')->count();

        // Asistencia global (promedio)
        $asistGlobal = \App\Models\CalificacionAcademica::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->whereNotNull('pct_asistencia')->avg('pct_asistencia');

        // Pagos (si módulo activo)
        $moduloPagos    = \App\Models\ConfigInstitucional::moduloActivo('pagos');
        $totalCobrado   = null;
        $totalPendiente = null;
        $totalDeudores  = null;
        if ($moduloPagos && $sy) {
            $pagos = \App\Models\Pago::whereHas('matricula', fn($m) => $m->where('school_year_id', $sy->id))->get();
            $totalCobrado   = $pagos->where('estado', 'pagado')->sum('monto');
            $totalPendiente = $pagos->whereIn('estado', ['pendiente', 'vencido'])->sum('monto');
            $totalDeudores  = $pagos->where('estado', 'vencido')->unique('matricula_id')->count();
        }

        // Alertas sin resolver
        $alertas = \App\Models\AlertaSistema::where('leida', false)->count();

        // Top grupos por rendimiento
        $topGrupos = \App\Models\RendimientoCache::when($sy, fn($q) => $q->where('school_year_id', $sy->id))
            ->whereNull('periodo_id')->with(['grupo.grado', 'grupo.seccion'])
            ->orderByDesc('promedio_grupo')->limit(10)->get();

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.sistema.reporte_ejecutivo_pdf', compact(
            'sy', 'inst', 'dir', 'config',
            'totalEstudiantes', 'totalDocentes', 'totalGrupos',
            'porGrado', 'promedioGlobal', 'aprobados', 'reprobados', 'asistGlobal',
            'moduloPagos', 'totalCobrado', 'totalPendiente', 'totalDeudores',
            'alertas', 'topGrupos'
        ))->setPaper('letter', 'portrait');

        return $pdf->download('reporte_ejecutivo_' . now()->format('Ymd') . '.pdf');
    }

    // ── Ficha Institucional PDF ───────────────────────────────────────────
    public function fichaInstitucionalPdf()
    {
        $sy = \App\Models\SchoolYear::actual();

        $cfg = [
            'nombre'     => \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name')),
            'codigo'     => \App\Models\ConfigInstitucional::get('codigo_centro', ''),
            'director'   => \App\Models\ConfigInstitucional::get('nombre_director', ''),
            'telefono'   => \App\Models\ConfigInstitucional::get('telefono', ''),
            'email'      => \App\Models\ConfigInstitucional::get('email_institucional', ''),
            'direccion'  => \App\Models\ConfigInstitucional::get('direccion', ''),
            'municipio'  => \App\Models\ConfigInstitucional::get('municipio', ''),
            'provincia'  => \App\Models\ConfigInstitucional::get('provincia', ''),
            'modalidad'  => \App\Models\ConfigInstitucional::get('modalidad', ''),
            'sector'     => \App\Models\ConfigInstitucional::get('sector', 'Público'),
        ];

        $stats = [
            'estudiantes'  => \App\Models\Estudiante::activos()->count(),
            'docentes'     => \App\Models\Docente::activos()->count(),
            'grupos'       => $sy ? \App\Models\Grupo::where('school_year_id', $sy->id)->count() : 0,
            'asignaciones' => $sy ? \App\Models\Asignacion::where('school_year_id', $sy->id)->where('activo', true)->count() : 0,
            'matriculas'   => $sy ? \App\Models\Matricula::where('school_year_id', $sy->id)->where('estado', 'activa')->count() : 0,
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.sistema.ficha_institucional_pdf',
            compact('cfg', 'stats', 'sy')
        )->setPaper('letter', 'portrait');

        return $pdf->download('ficha_institucional_' . now()->format('Ymd') . '.pdf');
    }

    public function deleteFavicon()
    {
        $favicon = $this->getSetting('system_favicon');
        if ($favicon && \Illuminate\Support\Facades\Storage::disk('public')->exists($favicon)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($favicon);
        }
        $this->setSetting('system_favicon', null);
        Cache::forget('system_favicon');
        return back()->with('success', 'Favicon eliminado.');
    }

    // ── WhatsApp / Notificaciones ──────────────────────────────────────────
    public function whatsappIndex()
    {
        $settings = \App\Helpers\Setting::all();
        return view('admin.sistema.whatsapp', compact('settings'));
    }

    public function whatsappUpdate(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'whatsapp_provider'      => 'required|in:twilio,meta',
            'whatsapp_account_sid'   => 'nullable|string|max:100',
            'whatsapp_auth_token'    => 'nullable|string|max:200',
            'whatsapp_from_number'   => 'nullable|string|max:30',
            'whatsapp_notify_grades' => 'nullable|in:1',
            'whatsapp_notify_absence'=> 'nullable|in:1',
            'whatsapp_notify_alerts' => 'nullable|in:1',
        ]);

        \App\Helpers\Setting::setMany([
            'module_whatsapp'         => $request->has('module_whatsapp') ? '1' : '0',
            'whatsapp_provider'       => $request->whatsapp_provider,
            'whatsapp_account_sid'    => $request->whatsapp_account_sid ?? '',
            'whatsapp_auth_token'     => $request->whatsapp_auth_token ?? '',
            'whatsapp_from_number'    => $request->whatsapp_from_number ?? '',
            'whatsapp_notify_grades'  => $request->has('whatsapp_notify_grades')  ? '1' : '0',
            'whatsapp_notify_absence' => $request->has('whatsapp_notify_absence') ? '1' : '0',
            'whatsapp_notify_alerts'  => $request->has('whatsapp_notify_alerts')  ? '1' : '0',
        ]);

        return back()->with('success', 'Configuración de WhatsApp guardada correctamente.');
    }

    // ── Configuración de notificaciones por email ─────────────────────────
    public function emailNotifIndex()
    {
        $settings = \App\Helpers\Setting::all();
        return view('admin.sistema.email_notif', compact('settings'));
    }

    public function emailNotifUpdate(\Illuminate\Http\Request $request)
    {
        \App\Helpers\Setting::setMany([
            'email_notif_calificaciones' => $request->boolean('email_notif_calificaciones') ? '1' : '0',
            'email_notif_comunicados'    => $request->boolean('email_notif_comunicados')    ? '1' : '0',
            'email_notif_pagos'          => $request->boolean('email_notif_pagos')          ? '1' : '0',
            'email_notif_aprobacion'     => $request->boolean('email_notif_aprobacion')     ? '1' : '0',
        ]);
        return back()->with('success', 'Configuración de notificaciones guardada.');
    }
}
