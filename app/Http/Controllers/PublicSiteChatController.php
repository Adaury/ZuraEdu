<?php

namespace App\Http\Controllers;

use App\Models\ConfigInstitucional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Chat con IA del sitio público — distinto, a propósito, del ZuraAI interno
 * (Admin\ChatController): este es anónimo (sin login) y solo puede usar
 * información PÚBLICA ya configurada en el Homepage (ConfigInstitucional).
 * Nunca datos de estudiantes, docentes, pagos ni nada operativo interno.
 */
class PublicSiteChatController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500',
            'history' => 'nullable|array',
        ]);

        $tenant = app('tenant');

        if (! $tenant->can('modo_publico')) {
            return response()->json(['reply' => 'Este chat no está disponible en este momento.'], 403);
        }

        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            return response()->json(['reply' => 'El chat no está disponible en este momento.']);
        }

        $nombre    = ConfigInstitucional::get('nombre_institucion', '') ?: $tenant->nombre_institucion;
        $sobre     = ConfigInstitucional::get('hp_about_texto', '');
        $direccion = ConfigInstitucional::get('hp_contacto_direccion', '');
        $telefono  = ConfigInstitucional::get('hp_contacto_telefono', '');
        $email     = ConfigInstitucional::get('hp_contacto_email', '');

        $datos = collect([
            'Nombre'    => $nombre,
            'Sobre la institución' => $sobre,
            'Dirección' => $direccion,
            'Teléfono'  => $telefono,
            'Correo'    => $email,
        ])->filter(fn ($v) => filled($v))
          ->map(fn ($v, $k) => "- {$k}: {$v}")
          ->implode("\n");

        $systemPrompt = <<<PROMPT
Eres el asistente virtual del sitio público de {$nombre}, un centro educativo. Respondes preguntas de visitantes anónimos (padres interesados, estudiantes, público en general) sobre la institución.

Información pública disponible:
{$datos}

Reglas estrictas:
- Solo puedes usar la información de arriba. NUNCA inventes datos que no tengas (horarios exactos, precios, cupos, requisitos de admisión, etc.) — si te preguntan algo que no sabes, indica amablemente que deben contactar directamente al centro usando los datos de contacto de arriba.
- NUNCA reveles ni comentes datos de estudiantes, docentes, pagos, calificaciones ni ninguna información interna del sistema de gestión — no tienes acceso a eso y no debes fingir que sí.
- Responde siempre en español, de forma breve, cordial y profesional.
PROMPT;

        $contents = [];
        $history = array_slice($request->input('history', []), -6);
        foreach ($history as $msg) {
            if (! empty($msg['role']) && ! empty($msg['text'])) {
                $contents[] = [
                    'role'  => $msg['role'] === 'user' ? 'user' : 'model',
                    'parts' => [['text' => mb_substr($msg['text'], 0, 400)]],
                ];
            }
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $request->message]]];

        try {
            $response = Http::timeout(20)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}",
                [
                    'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                    'contents'          => $contents,
                    'generationConfig'  => [
                        'temperature'     => 0.5,
                        'maxOutputTokens' => 400,
                    ],
                ]
            );

            if ($response->successful()) {
                $data  = $response->json();
                $reply = $data['candidates'][0]['content']['parts'][0]['text']
                         ?? 'No pude generar una respuesta. Intenta de nuevo.';
            } else {
                $reply = 'No pude procesar tu consulta en este momento. Intenta más tarde.';
            }
        } catch (\Throwable $e) {
            $reply = 'No se pudo conectar con el servicio de IA. Intenta más tarde.';
        }

        return response()->json(['reply' => $reply]);
    }
}
