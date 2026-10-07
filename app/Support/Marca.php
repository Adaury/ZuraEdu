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


    /**
     * Una URL de red social lista para un href, o null si no sirve. Sin esquema se asume https://; un esquema distinto de
     * http/https (javascript:, data:…) se rechaza, porque estos valores se guardan como texto libre y se escriben en el HTML.
     */
    public static function urlSegura(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || preg_match('/[\s<>"\x00-\x1f]/', $url)) {
            return null;
        }
        if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $url)) {
            return preg_match('#^https?://[^/]#i', $url) ? $url : null;
        }

        return 'https://' . ltrim($url, '/');
    }

    /** Redes sociales oficiales de ZuraEdu con URL válida: ['instagram' => url, 'facebook' => url, 'youtube' => url]. */
    public static function redes(): array
    {
        $redes = [];
        foreach (['instagram', 'facebook', 'youtube'] as $red) {
            if ($url = self::urlSegura(config("brand.redes.{$red}"))) {
                $redes[$red] = $url;
            }
        }

        return $redes;
    }
}
