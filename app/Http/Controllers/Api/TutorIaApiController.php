<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\ConfigInstitucional;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Representante;
use App\Models\SchoolYear;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class TutorIaApiController extends Controller
{
    /**
     * POST /api/v1/ai/chat
     *
     * Body: { message: string, history: [{role:'user'|'assistant', content:string}]? }
     */
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message'           => 'required|string|max:4000',
            'history'           => 'nullable|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:8000',
        ]);

        $apiKey = config('services.gemini.key');
        if (empty($apiKey)) {
            return response()->json(['error' => 'Servicio de IA no configurado.'], 503);
        }

        $user       = $request->user();
        $role       = $user->roles->first()?->name ?? '';
        $schoolYear = SchoolYear::actual();
        $sysName    = ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $systemPrompt = match (true) {
            $role === 'Estudiante'    => $this->promptEstudiante($user, $schoolYear, $sysName),
            $role === 'Representante' => $this->promptPadre($user, $schoolYear, $sysName),
            $role === 'Docente'       => $this->promptDocente($user, $schoolYear, $sysName),
            default                   => $this->promptGeneral($user, $sysName),
        };

        // Construir historial para Gemini
        $contents = [];
        foreach ($validated['history'] ?? [] as $h) {
            $contents[] = [
                'role'  => $h['role'] === 'assistant' ? 'model' : 'user',
                'parts' => [['text' => (string) $h['content']]],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $validated['message']]]];

        try {
            $client   = new Client(['timeout' => 60, 'connect_timeout' => 10]);
            $response = $client->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'json' => [
                        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                        'contents'          => $contents,
                        'generationConfig'  => [
                            'temperature'     => 0.7,
                            'maxOutputTokens' => 2048,
                        ],
                    ],
                ]
            );

            $body = json_decode($response->getBody()->getContents(), true);
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($text === null) {
                return response()->json(['error' => 'Sin respuesta del modelo.'], 502);
            }

            return response()->json([
                'response' => $text,
                'role'     => $role,
            ]);
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $status = $e->getResponse()->getStatusCode();
            $msg = match ($status) {
                401, 403 => 'API key de IA inválida o sin permisos.',
                429      => 'Demasiadas solicitudes. Espera un momento e intenta de nuevo.',
                default  => 'Error al conectar con el servicio de IA (código ' . $status . ').',
            };
            return response()->json(['error' => $msg], 502);
        } catch (\Throwable) {
            return response()->json(['error' => 'Error al conectar con el servicio de IA.'], 502);
        }
    }

    private function promptEstudiante($user, $schoolYear, string $sysName): string
    {
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        $nombre     = $estudiante?->nombre_completo ?? $user->name;
        $grupo      = '';
        $materias   = '';

        if ($estudiante && $schoolYear) {
            $matricula = $estudiante->matriculas()
                ->with(['grupo', 'grupo.asignaciones.asignatura'])
                ->where('estado', 'activa')
                ->where('school_year_id', $schoolYear->id)
                ->latest()->first();

            if ($matricula) {
                $grupo    = $matricula->grupo?->nombre_completo ?? '';
                $materias = $matricula->grupo?->asignaciones
                    ->map(fn($a) => $a->asignatura?->nombre)->filter()->unique()->join(', ') ?? '';
            }
        }

        return "Eres ZuraAI, el tutor académico inteligente de {$sysName}. " .
            "Estás ayudando a {$nombre}" .
            ($grupo      ? ", estudiante de {$grupo}"             : '') .
            ($schoolYear ? " — Año escolar {$schoolYear->nombre}" : '') .
            ($materias   ? ". Sus materias: {$materias}"          : '') . ".\n\n" .
            "Tu rol es ser un tutor paciente y motivador. Puedes ayudar con:\n" .
            "- Explicar temas y conceptos de cualquier materia de forma clara\n" .
            "- Resolver dudas de tareas, ejercicios y trabajos\n" .
            "- Repasar contenidos para exámenes con ejemplos y resúmenes\n" .
            "- Orientar cómo organizar el tiempo de estudio\n" .
            "- Sugerir técnicas de aprendizaje (mapas conceptuales, flashcards, etc.)\n" .
            "- Ayudar a redactar ensayos, reportes e informes académicos\n\n" .
            "Responde siempre en español. Adapta el lenguaje a un estudiante de nivel secundario. " .
            "Sé amigable, claro y usa ejemplos concretos. " .
            "Si el estudiante comete un error, corrígelo con amabilidad y explica por qué.";
    }

    private function promptPadre($user, $schoolYear, string $sysName): string
    {
        $representante = Representante::where('user_id', $user->id)->first();
        $nombre        = $representante?->nombre_completo ?? $user->name;
        $hijos         = '';

        if ($representante) {
            $hijos = $representante->estudiantes()->get()
                ->map(fn($e) => $e->nombre_completo)->filter()->join(', ');
        }

        return "Eres ZuraAI, el asistente académico de {$sysName} para representantes y padres de familia. " .
            "Estás asistiendo a {$nombre}" .
            ($hijos      ? ", representante de: {$hijos}"         : '') .
            ($schoolYear ? " — Año escolar {$schoolYear->nombre}" : '') . ".\n\n" .
            "Puedes ayudar con:\n" .
            "- Entender las calificaciones, boletines e informes de su hijo/a\n" .
            "- Sugerir cómo apoyar el aprendizaje y los estudios en casa\n" .
            "- Orientar sobre hábitos de estudio y organización del tiempo\n" .
            "- Explicar términos académicos y el sistema de evaluación\n" .
            "- Preparar preguntas para reuniones con docentes o coordinación\n" .
            "- Consejos para mejorar la comunicación familia-escuela\n\n" .
            "Responde siempre en español. Sé empático, claro y práctico. " .
            "Usa un lenguaje accesible para padres, evitando tecnicismos innecesarios.";
    }

    private function promptDocente($user, $schoolYear, string $sysName): string
    {
        $docente  = Docente::where('user_id', $user->id)->first();
        $nombre   = $docente?->nombre_completo ?? $user->name;
        $materias = '';

        if ($docente && $schoolYear) {
            $materias = Asignacion::with(['asignatura', 'grupo'])
                ->where('docente_id', $docente->id)
                ->where('school_year_id', $schoolYear->id)
                ->where('activo', true)->get()
                ->map(fn($a) => ($a->asignatura?->nombre ?? '?') . ' (' . ($a->grupo?->nombre_completo ?? '?') . ')')
                ->join(', ');
        }

        return "Eres ZuraAI, el asistente académico inteligente de {$sysName}. " .
            "Estás asistiendo a {$nombre}, docente" .
            ($materias   ? " de: {$materias}"                    : '') .
            ($schoolYear ? " — Año escolar {$schoolYear->nombre}" : '') . ".\n\n" .
            "Puedes ayudar con:\n" .
            "- Planificar clases, secuencias didácticas y unidades de aprendizaje\n" .
            "- Generar preguntas, evaluaciones, rúbricas y listas de cotejo\n" .
            "- Redactar observaciones, informes y comunicados para padres\n" .
            "- Sugerir estrategias para atender estudiantes con dificultades\n" .
            "- Explicar metodologías y marcos curriculares (currículo dominicano MINERD)\n\n" .
            "Responde siempre en español. Sé práctico, concreto y estructurado.";
    }

    private function promptGeneral($user, string $sysName): string
    {
        return "Eres ZuraAI, el asistente académico de {$sysName}. " .
            "Ayuda a {$user->name} con cualquier consulta académica o educativa. " .
            "Responde siempre en español de forma clara y concisa.";
    }
}
