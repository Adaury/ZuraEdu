<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ComunicadoPublicado;
use App\Models\Comunicado;
use App\Models\Grupo;
use App\Models\Matricula;
use App\Models\Notificacion;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ComunicadoController extends Controller
{
    // ── List (admin) ──────────────────────────────────────────────────────
    public function index()
    {
        $comunicados = Comunicado::with(['autor', 'grupo.grado', 'grupo.seccion'])
            ->latest()
            ->paginate(20);
        return view('admin.comunicados.index', compact('comunicados'));
    }

    // ── Create form ───────────────────────────────────────────────────────
    public function create()
    {
        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', SchoolYear::actual()?->id)
            ->activos()->get();
        return view('admin.comunicados.create', compact('grupos'));
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'            => 'required|string|max:255',
            'cuerpo'            => 'required|string',
            'tipo_destinatarios'=> 'required|in:todos,docentes,coordinadores,grupo',
            'grupo_id'          => 'nullable|required_if:tipo_destinatarios,grupo|exists:grupos,id',
            'published_at'      => 'nullable|date',
        ]);

        $data['autor_id']    = auth()->id();
        $data['grupo_id']    = $data['tipo_destinatarios'] === 'grupo' ? ($data['grupo_id'] ?? null) : null;
        $data['published_at']= $data['published_at'] ?? now();

        $comunicado = Comunicado::create($data);

        // Enviar notificación interna a los destinatarios
        $this->notificarDestinatarios($comunicado);

        return redirect()->route('admin.comunicados.index')
                         ->with('success', 'Comunicado publicado y notificaciones enviadas.');
    }

    // ── Edit form ─────────────────────────────────────────────────────────
    public function edit(Comunicado $comunicado)
    {
        $grupos = Grupo::with(['grado', 'seccion'])
            ->where('school_year_id', SchoolYear::actual()?->id)
            ->activos()->get();
        return view('admin.comunicados.edit', compact('comunicado', 'grupos'));
    }

    // ── Update ────────────────────────────────────────────────────────────
    public function update(Request $request, Comunicado $comunicado)
    {
        $data = $request->validate([
            'titulo'            => 'required|string|max:255',
            'cuerpo'            => 'required|string',
            'tipo_destinatarios'=> 'required|in:todos,docentes,coordinadores,grupo',
            'grupo_id'          => 'nullable|required_if:tipo_destinatarios,grupo|exists:grupos,id',
            'published_at'      => 'nullable|date',
            'activo'            => 'boolean',
        ]);

        $eraInactivo = !$comunicado->activo;
        $data['grupo_id'] = $data['tipo_destinatarios'] === 'grupo' ? ($data['grupo_id'] ?? null) : null;
        $data['activo']   = $request->boolean('activo', true);

        $comunicado->update($data);

        // Si acaba de activarse (estaba inactivo y ahora está activo), notificar
        if ($eraInactivo && $data['activo']) {
            $comunicado->refresh();
            $this->notificarDestinatarios($comunicado);
        }

        return redirect()->route('admin.comunicados.index')
                         ->with('success', 'Comunicado actualizado.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Comunicado $comunicado)
    {
        $comunicado->delete();
        return back()->with('success', 'Comunicado eliminado.');
    }

    // ── Notificar destinatarios del comunicado ────────────────────────────
    private function notificarDestinatarios(Comunicado $comunicado): void
    {
        $userIds = collect();
        $tipo    = $comunicado->tipo_destinatarios;
        $sy      = SchoolYear::actual();

        try {
            switch ($tipo) {
                case 'todos':
                    // Todos los usuarios activos excepto el autor
                    $userIds = User::where('activo', true)
                        ->where('id', '!=', $comunicado->autor_id)
                        ->pluck('id');
                    break;

                case 'docentes':
                    $userIds = User::role('Docente')
                        ->where('activo', true)
                        ->pluck('id');
                    break;

                case 'coordinadores':
                    $userIds = User::role([
                        'Coordinador Académico',
                        'Coordinador Primer Ciclo',
                        'Coordinador Segundo Ciclo',
                        'Director',
                    ])
                        ->where('activo', true)
                        ->pluck('id');
                    break;

                case 'grupo':
                    if ($comunicado->grupo_id && $sy) {
                        // Representantes/padres de los estudiantes del grupo
                        $estudianteIds = Matricula::where('grupo_id', $comunicado->grupo_id)
                            ->where('school_year_id', $sy->id)
                            ->where('estado', 'activa')
                            ->pluck('estudiante_id');

                        $userIds = User::whereHas('estudiante', fn($q) => $q->whereIn('id', $estudianteIds))
                            ->orWhereHas('representante', function ($q) use ($estudianteIds) {
                                $q->whereHas('estudiantes', fn($s) => $s->whereIn('estudiante_id', $estudianteIds));
                            })
                            ->where('activo', true)
                            ->pluck('id');
                    }
                    break;
            }

            if ($userIds->isNotEmpty()) {
                $idsUnicos = $userIds->unique()->values()->toArray();

                // Notificación interna en portal
                Notificacion::enviarA(
                    $idsUnicos,
                    'comunicado',
                    $comunicado->titulo,
                    \Illuminate\Support\Str::limit(strip_tags($comunicado->cuerpo), 120),
                    ['comunicado_id' => $comunicado->id]
                );

                // Email a usuarios con correo registrado (solo si está activado)
                if (\App\Helpers\Setting::get('email_notif_comunicados', '1') === '1') {
                    User::whereIn('id', $idsUnicos)
                        ->whereNotNull('email')
                        ->pluck('email')
                        ->chunk(50)
                        ->each(function ($emails) use ($comunicado) {
                            foreach ($emails as $email) {
                                try {
                                    Mail::to($email)->queue(new ComunicadoPublicado($comunicado));
                                } catch (\Throwable $e) {}
                            }
                        });
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Error al notificar comunicado: ' . $e->getMessage());
        }
    }

    // ── PDF de un comunicado ─────────────────────────────────────────────
    public function pdf(Comunicado $comunicado)
    {
        $comunicado->load(['autor', 'grupo.grado', 'grupo.seccion']);
        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $dir    = \App\Models\ConfigInstitucional::get('nombre_director', '');
        $sy     = SchoolYear::actual();
        $config = $sy ? \App\Models\BoletinConfig::getOrCreate($sy->id) : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.comunicados.comunicado_pdf',
            compact('comunicado', 'inst', 'dir', 'config')
        )->setPaper('letter', 'portrait');

        $slug = \Illuminate\Support\Str::slug($comunicado->titulo ?? 'comunicado');
        return $pdf->download("comunicado_{$slug}.pdf");
    }

    // ── Lista general PDF ────────────────────────────────────────────────
    public function listaPdf()
    {
        $comunicados = \App\Models\Comunicado::with('autor')
            ->orderByDesc('published_at')
            ->get();

        $inst   = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $sy     = SchoolYear::actual();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.comunicados.lista_pdf',
            compact('comunicados', 'inst', 'sy')
        )->setPaper('letter', 'portrait');

        return $pdf->download('comunicados_' . now()->format('Ymd') . '.pdf');
    }

    // ── Lista general Excel ──────────────────────────────────────────────
    public function listaExcel()
    {
        $comunicados = \App\Models\Comunicado::with('autor')
            ->orderByDesc('published_at')
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Comunicados');

        $headers = ['#', 'Título', 'Destinatario', 'Autor', 'Publicado', 'Fecha'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$col}1", $h);
        }
        $ws->getStyle('A1:F1')->getFont()->setBold(true);
        $ws->getStyle('A1:F1')->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setRGB('1e3a6e');
        $ws->getStyle('A1:F1')->getFont()->getColor()->setRGB('ffffff');

        foreach ($comunicados as $i => $com) {
            $row = $i + 2;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $com->titulo);
            $ws->setCellValue("C{$row}", $com->destinatario ?? 'todos');
            $ws->setCellValue("D{$row}", $com->autor?->name ?? '—');
            $ws->setCellValue("E{$row}", $com->publicado ? 'Sí' : 'No');
            $ws->setCellValue("F{$row}", $com->published_at?->format('d/m/Y') ?? '—');

            if ($i % 2 === 1) {
                $ws->getStyle("A{$row}:F{$row}")->getFill()
                   ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                   ->getStartColor()->setRGB('f0f4ff');
            }
        }

        foreach (range(1, 6) as $ci) {
            $ws->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci)
            )->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="comunicados_' . now()->format('Ymd') . '.xlsx"',
        ]);
    }

    // ── Ver mis comunicados (todos los roles) ─────────────────────────────
    public function misComunicados()
    {
        $user = auth()->user();

        $tipos = ['todos'];
        if ($user->hasRole('Docente'))                                      $tipos[] = 'docentes';
        if ($user->hasAnyRole(['Coordinador', 'Director', 'Administrador'])) {
            $tipos[] = 'coordinadores';
            $tipos[] = 'docentes';
        }

        $comunicados = Comunicado::publicados()
            ->whereIn('tipo_destinatarios', $tipos)
            ->orWhere('tipo_destinatarios', 'todos')
            ->with('autor')
            ->latest('published_at')
            ->paginate(15);

        return view('admin.comunicados.mis', compact('comunicados'));
    }
}
