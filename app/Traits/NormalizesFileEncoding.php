<?php

namespace App\Traits;

/**
 * Normaliza el contenido de archivos importados (CSV/TXT) a UTF-8 válido.
 *
 * mb_detect_encoding() con múltiples candidatos es poco confiable: si un archivo
 * es mayormente UTF-8 pero tiene un solo byte suelto inválido (común en exports
 * de Excel con comillas curvas o guiones de Windows-1252 mezclados), la detección
 * en modo estricto falla para TODO el archivo y termina reinterpretando bytes
 * UTF-8 ya válidos como Windows-1252, corrompiendo cada 'Ñ'/tilde del archivo.
 */
trait NormalizesFileEncoding
{
    protected function normalizeToUtf8(string $raw, array $fallbackEncodings = ['Windows-1252', 'ISO-8859-1']): string
    {
        if ($raw === '' || mb_check_encoding($raw, 'UTF-8')) {
            return $raw;
        }

        $noAscii = fn (string $s) => preg_replace('/[\x00-\x7F]/', '', $s);
        $nonAsciiOriginal = strlen($noAscii($raw));

        // Intenta limpiar bytes inválidos sueltos preservando el UTF-8 válido existente.
        $scrubbed = @mb_convert_encoding($raw, 'UTF-8', 'UTF-8');
        $nonAsciiScrubbed = $scrubbed !== false ? strlen($noAscii($scrubbed)) : 0;

        // Si la "limpieza" solo perdió una fracción menor de los bytes no-ASCII, el
        // archivo ya era mayormente UTF-8 válido -> usar la versión limpia.
        $fraccionPerdida = $nonAsciiOriginal > 0 ? 1 - ($nonAsciiScrubbed / $nonAsciiOriginal) : 0;
        if ($scrubbed !== false && $fraccionPerdida <= 0.3) {
            return $scrubbed;
        }

        // El archivo genuinamente no es UTF-8 (probablemente Windows-1252/ISO-8859-1/UTF-16 completo).
        $encoding = mb_detect_encoding($raw, $fallbackEncodings, true) ?: $fallbackEncodings[0];

        return mb_convert_encoding($raw, 'UTF-8', $encoding);
    }
}
