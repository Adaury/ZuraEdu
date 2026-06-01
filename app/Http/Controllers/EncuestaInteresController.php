<?php

namespace App\Http\Controllers;

use App\Models\EncuestaInteres;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EncuestaInteresController extends Controller
{
    private const NOTIFICAR_A = '8294778613'; // Ing. Adaury Paulino

    public function show()
    {
        return view('encuesta.index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipo'          => 'required|in:docente,administrativo',
            'nombre'        => 'required|string|max:80',
            'apellido'      => 'required|string|max:80',
            'telefono'      => 'required|string|max:30',
            'nivel_interes' => 'required|integer|min:1|max:5',
            'respuestas'    => 'required|array',
        ]);

        $encuesta = EncuestaInteres::create([
            'tipo'          => $data['tipo'],
            'nombre'        => $data['nombre'],
            'apellido'      => $data['apellido'],
            'telefono'      => $data['telefono'],
            'nivel_interes' => $data['nivel_interes'],
            'respuestas'    => $data['respuestas'],
            'ip'            => $request->ip(),
        ]);

        $this->notificarAdmin($encuesta);

        return response()->json(['ok' => true, 'id' => $encuesta->id]);
    }

    private function notificarAdmin(EncuestaInteres $e): void
    {
        try {
            $tipo   = $e->tipo === 'docente' ? 'Docente' : 'Administrativo';
            $temas  = $this->resumirTemas($e);

            $msg = "🎯 *ZuraEdu — Nueva solicitud de demo*\n\n"
                 . "*Nombre:* {$e->nombre} {$e->apellido}\n"
                 . "*Teléfono:* {$e->telefono}\n"
                 . "*Perfil:* {$tipo}\n"
                 . "*Nivel de interés:* {$e->nivel_interes}/5\n"
                 . ($temas ? "*Intereses:* {$temas}\n" : '')
                 . "\nRecibida: " . now()->format('d/m/Y H:i');

            WhatsAppService::send(self::NOTIFICAR_A, $msg);
        } catch (\Throwable $ex) {
            Log::warning('EncuestaInteres: no se pudo notificar — ' . $ex->getMessage());
        }
    }

    private function resumirTemas(EncuestaInteres $e): string
    {
        $r = $e->respuestas;
        $items = $r['d-auto'] ?? $r['a-carga'] ?? $r['a-mods'] ?? [];
        return implode(', ', array_slice((array) $items, 0, 4));
    }
}
