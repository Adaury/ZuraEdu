<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeInput
{
    /**
     * Fields that must never be touched (tokens, passwords, binary data, etc.)
     */
    protected array $skipFields = [
        '_token', '_method',
        'password', 'password_confirmation', 'current_password',
        'whatsapp_auth_token', 'whatsapp_account_sid',
    ];

    /**
     * Fields allowed to contain richer content (HTML, markdown, etc.)
     */
    protected array $allowedRichFields = [
        'descripcion', 'contenido', 'mensaje', 'notas', 'observacion',
        'notas_medicas', 'trial_mensaje', 'cuerpo', 'planificacion', 'actividad',
        'respuesta', 'justificacion', 'retroalimentacion', 'explicacion',
        'texto', 'motivo', 'criterio',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $input = $request->all();
        $request->merge($this->sanitize($input));

        return $next($request);
    }

    private function sanitize(array $data, string $prefix = ''): array
    {
        $clean = [];
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : (string) $key;

            if (in_array($key, $this->skipFields)) {
                $clean[$key] = $value;
            } elseif (is_array($value)) {
                $clean[$key] = $this->sanitize($value, $fullKey);
            } elseif (is_string($value)) {
                if (in_array($key, $this->allowedRichFields)) {
                    $clean[$key] = $this->stripDangerousTags($value);
                } else {
                    $clean[$key] = $this->cleanText($value);
                }
            } else {
                $clean[$key] = $value;
            }
        }
        return $clean;
    }

    private function cleanText(string $value): string
    {
        // Remove HTML tags
        $value = strip_tags($value);
        // Remove null bytes
        $value = str_replace("\0", '', $value);
        // Normalize whitespace (keep newlines for textareas)
        $value = preg_replace('/[ \t]+/', ' ', $value);
        return trim($value);
    }

    private function stripDangerousTags(string $value): string
    {
        // Remove script, iframe, object, embed and other tags with no legitimate use in plain-text rich fields
        $value = preg_replace('/<\s*(script|iframe|object|embed|base|form|meta|link|style|img|svg|body|math)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $value);
        $value = preg_replace('/<\s*(script|iframe|object|embed|base|form|meta|link|style|img|svg|body|math)[^>]*\/?>/i', '', $value);
        // Remove event handler attributes (on*=), quoted or unquoted
        $value = preg_replace('/\bon\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $value);
        // Remove javascript: URIs — tolerante a espacios/tabs/saltos de línea
        // intercalados DENTRO de la propia palabra ("java\tscript:"), no solo
        // entre la palabra y los dos puntos: los navegadores descartan tab/CR/LF
        // en cualquier punto de una URL antes de interpretar el esquema (spec
        // WHATWG URL), así que un "\s*" solo entre "javascript" y ":" no bastaba.
        $value = preg_replace('/j\s*a\s*v\s*a\s*s\s*c\s*r\s*i\s*p\s*t\s*:/i', '', $value);
        return trim($value);
    }
}
