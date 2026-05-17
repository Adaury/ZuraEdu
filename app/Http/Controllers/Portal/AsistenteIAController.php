<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AsistenteIAController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message'           => 'required|string|max:4000',
            'history'           => 'nullable|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:8000',
        ]);

        $apiKey = config('services.anthropic.key');
        $model  = config('services.anthropic.model', 'claude-haiku-4-5-20251001');

        if (empty($apiKey)) {
            return response()->json(['error' => 'API key de Anthropic no configurada. Agrega ANTHROPIC_API_KEY en .env'], 503);
        }

        // Build docente context
        $user       = Auth::user();
        $docente    = Docente::where('user_id', $user->id)->first();
        $schoolYear = SchoolYear::actual();

        $nombreDocente = $docente?->nombre_completo ?? $user->name;
        $materias      = '';

        if ($docente && $schoolYear) {
            $asignaciones = Asignacion::with(['asignatura', 'grupo'])
                ->where('docente_id', $docente->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)
                ->get();

            $materias = $asignaciones->map(fn($a) =>
                ($a->asignatura?->nombre ?? '?') . ' (' . ($a->grupo?->nombre_completo ?? '?') . ')'
            )->join(', ');
        }

        $sysName = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $systemPrompt =
            "Eres ZuraAI, el asistente académico inteligente de {$sysName}. " .
            "Estás asistiendo a {$nombreDocente}, docente" .
            ($materias      ? " de: {$materias}"              : '') .
            ($schoolYear    ? " — Año escolar {$schoolYear->nombre}" : '') . ".\n\n" .
            "Puedes ayudar con:\n" .
            "- Planificar clases, secuencias didácticas y unidades de aprendizaje\n" .
            "- Generar preguntas, evaluaciones, rúbricas y listas de cotejo\n" .
            "- Redactar observaciones, informes y comunicados para padres\n" .
            "- Sugerir estrategias para atender estudiantes con dificultades\n" .
            "- Explicar metodologías y marcos curriculares (currículo dominicano MINERD)\n" .
            "- Analizar situaciones del aula y proponer soluciones prácticas\n\n" .
            "Responde siempre en español. Sé práctico, concreto y estructurado. " .
            "Cuando generes evaluaciones o planificaciones usa listas o tablas.";

        $messages = [];
        foreach (($validated['history'] ?? []) as $h) {
            if (isset($h['role'], $h['content'])) {
                $messages[] = ['role' => $h['role'], 'content' => (string) $h['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $validated['message']];

        // Release session lock before streaming
        Session::save();

        return response()->stream(function () use ($apiKey, $model, $systemPrompt, $messages) {
            $client = new \GuzzleHttp\Client(['timeout' => 90, 'connect_timeout' => 10]);

            try {
                $response = $client->post('https://api.anthropic.com/v1/messages', [
                    'headers' => [
                        'x-api-key'         => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type'      => 'application/json',
                        'accept'            => 'text/event-stream',
                    ],
                    'json' => [
                        'model'      => $model,
                        'max_tokens' => 2048,
                        'stream'     => true,
                        'system'     => $systemPrompt,
                        'messages'   => $messages,
                    ],
                    'stream' => true,
                ]);

                $body = $response->getBody();
                while (!$body->eof()) {
                    $chunk = $body->read(512);
                    if ($chunk !== '') {
                        echo $chunk;
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }
            } catch (\Throwable $e) {
                $err = json_encode(['type' => 'error', 'error' => ['message' => 'Error al conectar con ZuraAI. Intenta de nuevo.']]);
                echo "event: error\ndata: {$err}\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
            'Connection'        => 'keep-alive',
        ]);
    }
}
