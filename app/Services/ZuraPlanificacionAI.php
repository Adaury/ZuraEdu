<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZuraPlanificacionAI
{
    private string $apiKey;
    private string $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY', ''));
    }

    // ── Generar contenido para un RA ────────────────────────────────────────
    public function generarRA(array $params): array
    {
        $asignatura    = $params['asignatura'] ?? 'Informática';
        $grupo         = $params['grupo'] ?? '';
        $familia       = $params['familia_profesional'] ?? 'Informática y Comunicaciones';
        $modulo        = $params['modulo'] ?? $asignatura;
        $raCodigo      = $params['ra_codigo'] ?? 'RA';
        $raHint        = $params['ra_hint'] ?? '';
        $nivel         = $params['nivel_taxonomico'] ?? 'Aplicación';
        $contexto      = $params['contexto'] ?? '';

        $prompt = <<<EOT
Eres un experto en planificación curricular técnico-profesional dominicano (MINERD).
Genera una planificación completa para UN Resultado de Aprendizaje (RA) en formato JSON.

CONTEXTO:
- Asignatura/Módulo: {$asignatura}
- Familia Profesional: {$familia}
- Módulo: {$modulo}
- Sesión/Grupo: {$grupo}
- Código RA: {$raCodigo}
- Nivel Taxonómico: {$nivel}
- Descripción breve del RA (hint del docente): "{$raHint}"
- Contexto adicional: "{$contexto}"

RESPONDE SOLO con JSON sin markdown, con esta estructura exacta:
{
  "ra_descripcion": "Descripción completa y formal del Resultado de Aprendizaje (2-3 oraciones)",
  "elementos_capacidad": [
    "1- Primer elemento de capacidad específico",
    "2- Segundo elemento de capacidad específico",
    "3- Tercer elemento de capacidad específico",
    "4- Cuarto elemento de capacidad específico",
    "5- Quinto elemento de capacidad específico"
  ],
  "actividades": "Actividad 1: [descripción de actividad de inicio-desarrollo]\nActividad 2: [siguiente actividad]\nActividad 3: [siguiente actividad]\nActividad 4: [actividad práctica]\nActividad 5: [cierre o evaluación]",
  "instrumentos_evaluacion": "- Indagación de saberes previos\n- Rúbrica de evaluación\n- Lista de cotejo\n- Prueba práctica",
  "contenidos": "- Concepto 1 relevante al RA\n- Concepto 2\n- Concepto 3\n- Concepto 4\n- Concepto 5"
}

Requisitos:
- Los elementos de capacidad deben ser concretos y medibles
- Las actividades deben seguir el ciclo aprendizaje: exploración, conceptualización, aplicación
- Los contenidos deben ser coherentes con el nivel técnico-profesional dominicano
- Usa lenguaje formal educativo dominicano
- El RA debe redactarse comenzando con un verbo en infinitivo (Seleccionar, Instalar, Configurar, etc.)
EOT;

        return $this->llamarGemini($prompt);
    }

    // ── Generar contenido para una Actividad ────────────────────────────────
    public function generarActividad(array $params): array
    {
        $asignatura = $params['asignatura'] ?? 'Informática';
        $grupo      = $params['grupo'] ?? '';
        $objetivo   = $params['objetivo_hint'] ?? '';
        $contexto   = $params['contexto'] ?? '';
        $raCodigo   = $params['ra_codigo'] ?? '';

        $prompt = <<<EOT
Eres un experto en planificación docente para educación técnico-profesional dominicana (MINERD).
Genera el contenido para una planificación por actividades en formato JSON.

CONTEXTO:
- Asignatura: {$asignatura}
- Sesión/Grupo: {$grupo}
- Código RA (si aplica): {$raCodigo}
- Objetivo de la actividad (hint): "{$objetivo}"
- Contexto adicional: "{$contexto}"

RESPONDE SOLO con JSON sin markdown, con esta estructura exacta:
{
  "objetivo": "Objetivo de aprendizaje completo y medible de la actividad",
  "act_inicio": "Descripción detallada de las actividades de inicio (motivación, exploración, saberes previos) — 3-4 oraciones",
  "act_desarrollo": "Descripción detallada de las actividades de desarrollo (conceptualización, práctica guiada, trabajo colaborativo) — 4-5 oraciones",
  "act_cierre": "Descripción detallada de las actividades de cierre (síntesis, reflexión, evaluación) — 2-3 oraciones",
  "estrategias": "- Aprendizaje basado en proyectos\n- Trabajo colaborativo\n- Demostración práctica\n- Pregunta generadora",
  "recursos": "- Computadoras\n- Proyector multimedia\n- Guía de trabajo\n- Material didáctico digital",
  "instrumentos_evaluacion": "- Rúbrica de evaluación práctica\n- Lista de cotejo\n- Observación directa"
}

Requisitos:
- El objetivo debe comenzar con un verbo de acción en infinitivo
- Las actividades deben seguir la estructura didáctica inicio-desarrollo-cierre
- Usa lenguaje pedagógico formal dominicano
- Adapta el contenido al área técnica de {$asignatura}
EOT;

        return $this->llamarGemini($prompt);
    }

    // ── Mejorar texto existente ─────────────────────────────────────────────
    public function mejorarTexto(string $campo, string $texto, string $contexto = ''): string
    {
        $prompt = <<<EOT
Eres un experto en redacción pedagógica técnico-profesional dominicana.
Mejora el siguiente texto para el campo "{$campo}" de una planificación docente.
Mantén el significado, mejora la redacción y el lenguaje formal educativo.
Contexto: {$contexto}

Texto a mejorar:
"{$texto}"

Responde SOLO con el texto mejorado, sin comillas ni explicaciones adicionales.
EOT;

        $response = Http::timeout(30)->post(
            $this->endpoint . '?key=' . $this->apiKey,
            [
                'contents'          => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig'  => ['temperature' => 0.6, 'maxOutputTokens' => 512],
            ]
        );

        if (! $response->successful()) {
            return $texto;
        }

        $text = $response->json('candidates.0.content.parts.0.text', $texto);
        return trim($text);
    }

    // ── Llamada base a Gemini ───────────────────────────────────────────────
    private function llamarGemini(string $prompt): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'API key de Gemini no configurada. Agrega GEMINI_API_KEY en el archivo .env'];
        }

        try {
            $response = Http::timeout(45)->post(
                $this->endpoint . '?key=' . $this->apiKey,
                [
                    'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                    'generationConfig' => [
                        'temperature'      => 0.75,
                        'maxOutputTokens'  => 3000,
                        'responseMimeType' => 'application/json',
                    ],
                ]
            );

            if (! $response->successful()) {
                Log::error('ZuraPlanificacionAI error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['error' => 'Error al conectar con el servicio de IA (HTTP ' . $response->status() . '). Intente de nuevo.'];
            }

            $text = $response->json('candidates.0.content.parts.0.text', '');

            // Limpiar posibles bloques markdown
            $text = preg_replace('/^```json\s*/i', '', trim($text));
            $text = preg_replace('/\s*```$/', '', $text);

            $data = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('ZuraPlanificacionAI: respuesta JSON inválida', ['text' => $text]);
                return ['error' => 'La IA devolvió una respuesta inválida. Intente de nuevo.'];
            }

            return $data;

        } catch (\Throwable $e) {
            Log::error('ZuraPlanificacionAI exception', ['message' => $e->getMessage()]);
            return ['error' => 'Error de conexión con el servicio de IA: ' . $e->getMessage()];
        }
    }
}
