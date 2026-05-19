<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\VentaCafeteria;
use Illuminate\Http\Request;

class CafeteriaApiController extends Controller
{
    /** GET /api/v1/cafeteria/saldo — para Estudiante */
    public function saldo(Request $request)
    {
        $user       = $request->user();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        if (! $estudiante) return response()->json(['message' => 'Perfil no encontrado.'], 404);

        return response()->json($this->buildSaldo($estudiante));
    }

    /** GET /api/v1/cafeteria/saldo-hijo/{estudiante} — para Representante */
    public function saldoHijo(Request $request, Estudiante $estudiante)
    {
        $rep = Representante::where('user_id', $request->user()->id)->first();
        if (! $rep || ! $rep->estudiantes()->where('estudiante_id', $estudiante->id)->exists()) {
            return response()->json(['message' => 'Acceso no autorizado.'], 403);
        }

        return response()->json($this->buildSaldo($estudiante));
    }

    private function buildSaldo(Estudiante $estudiante): array
    {
        $saldo         = VentaCafeteria::saldoEstudiante($estudiante->id);
        $totalRecargado = VentaCafeteria::where('estudiante_id', $estudiante->id)
            ->where('tipo', 'recarga')->sum('monto');
        $totalGastado  = VentaCafeteria::where('estudiante_id', $estudiante->id)
            ->where('tipo', 'venta')->sum('monto');

        $historial = VentaCafeteria::where('estudiante_id', $estudiante->id)
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($v) => [
                'id'          => $v->id,
                'tipo'        => $v->tipo,
                'descripcion' => $v->descripcion ?? ($v->tipo === 'recarga' ? 'Recarga' : 'Consumo'),
                'monto'       => (float) $v->monto,
                'fecha'       => $v->created_at->toDateString(),
                'hora'        => $v->created_at->format('H:i'),
            ]);

        return [
            'estudiante'     => $estudiante->nombre_completo,
            'saldo'          => (float) $saldo,
            'total_recargado'=> (float) $totalRecargado,
            'total_gastado'  => (float) $totalGastado,
            'historial'      => $historial,
        ];
    }
}
