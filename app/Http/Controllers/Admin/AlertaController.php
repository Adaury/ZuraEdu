<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AlertaSistema;
use App\Services\AcademicAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class AlertaController extends Controller
{
    public function index()
    {
        $user   = Auth::user();
        $roles  = $user->getRoleNames()->toArray();

        $alertas = AlertaSistema::where(function ($q) use ($user, $roles) {
                $q->where('destinatario_id', $user->id)
                  ->orWhereIn('destinatario_rol', $roles);
            })
            ->vigentes()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.alertas.index', compact('alertas'));
    }

    public function conteo()
    {
        $user  = Auth::user();
        $roles = $user->getRoleNames()->toArray();

        $total = AlertaSistema::where(function ($q) use ($user, $roles) {
                $q->where('destinatario_id', $user->id)
                  ->orWhereIn('destinatario_rol', $roles);
            })
            ->noLeidas()
            ->vigentes()
            ->count();

        return response()->json(['total' => $total]);
    }

    public function marcarLeida(AlertaSistema $alerta)
    {
        $alerta->marcarLeida();
        Cache::forget('alertas_no_leidas_' . Auth::id());
        return response()->json(['ok' => true]);
    }

    public function marcarTodasLeidas()
    {
        $user  = Auth::user();
        $roles = $user->getRoleNames()->toArray();

        AlertaSistema::where(function ($q) use ($user, $roles) {
                $q->where('destinatario_id', $user->id)
                  ->orWhereIn('destinatario_rol', $roles);
            })
            ->noLeidas()
            ->vigentes()
            ->update(['leida' => true, 'fecha_leida' => now()]);

        Cache::forget('alertas_no_leidas_' . $user->id);
        return response()->json(['ok' => true]);
    }

    public function destroy(AlertaSistema $alerta)
    {
        $alerta->delete();
        Cache::forget('alertas_no_leidas_' . Auth::id());
        return response()->json(['ok' => true]);
    }

    public function pdf()
    {
        $alertas = AlertaSistema::vigentes()
            ->with('destinatario')
            ->orderByRaw("FIELD(nivel,'critica','alta','media','baja')")
            ->orderBy('created_at', 'desc')
            ->get();

        $inst = \App\Models\ConfigInstitucional::first()?->nombre ?? 'Institución';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.alertas.alertas_pdf', compact('alertas', 'inst'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('Alertas_' . now()->format('Ymd') . '.pdf');
    }

    public function excel()
    {
        $alertas = AlertaSistema::vigentes()
            ->orderByRaw("FIELD(nivel,'critica','alta','media','baja')")
            ->orderBy('created_at', 'desc')
            ->get();

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $ss->getActiveSheet();
        $ws->setTitle('Alertas');

        $headers = ['#', 'Título', 'Mensaje', 'Tipo', 'Nivel', 'Fecha'];
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue("{$col}1", $h);
        }
        $ws->getStyle('A1:F1')->getFont()->setBold(true);
        $ws->getStyle('A1:F1')->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setRGB('1e3a6e');
        $ws->getStyle('A1:F1')->getFont()->getColor()->setRGB('ffffff');

        $nivelColors = [
            'critica' => 'fee2e2',
            'alta'    => 'ffedd5',
            'media'   => 'fffbeb',
            'baja'    => 'f0fdf4',
        ];

        foreach ($alertas as $i => $alerta) {
            $row = $i + 2;
            $ws->setCellValue("A{$row}", $i + 1);
            $ws->setCellValue("B{$row}", $alerta->titulo);
            $ws->setCellValue("C{$row}", \Illuminate\Support\Str::limit($alerta->mensaje, 100));
            $ws->setCellValue("D{$row}", $alerta->tipo);
            $ws->setCellValue("E{$row}", ucfirst($alerta->nivel ?? ''));
            $ws->setCellValue("F{$row}", $alerta->created_at?->format('d/m/Y'));

            $color = $nivelColors[$alerta->nivel] ?? 'f8fafc';
            $ws->getStyle("A{$row}:F{$row}")->getFill()
               ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
               ->getStartColor()->setRGB($color);
        }

        foreach (range(1, 6) as $ci) {
            $ws->getColumnDimension(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($ci)
            )->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
        return response()->stream(fn() => $writer->save('php://output'), 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="Alertas_' . now()->format('Ymd') . '.xlsx"',
        ]);
    }

    public function generarAcademicas(Request $request)
    {
        $service = new AcademicAlertService();
        $result  = $service->evaluarTodos();

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        $msg = "Evaluación completada: {$result['generadas']} alerta(s) generada(s).";
        if ($result['omitidas'] > 0) {
            $msg .= " {$result['omitidas']} ya existían.";
        }

        return back()->with('success', $msg);
    }
}
