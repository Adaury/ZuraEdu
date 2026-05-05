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
        'notas_medicas', 'trial_mensaje',
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
        // Remove script, iframe, object, embed tags — keep everything else
        $value = preg_replace('/<\s*(script|iframe|object|embed|base|form|meta|link|style)[^>]*>.*?<\s*\/\s*\1\s*>/is', '', $value);
        $value = preg_replace('/<\s*(script|iframe|object|embed|base|form|meta|link|style)[^>]*\/?>/i', '', $value);
        // Remove javascript: and data: URIs in attributes
        $value = preg_replace('/\bon\w+\s*=\s*["\'][^"\']*["\']/', '', $value);
        $value = preg_replace('/javascript\s*:/i', '', $value);
        return trim($value);
    }
}
