<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Hallazgo Alto de la auditoría de ZuraAI (ronda de módulos grandes del
 * 2026-09-03): 7 de 8 endpoints de chat con IA no tenían rate-limiting,
 * compartiendo una sola API key de Gemini global entre todos los
 * tenants — cualquier usuario autenticado podía generar llamadas
 * ilimitadas, costo sin techo para la plataforma. Se agregó
 * throttle:30,1 (la misma convención ya usada en los 2 endpoints que sí
 * lo tenían) a los 7 restantes.
 *
 * Se prueba a nivel de definición de ruta, no vía HTTP: el controlador
 * llama a Gemini con un \GuzzleHttp\Client crudo dentro de un
 * response()->stream(), no interceptable con Http::fake() — probar el
 * middleware directamente es más preciso y no depende de mockear una
 * librería HTTP de terceros ni de tener GEMINI_API_KEY configurada.
 */
class AsistenteIaThrottleTest extends TestCase
{
    public static function rutasIaProvider(): array
    {
        return [
            'estudiante: asistente.chat'       => ['portal.estudiante.asistente.chat'],
            'padre: asistente.chat'            => ['portal.padre.asistente.chat'],
            'docente: asistente.chat'          => ['portal.docente.asistente.chat'],
            'admin: asistente.chat'            => ['admin.asistente.chat'],
            'docente: planificacion.ia.ra'        => ['portal.docente.planificacion.ia.ra'],
            'docente: planificacion.ia.actividad' => ['portal.docente.planificacion.ia.actividad'],
            'docente: planificacion.ia.mejorar'   => ['portal.docente.planificacion.ia.mejorar'],
        ];
    }

    #[DataProvider('rutasIaProvider')]
    public function test_la_ruta_tiene_throttle_30_1(string $nombreRuta): void
    {
        $route = Route::getRoutes()->getByName($nombreRuta);

        $this->assertNotNull($route, "La ruta '{$nombreRuta}' no existe.");
        $this->assertContains(
            'throttle:30,1',
            $route->middleware(),
            "La ruta '{$nombreRuta}' debe tener throttle:30,1 para no permitir llamadas ilimitadas a la API de Gemini."
        );
    }
}
