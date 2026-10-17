<?php

namespace App\Support;

/**
 * Traduce el HTML ya generado (que está escrito en español) con el diccionario lang/{idioma}.json.
 *
 * Solo cambia textos que coinciden EXACTAMENTE con una clave del diccionario (texto entre etiquetas y los
 * atributos placeholder, title, aria-label y alt); lo demás —datos, scripts, estilos, áreas de texto— queda
 * igual. Así no hace falta tocar las vistas y ningún dato sale del servidor hacia un traductor externo.
 */
class Traductor
{
    /** @var array<string, array<string, string>> */
    private static array $diccionarios = [];

    /** @var array<string, array<string, string>> claves en minúsculas, para coincidir sin distinguir mayúsculas */
    private static array $indices = [];

    /**
     * Textos con una parte variable (números, nombres). $1… son los grupos de la expresión.
     * Estructura: [expresión en español => [idioma => plantilla]]
     */
    private const PATRONES = [
        '/^Hola, (.+)$/u' => ['en' => 'Hello, $1', 'fr' => 'Bonjour, $1', 'pt' => 'Olá, $1'],
        '/^(\d+) estudiantes activos$/u' => ['en' => '$1 active students', 'fr' => '$1 élèves actifs', 'pt' => '$1 alunos ativos'],
        '/^(\d+) estudiantes$/u' => ['en' => '$1 students', 'fr' => '$1 élèves', 'pt' => '$1 alunos'],
        '/^(\d+) est\.$/u' => ['en' => '$1 st.', 'fr' => '$1 él.', 'pt' => '$1 al.'],
        '/^(\d+) registros$/u' => ['en' => '$1 records', 'fr' => '$1 enregistrements', 'pt' => '$1 registros'],
        '/^(\d+) asignacion\(es\)$/u' => ['en' => '$1 assignment(s)', 'fr' => '$1 affectation(s)', 'pt' => '$1 atribuição(ões)'],
        '/^Mostrando (\d+)[–-](\d+) de (\d+) registros$/u' => [
            'en' => 'Showing $1–$2 of $3 records', 'fr' => 'Affichage de $1–$2 sur $3 enregistrements', 'pt' => 'Mostrando $1–$2 de $3 registros',
        ],
        '/^Bienvenido, (.+)$/u' => ['en' => 'Welcome, $1', 'fr' => 'Bienvenue, $1', 'pt' => 'Bem-vindo, $1'],
        '/^Año escolar: (.+)$/u' => ['en' => 'School year: $1', 'fr' => 'Année scolaire : $1', 'pt' => 'Ano letivo: $1'],
        '/^Actualizado: (.+)$/u' => ['en' => 'Updated: $1', 'fr' => 'Mis à jour : $1', 'pt' => 'Atualizado: $1'],
    ];

    public static function diccionario(string $idioma): array
    {
        if (! isset(self::$diccionarios[$idioma])) {
            $ruta = base_path("lang/{$idioma}.json");
            $datos = is_file($ruta) ? json_decode((string) file_get_contents($ruta), true) : null;
            self::$diccionarios[$idioma] = is_array($datos) ? $datos : [];
        }

        return self::$diccionarios[$idioma];
    }

    public static function olvidar(): void
    {
        self::$diccionarios = [];
        self::$indices = [];
    }

    /** Traduce un texto suelto (o null si no hay traducción). */
    public static function texto(string $espanol, string $idioma, bool $recursivo = false): ?string
    {
        $dic = self::diccionario($idioma);
        $clave = trim((string) preg_replace('/\s+/u', ' ', $espanol));
        if ($clave === '') {
            return null;
        }
        if (isset($dic[$clave])) {
            return $dic[$clave];
        }

        // Sin distinguir mayúsculas: las vistas escriben «Ver todos», «VER TODOS» o «Ver Todos» según el caso.
        // Si el original estaba todo en mayúsculas, la traducción también.
        $minuscula = mb_strtolower($clave);
        $indice = self::$indices[$idioma] ??= array_combine(array_map('mb_strtolower', array_keys($dic)), array_values($dic));
        if (isset($indice[$minuscula])) {
            $traduccion = $indice[$minuscula];

            return $clave === mb_strtoupper($clave) && $clave !== $minuscula ? mb_strtoupper($traduccion) : $traduccion;
        }
        foreach (self::PATRONES as $patron => $plantillas) {
            if (isset($plantillas[$idioma]) && preg_match($patron, $clave)) {
                return preg_replace($patron, $plantillas[$idioma], $clave);
            }
        }

        // Adornos delante del texto (emojis, viñetas): «📝 Exámenes» → «📝 Exams»
        if (! $recursivo && preg_match('/^([^\p{L}\p{N}¿¡]+)(.+)$/u', $clave, $m)) {
            $interno = self::texto($m[2], $idioma, true);

            return $interno === null ? null : $m[1] . $interno;
        }

        // Títulos compuestos: «Mis Mensajes — ZuraEdu», «Portal Docente — Ana Pérez — ZuraEdu». Se traduce
        // cada parte que esté en el diccionario y el resto (nombres, marca) queda tal cual.
        if (! $recursivo && str_contains($clave, ' — ')) {
            $partes = explode(' — ', $clave);
            $alguna = false;
            foreach ($partes as $i => $parte) {
                $t = self::texto($parte, $idioma, true);
                if ($t !== null) {
                    $partes[$i] = $t;
                    $alguna = true;
                }
            }

            return $alguna ? implode(' — ', $partes) : null;
        }

        return null;
    }

    public static function html(string $html, string $idioma): string
    {
        if ($idioma === 'es' || $html === '' || self::diccionario($idioma) === []) {
            return $html;
        }

        // 1) Texto entre etiquetas. La primera alternativa consume enteros los bloques que no se tocan.
        $html = preg_replace_callback(
            '~<(script|style|textarea|pre|code)\b[^>]*>.*?</\1>|(?<=>)([^<>]+)(?=<)~is',
            function (array $m) use ($idioma) {
                if (! isset($m[2]) || $m[2] === '') {
                    return $m[0];
                }
                $traducido = self::texto($m[2], $idioma);
                if ($traducido === null) {
                    return $m[0];
                }
                preg_match('/^\s*/u', $m[2], $ini);
                preg_match('/\s*$/u', $m[2], $fin);

                return $ini[0] . $traducido . $fin[0];
            },
            $html
        ) ?? $html;

        // 2) Atributos visibles
        $html = preg_replace_callback(
            '~\b(placeholder|title|aria-label|alt)="([^"]*)"~u',
            function (array $m) use ($idioma) {
                $traducido = self::texto(html_entity_decode($m[2], ENT_QUOTES | ENT_HTML5, 'UTF-8'), $idioma);

                return $traducido === null ? $m[0] : $m[1] . '="' . htmlspecialchars($traducido, ENT_QUOTES | ENT_HTML5, 'UTF-8', false) . '"';
            },
            $html
        ) ?? $html;

        // 3) <html lang="es">
        return preg_replace('~<html([^>]*?)\blang="[^"]*"~i', '<html$1lang="' . $idioma . '"', $html, 1) ?? $html;
    }
}
