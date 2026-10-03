<?php

namespace App\Support;

use App\Http\Middleware\CheckTenantFeature;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Oculta del menú lateral los enlaces que el usuario NO puede abrir.
 *
 * El menú es un bloque grande de HTML con ramas por rol; mantener a mano en cada enlace el mismo permiso que ya tiene su ruta se
 * desincroniza (el recorrido de menús encontró 4 enlaces con 403 al Director, 6 de 15 al Personal Administrativo, etc.). Aquí la RUTA es
 * la fuente de verdad: por cada enlace se resuelve la ruta real y se evalúan sus middleware `can:`, `role:`/`permission:` y `tenant.feature:`
 * con el usuario actual. Si falla alguno, el enlace desaparece; los grupos que se quedan sin enlaces también.
 *
 * Solo oculta: ante cualquier duda (ruta desconocida, permiso con modelo, error) el enlace se deja.
 */
class MenuFiltro
{
    /**
     * Estado de la petición (memo de decisiones y «módulos ya precargados»). Vive en el contenedor y no en propiedades estáticas: el
     * contenedor se recrea en cada petición/prueba, y un estado estático pasaría de un usuario o una prueba a la siguiente.
     */
    private static function estado(): \stdClass
    {
        if (! app()->bound('menu_filtro.estado')) {
            app()->instance('menu_filtro.estado', (object) ['memo' => [], 'preparado' => false]);
        }

        return app('menu_filtro.estado');
    }

    public static function filtrar(string $html, ?User $usuario = null): string
    {
        $usuario ??= auth()->user();
        if (! $usuario || trim($html) === '') {
            return $html;
        }

        try {
            return self::procesar($html, $usuario);
        } catch (\Throwable $e) {
            report($e);

            return $html;   // el filtro nunca debe romper la página
        }
    }

    public static function olvidar(): void
    {
        app()->forgetInstance('menu_filtro.estado');
    }

    private static function procesar(string $html, User $usuario): string
    {
        $anterior = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML('<?xml encoding="UTF-8"><body><div id="__menu">' . $html . '</div></body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($anterior);

        $xp = new \DOMXPath($dom);
        $raiz = $xp->query('//div[@id="__menu"]')->item(0);
        if (! $raiz) {
            return $html;
        }

        // 1) Enlaces sin permiso: se quita su <li>
        foreach (iterator_to_array($xp->query('.//a[@href]', $raiz)) as $a) {
            $href = trim($a->getAttribute('href'));
            if ($href === '' || $href[0] === '#' || str_starts_with($href, 'javascript:') || self::permite($href, $usuario)) {
                continue;
            }
            $li = $a;
            while ($li && $li->nodeName !== 'li') {
                $li = $li->parentNode;
            }
            ($li && $li->parentNode) ? $li->parentNode->removeChild($li) : null;
        }

        // 2) Submenús vacíos: se quita la lista y el <li> que solo era su cabecera (enlace «#»)
        do {
            $quitado = false;
            foreach (iterator_to_array($xp->query('.//ul', $raiz)) as $ul) {
                if (! $ul->parentNode || $xp->query('./li', $ul)->length > 0) {
                    continue;
                }
                $padre = $ul->parentNode;
                $titulo = self::elementoAnterior($ul);
                $padre->removeChild($ul);
                $quitado = true;
                if ($titulo && $titulo->nodeName === 'div' && str_contains(' ' . $titulo->getAttribute('class') . ' ', ' nav-section-title ')) {
                    $padre->removeChild($titulo);   // título de sección sin enlaces
                }
                if ($padre->nodeName === 'li' && $xp->query('./ul', $padre)->length === 0 && $xp->query('./a[starts-with(@href, "http")]', $padre)->length === 0 && $padre->parentNode) {
                    $padre->parentNode->removeChild($padre);   // cabecera de un submenú que quedó vacío
                }
            }
        } while ($quitado);

        $salida = '';
        foreach ($raiz->childNodes as $hijo) {
            $salida .= $dom->saveHTML($hijo);
        }

        return $salida;
    }

    private static function elementoAnterior(\DOMNode $nodo): ?\DOMNode
    {
        for ($p = $nodo->previousSibling; $p; $p = $p->previousSibling) {
            if ($p->nodeType === XML_ELEMENT_NODE) {
                return $p;
            }
        }

        return null;
    }

    /** ¿Puede este usuario abrir la URL? (según los middleware de su ruta) */
    public static function permite(string $url, User $usuario): bool
    {
        $ruta = parse_url($url, PHP_URL_PATH) ?: '/';
        $clave = $usuario->id . '|' . $ruta;

        $estado = self::estado();
        if (isset($estado->memo[$clave])) {
            return $estado->memo[$clave];
        }

        if (! $estado->preparado) {
            $estado->preparado = true;
            CheckTenantFeature::precargar();   // todos los módulos del plan en 2 consultas, no una por enlace
        }

        // Decisión cacheada 2 min por colegio, usuario, roles y ruta: en uso normal el menú no cuesta ninguna consulta
        $huella = md5(implode(',', $usuario->getRoleNames()->sort()->all()));
        $llave = 'menu_permite:' . (tenant_id() ?? 0) . ':' . $usuario->id . ':' . $huella . ':' . md5($ruta);

        return $estado->memo[$clave] = (bool) \Illuminate\Support\Facades\Cache::remember($llave, 120, fn () => self::evaluar($url, $usuario));
    }

    private static function evaluar(string $url, User $usuario): bool
    {
        try {
            $host = parse_url($url, PHP_URL_HOST);
            if ($host && $host !== request()->getHost()) {
                return true;   // enlace externo
            }
            $ruta = app('router')->getRoutes()->match(Request::create($url, 'GET'));
        } catch (\Throwable) {
            return true;   // ruta desconocida: no se oculta
        }

        $gate = Gate::forUser($usuario);

        foreach ($ruta->gatherMiddleware() as $m) {
            if (! is_string($m)) {
                continue;
            }

            try {
                if (str_starts_with($m, 'can:')) {
                    $partes = explode(',', substr($m, 4));
                    if (count($partes) === 1 && ! $gate->allows($partes[0])) {
                        return false;
                    }
                } elseif (preg_match('/^role:(.+)$/', $m, $x)) {
                    if (! $usuario->hasAnyRole(preg_split('/[|,]/', $x[1]))) {
                        return false;
                    }
                } elseif (preg_match('/^permission:(.+)$/', $m, $x)) {
                    if (! $usuario->hasAnyPermission(preg_split('/[|,]/', $x[1]))) {
                        return false;
                    }
                } elseif (str_starts_with($m, 'tenant.feature:')) {
                    if (! CheckTenantFeature::disponible(substr($m, strlen('tenant.feature:')))) {
                        return false;
                    }
                }
            } catch (\Throwable) {
                // un permiso que no se puede evaluar no oculta el enlace
            }
        }

        return true;
    }
}
