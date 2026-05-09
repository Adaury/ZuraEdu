<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Encuesta;
use App\Models\PreguntaEncuesta;
use App\Models\OpcionPregunta;
use Illuminate\Http\Request;

class EncuestaController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────────
    public function index()
    {
        $encuestas = Encuesta::withCount(['preguntas', 'respuestas'])
            ->latest()
            ->paginate(20);

        return view('admin.encuestas.index', compact('encuestas'));
    }

    // ── Create ────────────────────────────────────────────────────────────
    public function create()
    {
        return view('admin.encuestas.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'dirigida_a'   => 'required|in:padres,estudiantes,todos',
            'activo'       => 'boolean',
            'fecha_cierre' => 'nullable|date|after_or_equal:today',
            'preguntas'    => 'required|array|min:1',
            'preguntas.*.texto' => 'required|string',
            'preguntas.*.tipo'  => 'required|in:opcion_multiple,texto_libre,escala_1_5',
            'preguntas.*.opciones'   => 'nullable|array',
            'preguntas.*.opciones.*' => 'nullable|string',
        ]);

        $encuesta = Encuesta::create([
            'titulo'       => $data['titulo'],
            'descripcion'  => $data['descripcion'] ?? null,
            'dirigida_a'   => $data['dirigida_a'],
            'activo'       => $request->boolean('activo', true),
            'fecha_cierre' => $data['fecha_cierre'] ?? null,
        ]);

        foreach ($data['preguntas'] as $orden => $preguntaData) {
            $pregunta = PreguntaEncuesta::create([
                'encuesta_id' => $encuesta->id,
                'texto'       => $preguntaData['texto'],
                'tipo'        => $preguntaData['tipo'],
                'orden'       => $orden,
            ]);

            if ($preguntaData['tipo'] === 'opcion_multiple' && ! empty($preguntaData['opciones'])) {
                foreach (array_values(array_filter($preguntaData['opciones'])) as $opOrden => $textoOpcion) {
                    if (trim($textoOpcion) !== '') {
                        OpcionPregunta::create([
                            'pregunta_id' => $pregunta->id,
                            'texto'       => trim($textoOpcion),
                            'orden'       => $opOrden,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('admin.encuestas.index')
                         ->with('success', 'Encuesta creada correctamente.');
    }

    // ── Show (resultados) ─────────────────────────────────────────────────
    public function show(Encuesta $encuesta)
    {
        $encuesta->load(['preguntas.opciones', 'preguntas.respuestas']);

        $estadisticas = $encuesta->preguntas->map(function ($pregunta) {
            return [
                'pregunta'     => $pregunta,
                'estadisticas' => $pregunta->estadisticas(),
            ];
        });

        $totalParticipantes = $encuesta->totalParticipantes();

        return view('admin.encuestas.show', compact('encuesta', 'estadisticas', 'totalParticipantes'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────
    public function edit(Encuesta $encuesta)
    {
        $encuesta->load(['preguntas.opciones']);
        return view('admin.encuestas.edit', compact('encuesta'));
    }

    // ── Update ────────────────────────────────────────────────────────────
    public function update(Request $request, Encuesta $encuesta)
    {
        $data = $request->validate([
            'titulo'       => 'required|string|max:255',
            'descripcion'  => 'nullable|string',
            'dirigida_a'   => 'required|in:padres,estudiantes,todos',
            'activo'       => 'boolean',
            'fecha_cierre' => 'nullable|date',
            'preguntas'    => 'required|array|min:1',
            'preguntas.*.texto'      => 'required|string',
            'preguntas.*.tipo'       => 'required|in:opcion_multiple,texto_libre,escala_1_5',
            'preguntas.*.opciones'   => 'nullable|array',
            'preguntas.*.opciones.*' => 'nullable|string',
        ]);

        $encuesta->update([
            'titulo'       => $data['titulo'],
            'descripcion'  => $data['descripcion'] ?? null,
            'dirigida_a'   => $data['dirigida_a'],
            'activo'       => $request->boolean('activo'),
            'fecha_cierre' => $data['fecha_cierre'] ?? null,
        ]);

        // Regenerar preguntas y opciones
        $encuesta->preguntas()->each(fn($p) => $p->opciones()->delete());
        $encuesta->preguntas()->delete();

        foreach ($data['preguntas'] as $orden => $preguntaData) {
            $pregunta = PreguntaEncuesta::create([
                'encuesta_id' => $encuesta->id,
                'texto'       => $preguntaData['texto'],
                'tipo'        => $preguntaData['tipo'],
                'orden'       => $orden,
            ]);

            if ($preguntaData['tipo'] === 'opcion_multiple' && ! empty($preguntaData['opciones'])) {
                foreach (array_values(array_filter($preguntaData['opciones'])) as $opOrden => $textoOpcion) {
                    if (trim($textoOpcion) !== '') {
                        OpcionPregunta::create([
                            'pregunta_id' => $pregunta->id,
                            'texto'       => trim($textoOpcion),
                            'orden'       => $opOrden,
                        ]);
                    }
                }
            }
        }

        return redirect()->route('admin.encuestas.show', $encuesta)
                         ->with('success', 'Encuesta actualizada correctamente.');
    }

    // ── Destroy ───────────────────────────────────────────────────────────
    public function destroy(Encuesta $encuesta)
    {
        $encuesta->delete();
        return back()->with('success', 'Encuesta eliminada.');
    }

    // ── Toggle activo ─────────────────────────────────────────────────────
    public function toggleActivo(Encuesta $encuesta)
    {
        $encuesta->update(['activo' => ! $encuesta->activo]);

        $estado = $encuesta->activo ? 'activada' : 'desactivada';
        return back()->with('success', "Encuesta {$estado}.");
    }

    // ── Lista Excel ───────────────────────────────────────────────────────
    public function listaExcel()
    {
        $encuestas = Encuesta::withCount(['preguntas', 'respuestas'])
            ->latest()
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Encuestas');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4f46e5']],
        ];

        $ws->mergeCells('A1:G1');
        $ws->setCellValue('A1', 'Encuestas de Satisfacción — ' . now()->format('d/m/Y'));
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        foreach (['#', 'Título', 'Dirigida a', 'Preguntas', 'Participantes', 'Fecha Cierre', 'Estado'] as $i => $h) {
            $ws->setCellValue(chr(65 + $i) . '3', $h);
        }
        $ws->getStyle('A3:G3')->applyFromArray($hdrStyle);

        $dirigidaLabels = ['padres' => 'Padres/Representantes', 'estudiantes' => 'Estudiantes', 'todos' => 'Todos'];

        foreach ($encuestas as $i => $enc) {
            $row = $i + 4;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $enc->titulo);
            $ws->setCellValue("C{$row}", $dirigidaLabels[$enc->dirigida_a] ?? $enc->dirigida_a);
            $ws->setCellValue("D{$row}", $enc->preguntas_count);
            $ws->setCellValue("E{$row}", $enc->totalParticipantes());
            $ws->setCellValue("F{$row}", $enc->fecha_cierre ? $enc->fecha_cierre->format('d/m/Y') : '—');
            $ws->setCellValue("G{$row}", $enc->activo ? 'Activa' : 'Inactiva');
            $bg = $enc->activo ? 'd1fae5' : 'f3f4f6';
            $ws->getStyle("A{$row}:G{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB($bg);
        }

        foreach (range('A', 'G') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'enc_') . '.xlsx';
        $writer->save($tmp);

        return response()->download($tmp, 'encuestas_' . now()->format('Ymd') . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    // ── Lista PDF ─────────────────────────────────────────────────────────
    public function listaPdf()
    {
        $encuestas = Encuesta::withCount(['preguntas', 'respuestas'])->latest()->get();
        $inst      = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView(
            'admin.encuestas.lista_pdf',
            compact('encuestas', 'inst')
        )->setPaper('letter', 'landscape');

        return $pdf->download('encuestas_' . now()->format('Ymd') . '.pdf');
    }

    // ── Resultados Excel por encuesta ─────────────────────────────────────
    public function resultadosExcel(Encuesta $encuesta)
    {
        $encuesta->load(['preguntas.opciones', 'preguntas.respuestas']);

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet()->setTitle('Resultados');

        $hdrStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'ffffff']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4f46e5']],
        ];

        $ws->mergeCells('A1:D1');
        $ws->setCellValue('A1', 'Resultados: ' . $encuesta->titulo);
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(12);
        $ws->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $ws->setCellValue('A2', 'Participantes únicos: ' . $encuesta->totalParticipantes() . '   |   Exportado: ' . now()->format('d/m/Y H:i'));
        $ws->getStyle('A2')->getFont()->setItalic(true)->setSize(9);

        $row = 4;
        foreach ($encuesta->preguntas as $nPregunta => $pregunta) {
            $stats = $pregunta->estadisticas();

            // Cabecera de pregunta
            $ws->mergeCells("A{$row}:D{$row}");
            $ws->setCellValue("A{$row}", 'P' . ($nPregunta + 1) . ': ' . $pregunta->texto . '  [' . $pregunta->tipo_label . ']');
            $ws->getStyle("A{$row}")->getFont()->setBold(true);
            $ws->getStyle("A{$row}:D{$row}")->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('ede9fe');
            $row++;

            if ($stats['tipo'] === 'opcion_multiple') {
                $ws->setCellValue("A{$row}", 'Opción');
                $ws->setCellValue("B{$row}", 'Votos');
                $ws->setCellValue("C{$row}", '%');
                $ws->getStyle("A{$row}:C{$row}")->applyFromArray($hdrStyle);
                $row++;
                foreach ($stats['data'] as $item) {
                    $ws->setCellValue("A{$row}", $item['label']);
                    $ws->setCellValue("B{$row}", $item['count']);
                    $ws->setCellValue("C{$row}", $item['porcentaje'] . '%');
                    $row++;
                }
                $ws->setCellValue("A{$row}", 'Total respuestas: ' . $stats['total']);
                $ws->getStyle("A{$row}")->getFont()->setItalic(true)->setColor((new \PhpOffice\PhpSpreadsheet\Style\Color())->setRGB('6b7280'));
            } elseif ($stats['tipo'] === 'escala_1_5') {
                $ws->setCellValue("A{$row}", 'Valor');
                $ws->setCellValue("B{$row}", 'Votos');
                $ws->setCellValue("C{$row}", '%');
                $ws->getStyle("A{$row}:C{$row}")->applyFromArray($hdrStyle);
                $row++;
                foreach ($stats['data'] as $item) {
                    $ws->setCellValue("A{$row}", $item['label']);
                    $ws->setCellValue("B{$row}", $item['count']);
                    $ws->setCellValue("C{$row}", $item['porcentaje'] . '%');
                    $row++;
                }
                $ws->setCellValue("A{$row}", 'Promedio: ' . ($stats['promedio'] ?? '—') . '   |   Total: ' . $stats['total']);
                $ws->getStyle("A{$row}")->getFont()->setItalic(true);
            } else {
                // texto_libre
                $ws->setCellValue("A{$row}", 'Respuesta');
                $ws->getStyle("A{$row}")->applyFromArray($hdrStyle);
                $row++;
                foreach ($stats['textos'] as $texto) {
                    $ws->mergeCells("A{$row}:D{$row}");
                    $ws->setCellValue("A{$row}", $texto);
                    $ws->getStyle("A{$row}")->getAlignment()->setWrapText(true);
                    $row++;
                }
                if ($stats['textos']->isEmpty()) {
                    $ws->setCellValue("A{$row}", '(Sin respuestas)');
                }
            }

            $row += 2; // Espacio entre preguntas
        }

        foreach (range('A', 'D') as $col) $ws->getColumnDimension($col)->setAutoSize(true);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        $tmp    = tempnam(sys_get_temp_dir(), 'enc_res_') . '.xlsx';
        $writer->save($tmp);

        $slug = \Illuminate\Support\Str::slug($encuesta->titulo, '_');
        return response()->download($tmp, "encuesta_{$slug}_resultados.xlsx", [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
