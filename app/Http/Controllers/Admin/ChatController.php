<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use App\Models\SchoolYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatController extends Controller
{
    public function send(Request $request)
    {
        $request->validate([
            'message'  => 'required|string|max:1000',
            'history'  => 'nullable|array',
        ]);

        $apiKey = config('services.gemini.key');

        if (! $apiKey) {
            return response()->json([
                'reply' => '⚠️ La clave de la API de Gemini no está configurada. Agrega GEMINI_API_KEY en el archivo .env.',
            ]);
        }

        // ── Build context about the school ────────────────────────────────
        $schoolYear      = SchoolYear::actual();
        $totalEstudiantes = Estudiante::activos()->count();
        $totalDocentes    = Docente::activos()->count();
        $totalGrupos      = $schoolYear
            ? Grupo::where('school_year_id', $schoolYear->id)->count()
            : 0;

        $schoolName  = \App\Models\ConfigInstitucional::get('nombre_institucion', config('app.name'));
        $schoolNivel = \App\Models\ConfigInstitucional::get('nivel_educativo', config('services.school.nivel', ''));
        $schoolCod   = \App\Models\ConfigInstitucional::get('codigo_centro', '');
        $director    = \App\Models\ConfigInstitucional::get('nombre_director', '');

        // Contexto adicional: alertas y pagos
        $totalAlertas = \App\Models\AlertaSistema::where('leida', false)->count();
        $periodoActivo = $schoolYear
            ? \App\Models\Periodo::where('school_year_id', $schoolYear->id)->where('activo', true)->first()
            : null;
        $moduloPagos = \App\Models\ConfigInstitucional::moduloActivo('pagos');
        $deudores    = $moduloPagos ? \App\Models\Pago::where('estado', 'vencido')
            ->whereHas('matricula', fn($m) => $m->where('school_year_id', $schoolYear?->id))
            ->distinct('matricula_id')->count('matricula_id') : 0;

        $systemPrompt = <<<PROMPT
Eres el asistente virtual del Sistema de Gestión Escolar (SGE) del {$schoolName} — {$schoolNivel}.

Tu función es ayudar al personal con:
- Consultas sobre el sistema: asistencia, calificaciones, matrículas, boletines, horarios, comunicados, pagos
- Orientación sobre flujos de trabajo MINERD (registro, períodos, promoción)
- Resolución de dudas técnicas del SGE
- Análisis de datos del centro (rendimiento, asistencia, situación financiera)

Datos actuales del sistema:
- Centro: {$schoolName} | Código: {$schoolCod} | Director/a: {$director}
- Año escolar: {$schoolYear?->nombre}
- Período activo: {$periodoActivo?->nombre}
- Estudiantes activos: {$totalEstudiantes}
- Docentes activos: {$totalDocentes}
- Grupos este año: {$totalGrupos}
- Alertas sin leer: {$totalAlertas}
- Estudiantes con pagos vencidos: {$deudores}

Módulos disponibles en este SGE:
Estudiantes, Matrículas, Docentes, Asignaciones, Calificaciones (Técnica RA y Académica MINERD),
Asistencia, Boletines PDF, Registro MINERD, Horarios automáticos, Suplencias, Comunicados,
Planificaciones Docentes, Planes de Clase, Observaciones, Recursos, Pagos/Colegiaturas,
Rendimiento y Alertas Académicas, Calendario Académico, Portal Docente, Portal Estudiante,
Portal Padre/Representante, Portal Representante Público.

Responde siempre en español, de forma clara y concisa. Sé específico cuando des instrucciones (menciona rutas del menú). Si no conoces algo, dilo honestamente.
PROMPT;

        // ── Build conversation history ─────────────────────────────────────
        $contents = [];

        // Limitar historial a los últimos 10 intercambios para evitar payloads enormes
        $history = array_slice($request->history ?? [], -10);
        foreach ($history as $msg) {
            if (! empty($msg['role']) && ! empty($msg['text'])) {
                $contents[] = [
                    'role'  => $msg['role'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => mb_substr($msg['text'], 0, 500)]],
                ];
            }
        }

        // Add current message
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $request->message]],
        ];

        // ── Call Gemini API ────────────────────────────────────────────────
        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}",
                [
                    'systemInstruction' => [
                        'parts' => [['text' => $systemPrompt]],
                    ],
                    'contents'          => $contents,
                    'generationConfig'  => [
                        'temperature'     => 0.7,
                        'maxOutputTokens' => 800,
                    ],
                ]
            );

            if ($response->successful()) {
                $data  = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text']
                         ?? 'No pude generar una respuesta. Intenta de nuevo.';
            } else {
                $error = $response->json('error.message', 'Error desconocido');
                $reply = "Error de la API: {$error}";
            }
        } catch (\Exception $e) {
            $reply = 'No se pudo conectar con el servicio de IA. Verifica tu conexión.';
        }

        return response()->json(['reply' => $reply]);
    }
}
