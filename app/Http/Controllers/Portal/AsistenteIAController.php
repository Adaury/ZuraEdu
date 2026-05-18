<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Asignacion;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\Representante;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class AsistenteIAController extends Controller
{
    // ── Portal Docente ────────────────────────────────────────────────────
    public function chat(Request $request)
    {
        $validated  = $this->validateChat($request);
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();
        $docente    = Docente::where('user_id', $user->id)->first();
        $sysName    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

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

        $systemPrompt =
            "Eres ZuraAI, el asistente académico inteligente de {$sysName}. " .
            "Estás asistiendo a {$nombreDocente}, docente" .
            ($materias   ? " de: {$materias}"                   : '') .
            ($schoolYear ? " — Año escolar {$schoolYear->nombre}" : '') . ".\n\n" .
            "Puedes ayudar con:\n" .
            "- Planificar clases, secuencias didácticas y unidades de aprendizaje\n" .
            "- Generar preguntas, evaluaciones, rúbricas y listas de cotejo\n" .
            "- Redactar observaciones, informes y comunicados para padres\n" .
            "- Sugerir estrategias para atender estudiantes con dificultades\n" .
            "- Explicar metodologías y marcos curriculares (currículo dominicano MINERD)\n" .
            "- Analizar situaciones del aula y proponer soluciones prácticas\n\n" .
            "Responde siempre en español. Sé práctico, concreto y estructurado. " .
            "Cuando generes evaluaciones o planificaciones usa listas o tablas.";

        return $this->stream($validated, $systemPrompt);
    }

    // ── Portal Estudiante ─────────────────────────────────────────────────
    public function chatEstudiante(Request $request)
    {
        $validated  = $this->validateChat($request);
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();
        $estudiante = Estudiante::where('user_id', $user->id)->first();
        $sysName    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $nombreEstudiante = $estudiante?->nombre_completo ?? $user->name;
        $grupo            = '';
        $materias         = '';

        if ($estudiante && $schoolYear) {
            $matricula = $estudiante->matriculas()
                ->with(['grupo', 'grupo.asignaciones.asignatura'])
                ->where('estado', 'activa')
                ->where('school_year_id', $schoolYear->id)
                ->latest()->first();

            if ($matricula) {
                $grupo    = $matricula->grupo?->nombre_completo ?? '';
                $materias = $matricula->grupo?->asignaciones
                    ->map(fn($a) => $a->asignatura?->nombre)
                    ->filter()->unique()->join(', ') ?? '';
            }
        }

        $systemPrompt =
            "Eres ZuraAI, el tutor académico inteligente de {$sysName}. " .
            "Estás ayudando a {$nombreEstudiante}" .
            ($grupo      ? ", estudiante de {$grupo}"            : '') .
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

        return $this->stream($validated, $systemPrompt);
    }

    // ── Portal Padre ──────────────────────────────────────────────────────
    public function chatPadre(Request $request)
    {
        $validated    = $this->validateChat($request);
        $schoolYear   = SchoolYear::actual();
        $user         = Auth::user();
        $representante = Representante::where('user_id', $user->id)->first();
        $sysName      = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));

        $nombrePadre = $representante?->nombre_completo ?? $user->name;
        $hijos       = '';

        if ($representante) {
            $hijos = $representante->estudiantes()
                ->get()
                ->map(fn($e) => $e->nombre_completo)
                ->filter()->join(', ');
        }

        $systemPrompt =
            "Eres ZuraAI, el asistente académico de {$sysName} para representantes y padres de familia. " .
            "Estás asistiendo a {$nombrePadre}" .
            ($hijos      ? ", representante de: {$hijos}"        : '') .
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

        return $this->stream($validated, $systemPrompt);
    }

    // ── Panel Admin ──────────────────────────────────────────────────────
    public function chatAdmin(Request $request)
    {
        $validated  = $this->validateChat($request);
        $schoolYear = SchoolYear::actual();
        $user       = Auth::user();
        $sysName    = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $nivel      = \App\Models\ConfigInstitucional::get('nivel_educativo', config('services.school.nivel', ''));
        $codigo     = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $director   = \App\Models\ConfigInstitucional::get('nombre_director', '');

        $roles   = $user->getRoleNames()->join(', ');
        $nEst    = Estudiante::activos()->count();
        $nDoc    = Docente::activos()->count();
        $nGrupos = $schoolYear ? Grupo::where('school_year_id', $schoolYear->id)->count() : 0;

        $periodoActivo = $schoolYear
            ? \App\Models\Periodo::where('school_year_id', $schoolYear->id)->where('activo', true)->first()
            : null;

        $systemPrompt =
            "Eres ZuraAI, el asistente de gestión institucional de {$sysName} ({$nivel}).\n" .
            "Estás asistiendo a {$user->name} ({$roles})" .
            ($schoolYear ? " — Año escolar: {$schoolYear->nombre}" : '') . ".\n\n" .
            "Datos actuales del centro:\n" .
            "- Centro: {$sysName} | Código: {$codigo} | Director/a: {$director}\n" .
            ($periodoActivo ? "- Período activo: {$periodoActivo->nombre}\n" : '') .
            "- Estudiantes activos: {$nEst} | Docentes activos: {$nDoc} | Grupos: {$nGrupos}\n\n" .
            "Puedes ayudar con:\n" .
            "- Interpretar estadísticas, KPIs y reportes de rendimiento académico\n" .
            "- Redactar circulares, comunicados oficiales, actas y memorandos institucionales\n" .
            "- Analizar datos de asistencia, calificaciones y matrícula\n" .
            "- Sugerir estrategias de mejora basadas en indicadores educativos\n" .
            "- Preparar informes para el MINERD, SIGERD y organismos reguladores dominicanos\n" .
            "- Orientar sobre normativas, reglamentos y currículo dominicano\n" .
            "- Apoyar en planificación institucional, POA y planes de mejora\n" .
            "- Responder dudas sobre el SGE y sus módulos\n\n" .
            "Responde siempre en español. Sé profesional, preciso y estructurado. " .
            "Usa tablas o listas cuando organices información. " .
            "Para documentos oficiales, usa tono formal acorde a instituciones educativas dominicanas.";

        return $this->stream($validated, $systemPrompt);
    }

    // ── Lógica compartida ─────────────────────────────────────────────────
    private function validateChat(Request $request): array
    {
        return $request->validate([
            'message'           => 'required|string|max:4000',
            'history'           => 'nullable|array|max:20',
            'history.*.role'    => 'required|in:user,assistant',
            'history.*.content' => 'required|string|max:8000',
        ]);
    }

    private function stream(array $validated, string $systemPrompt)
    {
        $apiKey = config('services.gemini.key');

        if (empty($apiKey)) {
            return response()->json(['error' => 'API key de Gemini no configurada. Agrega GEMINI_API_KEY en .env'], 503);
        }

        // Gemini usa 'model' en lugar de 'assistant'
        $contents = [];
        foreach (($validated['history'] ?? []) as $h) {
            if (isset($h['role'], $h['content'])) {
                $contents[] = [
                    'role'  => $h['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [['text' => (string) $h['content']]],
                ];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $validated['message']]]];

        Session::save();

        return response()->stream(function () use ($apiKey, $systemPrompt, $contents) {
            $client = new \GuzzleHttp\Client(['timeout' => 90, 'connect_timeout' => 10]);

            try {
                $response = $client->post(
                    "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:streamGenerateContent?alt=sse&key={$apiKey}",
                    [
                        'json' => [
                            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                            'contents'          => $contents,
                            'generationConfig'  => [
                                'temperature'     => 0.7,
                                'maxOutputTokens' => 2048,
                            ],
                        ],
                        'stream' => true,
                    ]
                );

                $body = $response->getBody();
                $buf  = '';

                while (!$body->eof()) {
                    $chunk = $body->read(512);
                    if ($chunk === '') continue;
                    $buf .= $chunk;

                    // Procesa líneas SSE completas
                    while (($pos = strpos($buf, "\n")) !== false) {
                        $line = substr($buf, 0, $pos);
                        $buf  = substr($buf, $pos + 1);

                        if (!str_starts_with($line, 'data: ')) continue;
                        $raw = trim(substr($line, 6));
                        if ($raw === '' || $raw === '[DONE]') continue;

                        $evt  = json_decode($raw, true);
                        $text = $evt['candidates'][0]['content']['parts'][0]['text'] ?? null;
                        if ($text === null) continue;

                        // Re-emite en formato Anthropic SSE (el frontend ya lo maneja)
                        $out = json_encode([
                            'type'  => 'content_block_delta',
                            'delta' => ['type' => 'text_delta', 'text' => $text],
                        ]);
                        echo "data: {$out}\n\n";
                        if (ob_get_level() > 0) ob_flush();
                        flush();
                    }
                }
            } catch (\GuzzleHttp\Exception\ClientException $e) {
                $status = $e->getResponse()->getStatusCode();
                $msg = match($status) {
                    400 => 'Error en la solicitud. Verifica que la GEMINI_API_KEY en .env sea válida.',
                    401, 403 => 'API key inválida o sin permisos. Revisa GEMINI_API_KEY en .env.',
                    429 => 'ZuraAI recibió demasiadas solicitudes. Espera unos segundos e intenta de nuevo.',
                    default => 'Error al conectar con ZuraAI (código ' . $status . '). Intenta de nuevo.',
                };
                $err = json_encode(['type' => 'error', 'error' => ['message' => $msg]]);
                echo "data: {$err}\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            } catch (\Throwable $e) {
                $err = json_encode(['type' => 'error', 'error' => ['message' => 'Error al conectar con ZuraAI. Intenta de nuevo.']]);
                echo "data: {$err}\n\n";
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
