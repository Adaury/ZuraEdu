<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\PreMatriculaResolucion;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\PreMatricula;
use App\Models\Representante;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class PreMatriculaAdminController extends Controller
{
    /**
     * Listado de todas las solicitudes con filtros.
     */
    public function index(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }

        if ($request->filled('grado')) {
            $query->porGrado($request->grado);
        }

        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $solicitudes  = $query->paginate(20)->withQueryString();
        $grados       = PreMatricula::gradosDisponibles();
        $totalPendientes = PreMatricula::pendientes()->count();

        // Conteos para tarjetas
        $conteos = [
            'total'     => PreMatricula::count(),
            'pendiente' => PreMatricula::where('estado', 'pendiente')->count(),
            'aprobada'  => PreMatricula::where('estado', 'aprobada')->count(),
            'rechazada' => PreMatricula::where('estado', 'rechazada')->count(),
        ];

        return view('admin.pre_matriculas.index', compact(
            'solicitudes', 'grados', 'conteos', 'totalPendientes'
        ));
    }

    /**
     * Detalle de una solicitud.
     */
    public function show(PreMatricula $preMatricula)
    {
        return view('admin.pre_matriculas.show', compact('preMatricula'));
    }

    /**
     * Aprobar solicitud.
     */
    public function aprobar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['nullable', 'string', 'max:1000'],
        ]);

        $preMatricula->update([
            'estado'      => 'aprobada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} aprobada. Se notificó al representante.");
    }

    /**
     * Rechazar solicitud.
     */
    public function rechazar(Request $request, PreMatricula $preMatricula)
    {
        $request->validate([
            'notas_admin' => ['required', 'string', 'max:1000'],
        ], [
            'notas_admin.required' => 'Debe indicar el motivo del rechazo.',
        ]);

        $preMatricula->update([
            'estado'      => 'rechazada',
            'notas_admin' => $request->notas_admin,
        ]);

        try {
            Mail::to($preMatricula->email)->queue(new PreMatriculaResolucion($preMatricula));
        } catch (\Throwable $e) {
            // Email falla en silencio
        }

        return redirect()->route('admin.pre-matriculas.show', $preMatricula)
            ->with('success', "Solicitud de {$preMatricula->nombre_completo} rechazada. Se notificó al representante.");
    }

    /**
     * Formulario para convertir pre-matrícula aprobada en matrícula real.
     */
    public function formConvertir(PreMatricula $preMatricula)
    {
        abort_if($preMatricula->estado !== 'aprobada', 403, 'Solo se pueden convertir solicitudes aprobadas.');

        $schoolYear = SchoolYear::actual();
        $grupos = Grupo::with(['grado', 'seccion'])
            ->when($schoolYear, fn($q) => $q->where('school_year_id', $schoolYear->id))
            ->orderBy('grado_id')->orderBy('seccion_id')
            ->get();

        return view('admin.pre_matriculas.convertir', compact('preMatricula', 'grupos', 'schoolYear'));
    }

    /**
     * Ejecutar conversión: crear estudiante, representante, usuario y matrícula.
     */
    public function convertir(Request $request, PreMatricula $preMatricula)
    {
        abort_if($preMatricula->estado !== 'aprobada', 403);

        $request->validate([
            'grupo_id'        => 'required|exists:grupos,id',
            'crear_usuario'   => 'nullable|boolean',
            'observaciones'   => 'nullable|string|max:500',
        ]);

        $schoolYear = SchoolYear::actual();
        abort_unless($schoolYear, 422, 'No hay un año escolar activo.');

        DB::beginTransaction();
        try {
            // ── 1. Estudiante ─────────────────────────────────────────────
            $nroMat = $this->generarNumeroMatricula($schoolYear->nombre ?? date('Y'));

            $sexo = match($preMatricula->genero ?? '') {
                'Masculino' => 'M',
                'Femenino'  => 'F',
                default     => 'M',
            };

            $estudiante = Estudiante::create([
                'numero_matricula' => $nroMat,
                'cedula'           => $preMatricula->cedula_estudiante,
                'nombres'          => $preMatricula->nombres,
                'apellidos'        => $preMatricula->apellidos,
                'fecha_nacimiento' => $preMatricula->fecha_nacimiento,
                'sexo'             => $sexo,
                'lugar_nacimiento' => $preMatricula->lugar_nacimiento,
                'email'            => $preMatricula->email,
                'direccion'        => $preMatricula->direccion,
                'estado'           => 'activo',
            ]);

            // ── 2. Representante (buscar por cédula o crear) ──────────────
            $rep = Representante::where('cedula', $preMatricula->cedula_representante)->first();

            $repNombres   = '';
            $repApellidos = '';
            $parts = explode(' ', trim($preMatricula->nombre_representante), 2);
            if (count($parts) === 2) {
                $repNombres   = $parts[0];
                $repApellidos = $parts[1];
            } else {
                $repNombres = $preMatricula->nombre_representante;
            }

            if (! $rep) {
                $repUser = null;
                $tempPass = null;

                if ($request->boolean('crear_usuario')) {
                    $tempPass = Str::random(10);
                    $username = $this->generarUsername($preMatricula->email, $preMatricula->cedula_representante);

                    $repUser = User::create([
                        'name'     => $preMatricula->nombre_representante,
                        'email'    => $preMatricula->email,
                        'username' => $username,
                        'password' => Hash::make($tempPass),
                        'activo'   => true,
                    ]);
                    $repUser->assignRole('Representante');
                }

                $rep = Representante::create([
                    'user_id'   => $repUser?->id,
                    'cedula'    => $preMatricula->cedula_representante,
                    'nombres'   => $repNombres,
                    'apellidos' => $repApellidos,
                    'telefono'  => $preMatricula->telefono,
                    'email'     => $preMatricula->email,
                    'direccion' => $preMatricula->direccion,
                ]);
            }

            // Vincular representante con el estudiante
            $parentesco = $preMatricula->relacion_representante ?? 'Tutor/a';
            $rep->estudiantes()->syncWithoutDetaching([
                $estudiante->id => ['parentesco' => $parentesco, 'es_principal' => true],
            ]);

            // ── 3. Matrícula ──────────────────────────────────────────────
            $grupo = Grupo::findOrFail($request->grupo_id);
            $nroOrden = Matricula::where('grupo_id', $grupo->id)->count() + 1;

            $matricula = Matricula::create([
                'school_year_id'  => $schoolYear->id,
                'estudiante_id'   => $estudiante->id,
                'grupo_id'        => $grupo->id,
                'fecha_matricula' => now(),
                'numero_orden'    => $nroOrden,
                'estado'          => 'activa',
                'observaciones'   => $request->observaciones,
            ]);

            // ── 4. Marcar pre-matrícula como convertida ───────────────────
            $preMatricula->update(['estudiante_id' => $estudiante->id]);

            DB::commit();

            // Email de bienvenida con credenciales (si se creó usuario)
            if ($request->boolean('crear_usuario') && isset($repUser, $tempPass)) {
                try {
                    Mail::to($repUser->email)->queue(
                        new \App\Mail\BienvenidaRepresentante($rep, $repUser, $tempPass, $matricula)
                    );
                } catch (\Throwable $e) {}
            }

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar la matrícula: ' . $e->getMessage());
        }

        return redirect()->route('admin.matriculas.show', $matricula)
            ->with('success', "¡Matrícula creada! {$estudiante->nombre_completo} — {$nroMat}");
    }

    private function generarNumeroMatricula(string $anio): string
    {
        $prefix = 'MAT-' . substr($anio, -2);
        $ultimo = Estudiante::withoutGlobalScopes()
            ->where('numero_matricula', 'like', "{$prefix}%")
            ->max('numero_matricula');

        $siguiente = 1;
        if ($ultimo) {
            $num = (int) substr($ultimo, -4);
            $siguiente = $num + 1;
        }

        return $prefix . '-' . str_pad($siguiente, 4, '0', STR_PAD_LEFT);
    }

    private function generarUsername(string $email, string $cedula): string
    {
        // Preferir parte antes del @ del email
        $base = Str::slug(explode('@', $email)[0]);
        if (! User::where('username', $base)->exists()) {
            return $base;
        }
        // Fallback: últimos 6 dígitos de la cédula
        $cedNum = preg_replace('/\D/', '', $cedula);
        $base2  = 'rep' . substr($cedNum, -6);
        if (! User::where('username', $base2)->exists()) {
            return $base2;
        }
        return $base . '_' . Str::random(4);
    }

    /**
     * Eliminar solicitud.
     */
    public function destroy(PreMatricula $preMatricula)
    {
        $nombre = $preMatricula->nombre_completo;
        $preMatricula->delete();

        return redirect()->route('admin.pre-matriculas.index')
            ->with('success', "Solicitud de {$nombre} eliminada.");
    }

    /**
     * Exportar lista de solicitudes a Excel.
     */
    public function listaExcel(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) {
            $query->porEstado($request->estado);
        }
        if ($request->filled('grado')) {
            $query->porGrado($request->grado);
        }
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(function ($q) use ($buscar) {
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
                  ->orWhere('cedula_representante', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        $solicitudes = $query->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Pre-matrículas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1e3a6e']],
        ];

        $ws->mergeCells('A1:H1');
        $ws->setCellValue('A1', 'Solicitudes de Pre-matrículas — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Estudiante', 'Grado Solicitado', 'Representante', 'Cédula Rep.', 'Teléfono', 'Correo', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:H3')->applyFromArray($hdrStyle);

        foreach ($solicitudes as $i => $s) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $s->nombre_completo);
            $ws->setCellValue("C{$row}", $s->grado_solicitado ?? '—');
            $ws->setCellValue("D{$row}", $s->nombre_representante ?? '—');
            $ws->setCellValue("E{$row}", $s->cedula_representante ?? '—');
            $ws->setCellValue("F{$row}", $s->telefono ?? '—');
            $ws->setCellValue("G{$row}", $s->email ?? '—');
            $ws->setCellValue("H{$row}", ucfirst($s->estado ?? '—'));
            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:H{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('eff6ff');
            }
        }

        foreach (range('A', 'H') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'prm_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'pre_matriculas_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf(Request $request)
    {
        $query = PreMatricula::latest();

        if ($request->filled('estado')) $query->porEstado($request->estado);
        if ($request->filled('grado'))  $query->porGrado($request->grado);
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->where(fn($q) =>
                $q->where('nombres', 'like', "%{$buscar}%")
                  ->orWhere('apellidos', 'like', "%{$buscar}%")
                  ->orWhere('nombre_representante', 'like', "%{$buscar}%")
            );
        }

        $solicitudes = $query->get();
        $inst        = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.pre_matriculas.lista_pdf',
            compact('solicitudes', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('pre_matriculas_' . now()->format('Ymd') . '.pdf');
    }
}
