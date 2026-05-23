<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarnetAcceso;
use App\Models\CarnetIdentidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarnetReportesController extends Controller
{
    public function index(Request $request)
    {
        $fecha = $request->fecha ? now()->parse($request->fecha) : today();

        // KPIs del día
        $entradas  = CarnetAcceso::whereDate('created_at', $fecha)->where('tipo_evento', 'entrada')->count();
        $salidas   = CarnetAcceso::whereDate('created_at', $fecha)->where('tipo_evento', 'salida')->count();
        $tardanzas = CarnetAcceso::whereDate('created_at', $fecha)->where('estado', 'tardanza')->count();
        $total     = CarnetIdentidad::activos()->where('tipo', 'estudiante')->count();
        $ausentes  = max(0, $total - $entradas);

        // Actividad por hora (últimas 24h)
        $porHora = CarnetAcceso::selectRaw('HOUR(created_at) as hora, COUNT(*) as total')
            ->whereDate('created_at', $fecha)
            ->groupBy('hora')
            ->orderBy('hora')
            ->pluck('total', 'hora')
            ->toArray();

        $horaLabels = array_map(fn($h) => sprintf('%02d:00', $h), range(5, 18));
        $horaSeries = array_map(fn($h) => $porHora[$h] ?? 0, range(5, 18));

        // Tendencia semanal (últimos 7 días — entradas)
        $tendencia = CarnetAcceso::selectRaw('DATE(created_at) as dia, COUNT(*) as total')
            ->where('tipo_evento', 'entrada')
            ->where('created_at', '>=', now()->subDays(6)->startOfDay())
            ->groupBy('dia')
            ->orderBy('dia')
            ->pluck('total', 'dia')
            ->toArray();

        $tendDias    = [];
        $tendValues  = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = now()->subDays($i)->toDateString();
            $tendDias[]   = now()->subDays($i)->format('d/M');
            $tendValues[] = $tendencia[$d] ?? 0;
        }

        // Top ausentes recurrentes (últimos 7 días)
        $ausRecurrentes = CarnetIdentidad::with(['user', 'matricula.grupo'])
            ->activos()
            ->where('tipo', 'estudiante')
            ->get()
            ->filter(function ($c) {
                $entradas7 = CarnetAcceso::withoutTenant()
                    ->where('tenant_id', $c->tenant_id)
                    ->where('carnet_identidad_id', $c->id)
                    ->where('tipo_evento', 'entrada')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count();
                return $entradas7 <= 2; // ausente ≥ 5 de 7 días
            })
            ->take(5);

        return view('admin.carnet.reportes', compact(
            'fecha', 'entradas', 'salidas', 'tardanzas', 'total', 'ausentes',
            'horaLabels', 'horaSeries', 'tendDias', 'tendValues', 'ausRecurrentes'
        ));
    }

    // ── API JSON para ApexCharts ──────────────────────────────────────────────

    public function chartData(Request $request)
    {
        $dias = (int) ($request->dias ?? 7);

        $data = CarnetAcceso::selectRaw('DATE(created_at) as dia, estado, COUNT(*) as total')
            ->where('tipo_evento', 'entrada')
            ->where('created_at', '>=', now()->subDays($dias - 1)->startOfDay())
            ->groupBy('dia', 'estado')
            ->get()
            ->groupBy('dia');

        $result = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $d     = now()->subDays($i)->toDateString();
            $grupo = $data->get($d, collect());
            $result[] = [
                'dia'       => now()->subDays($i)->format('d/M'),
                'presentes' => $grupo->where('estado', 'presente')->sum('total'),
                'tardanzas' => $grupo->where('estado', 'tardanza')->sum('total'),
            ];
        }

        return response()->json($result);
    }
}
