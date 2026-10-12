<?php

namespace App\Support;

use App\Helpers\Setting;

/**
 * Escala literal OPCIONAL (A+ … F). No es la escala oficial del MINERD (0-100): es una ayuda para
 * centros cuyos docentes ya trabajan con letras. Cada letra vale su nota mínima.
 * Se activa por centro con la clave 'escala_literal_activa' (Notificaciones → Umbrales).
 */
class EscalaLiteral
{
    public const DEFECTO = [
        'A+' => 100, 'A' => 95, 'B+' => 90, 'B' => 85,
        'C+' => 80,  'C' => 75, 'D+' => 70, 'D' => 65, 'F' => 50,
    ];

    public static function activa(): bool
    {
        return filter_var(Setting::get('escala_literal_activa', '0'), FILTER_VALIDATE_BOOLEAN);
    }

    /** Valor numérico de una letra ("b+", "A"), o null si no es una letra de la escala. */
    public static function aNumero(string $letra): ?float
    {
        $l = strtoupper(trim($letra));

        return array_key_exists($l, self::DEFECTO) ? (float) self::DEFECTO[$l] : null;
    }

    /** Letra de una nota 0-100: la mayor cuyo mínimo no supera la nota (por debajo de todas: F). */
    public static function aLetra(float $nota): string
    {
        foreach (self::DEFECTO as $letra => $minimo) {
            if ($nota >= $minimo) {
                return $letra;
            }
        }

        return 'F';
    }
}
