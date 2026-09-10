<?php

namespace App\Services;

use App\Models\PlantillaComunicacion;
use Illuminate\Support\Facades\Cache;

/**
 * Motor de renderizado de Plantillas de Comunicación. El sistema hardcodeado
 * actual (WhatsAppService, clases Mail) sigue siendo el FALLBACK CANÓNICO
 * permanente -- este servicio nunca reemplaza nada, solo personaliza cuando
 * el propio tenant lo pidió explícitamente. NO eliminar el texto hardcodeado
 * de los call sites en futuros refactors sin antes hacer un backfill
 * explícito de ese evento (ver docblock de la migración de
 * plantillas_comunicacion para la justificación completa de por qué hoy no
 * lo tiene).
 */
class PlantillaComunicacionService
{
    private const CACHE_TTL = 300;

    /**
     * In-memory store indexado por tenant_id -- NO un array plano. Réplica
     * exacta del bug ya documentado en app/Helpers/Setting.php:11-19: un
     * memo sin la clave de tenant arrastraría las plantillas del primer
     * tenant leído en un worker/consola a todos los tenants siguientes.
     *
     * @var array<int, array<string, array{asunto: ?string, cuerpo: ?string}>>
     */
    private static array $memo = [];

    /**
     * Canal de una sola pieza (WhatsApp). null = no hay personalización
     * activa -> el caller debe usar su texto hardcodeado actual.
     */
    public static function render(string $evento, string $canal, array $variables = []): ?string
    {
        $fila = static::plantillas()["{$evento}|{$canal}"] ?? null;

        if (! $fila || blank($fila['cuerpo'])) {
            return null;
        }

        return static::sustituir($fila['cuerpo'], $variables, $canal);
    }

    /**
     * Canal email: asunto y cuerpo son independientes -- un tenant puede
     * personalizar solo uno de los dos. null = ninguna fila activa.
     *
     * @return array{asunto: ?string, cuerpo: ?string}|null
     */
    public static function renderEmail(string $evento, array $variables = []): ?array
    {
        $fila = static::plantillas()["{$evento}|email"] ?? null;

        if (! $fila) {
            return null;
        }

        return [
            'asunto' => filled($fila['asunto']) ? static::sustituir($fila['asunto'], $variables, 'email') : null,
            'cuerpo' => filled($fila['cuerpo']) ? static::sustituir($fila['cuerpo'], $variables, 'email') : null,
        ];
    }

    /** "{{ x }}" / "{{x }}" -> "{{x}}". Se aplica al GUARDAR, no al renderizar. */
    public static function normalizar(string $texto): string
    {
        return (string) preg_replace('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', '{{$1}}', $texto);
    }

    /** Variables usadas en $texto que no pertenecen al catálogo de $evento -- para validar en el CRUD. */
    public static function variablesDesconocidas(string $texto, string $evento): array
    {
        preg_match_all('/\{\{\s*([A-Za-z0-9_]+)\s*\}\}/', $texto, $m);
        $usadas = array_unique($m[1] ?? []);
        $validas = PlantillaComunicacion::variablesDe($evento);

        return array_values(array_diff($usadas, $validas));
    }

    public static function olvidarCache(?int $tenantId): void
    {
        if ($tenantId === null) {
            return;
        }

        unset(static::$memo[$tenantId]);
        Cache::forget("plantillas_com_t{$tenantId}");
    }

    /**
     * @return array<string, array{asunto: ?string, cuerpo: ?string}>
     */
    private static function plantillas(): array
    {
        $tenant = tenant();
        if (! $tenant) {
            // Sin tenant bindeado (webhook de plataforma, comando de
            // consola sin LoopsPerTenant) -- nunca personalizar, siempre
            // hardcode. tenant_id() NO sirve aquí: cae a 1 como fallback.
            return [];
        }

        $tid = $tenant->id;

        if (isset(static::$memo[$tid])) {
            return static::$memo[$tid];
        }

        return static::$memo[$tid] = Cache::remember("plantillas_com_t{$tid}", self::CACHE_TTL, function () {
            return PlantillaComunicacion::activas()
                ->get(['evento', 'canal', 'asunto', 'cuerpo'])
                ->keyBy(fn ($p) => "{$p->evento}|{$p->canal}")
                ->map(fn ($p) => ['asunto' => $p->asunto, 'cuerpo' => $p->cuerpo])
                ->all();
        });
    }

    /**
     * strtr() -- NUNCA un bucle de str_replace(). strtr() sustituye en una
     * sola pasada y no re-examina el texto ya sustituido: si un VALOR de
     * datos (nombre de estudiante, concepto de pago) contuviera literalmente
     * "{{clave_temporal}}", un str_replace() en bucle lo expandiría en la
     * iteración siguiente y filtraría la contraseña temporal de otro
     * mensaje. Con strtr() eso es imposible por construcción. strtr()
     * además prioriza la clave más larga, así que {{monto}} y
     * {{monto_total}} no colisionan.
     */
    private static function sustituir(string $texto, array $variables, string $canal): string
    {
        $mapa = [];
        foreach ($variables as $clave => $valor) {
            $valor = (string) ($valor ?? '');
            // El cuerpo de email se inyecta con {!! !!} en el shell -- los
            // valores vienen de BD y no son de confianza. WhatsApp es texto
            // plano hacia una API HTTP, escapar ahí produciría "&amp;" visibles.
            $mapa['{{' . $clave . '}}'] = $canal === 'email' ? e($valor) : $valor;
        }

        $texto = strtr($texto, $mapa);

        // Placeholder que el centro escribió pero que el caller no provee
        // (o que ya no existe tras un cambio de catálogo): se elimina en vez
        // de filtrar "{{cosa}}" literal al destinatario final.
        $texto = (string) preg_replace('/\{\{\s*[A-Za-z0-9_]+\s*\}\}/', '', $texto);

        return trim((string) preg_replace('/[ \t]{2,}/', ' ', $texto));
    }
}
