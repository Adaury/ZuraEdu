<?php

namespace App\Support;

/**
 * Marca de la plataforma ZuraEdu: nombre, texto legal y logos. Un solo lugar para los pies de página de pantallas, PDF y correos.
 * Los valores salen de config/brand.php.
 */
class Marca
{
    public static function nombre(): string
    {
        return (string) config('brand.nombre', 'ZuraEdu');
    }

    public static function lema(): string
    {
        return (string) config('brand.lema', '');
    }

    /** «© 2026 ZuraEdu. Todos los derechos reservados.» (con rango de años a partir del siguiente). */
    public static function copyright(): string
    {
        $inicio = (int) config('brand.anio_inicio', 2026);
        $actual = (int) date('Y');
        $anios  = $actual > $inicio ? "{$inicio}–{$actual}" : (string) $inicio;

        return "© {$anios} " . self::nombre() . '. ' . config('brand.derechos', 'Todos los derechos reservados') . '.';
    }

    /** URL pública de un logo (svg o png) por su clave de config('brand.logos'). */
    public static function logoUrl(string $variante = 'color'): string
    {
        $ruta = config("brand.logos.{$variante}") ?? config('brand.logos.color');

        return asset($ruta);
    }

    /**
     * El logo PNG como data URI: para PDF (dompdf no depende de red ni de rutas) y lugares sin acceso público al archivo.
     * Se lee del disco una sola vez por proceso.
     */
    public static function logoDataUri(string $variante = 'png'): string
    {
        static $cache = [];

        if (! isset($cache[$variante])) {
            $archivo = public_path(config("brand.logos.{$variante}", config('brand.logos.png')));
            $cache[$variante] = is_file($archivo)
                ? 'data:' . (str_ends_with($archivo, '.svg') ? 'image/svg+xml' : 'image/png') . ';base64,' . base64_encode((string) file_get_contents($archivo))
                : '';
        }

        return $cache[$variante];
    }
}
