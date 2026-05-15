<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Response;

class PwaController extends Controller
{
    // ── manifest.json dinámico por tenant ─────────────────────────────────

    public function manifest()
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;

        $nombre       = $tenant?->nombre_institucion ?? config('app.name', 'ZuraEdu');
        $corto        = $this->shortName($nombre);
        $colorPrimario = $tenant?->color_primario ?? '#1d4ed8';
        $tenantId     = $tenant?->id ?? 0;

        $manifest = [
            'name'             => $nombre . ' — ZuraEdu',
            'short_name'       => $corto,
            'description'      => 'Sistema de Gestión Escolar — notas, asistencia, boletines y comunicados',
            'start_url'        => '/admin/dashboard',
            'scope'            => '/',
            'display'          => 'standalone',
            'background_color' => '#f1f5f9',
            'theme_color'      => $colorPrimario,
            'orientation'      => 'portrait-primary',
            'lang'             => 'es',
            'categories'       => ['education', 'productivity'],
            'icons'            => [
                [
                    'src'     => '/pwa/icon/192?tid=' . $tenantId,
                    'sizes'   => '192x192',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => '/pwa/icon/512?tid=' . $tenantId,
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src'     => '/pwa/icon/512?tid=' . $tenantId . '&maskable=1',
                    'sizes'   => '512x512',
                    'type'    => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name'      => 'Dashboard',
                    'short_name'=> 'Inicio',
                    'url'       => '/admin/dashboard',
                    'icons'     => [['src' => '/pwa/icon/96?tid=' . $tenantId, 'sizes' => '96x96', 'type' => 'image/png']],
                ],
                [
                    'name'      => 'Asistencia',
                    'short_name'=> 'Asist.',
                    'url'       => '/admin/asistencia',
                    'icons'     => [['src' => '/pwa/icon/96?tid=' . $tenantId, 'sizes' => '96x96', 'type' => 'image/png']],
                ],
                [
                    'name'      => 'Comunicados',
                    'short_name'=> 'Comuni.',
                    'url'       => '/admin/comunicados',
                    'icons'     => [['src' => '/pwa/icon/96?tid=' . $tenantId, 'sizes' => '96x96', 'type' => 'image/png']],
                ],
            ],
        ];

        return Response::make(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 200, [
            'Content-Type'  => 'application/manifest+json',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    // ── Icono dinámico generado con GD ────────────────────────────────────

    public function icon(Request $request, int $size)
    {
        $size = in_array($size, [96, 192, 512]) ? $size : 192;

        $tenantId  = (int) $request->query('tid', 0);
        $maskable  = (bool) $request->query('maskable', false);
        $cacheKey  = "pwa_icon_{$tenantId}_{$size}_" . ($maskable ? 'm' : 'a');

        $png = Cache::remember($cacheKey, 86400, function () use ($size, $tenantId, $maskable) {
            $tenant = $tenantId ? Tenant::find($tenantId) : null;
            $hex    = ltrim($tenant?->color_primario ?? '#1d4ed8', '#');
            return $this->generateIcon($size, $hex, $maskable);
        });

        return Response::make($png, 200, [
            'Content-Type'  => 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    // ── Página offline ────────────────────────────────────────────────────

    public function offline()
    {
        return view('pwa.offline');
    }

    // ── Generador de icono con GD ─────────────────────────────────────────

    private function generateIcon(int $size, string $hex, bool $maskable): string
    {
        // Convertir hex a RGB
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);

        $transparent = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $transparent);
        imagealphablending($img, true);

        $bg    = imagecolorallocate($img, $r, $g, $b);
        $white = imagecolorallocate($img, 255, 255, 255);

        $padding = $maskable ? 0 : (int) round($size * 0.08);

        // Fondo redondeado (o cuadrado completo para maskable)
        if ($maskable) {
            imagefilledrectangle($img, 0, 0, $size - 1, $size - 1, $bg);
        } else {
            $this->roundedRect($img, $padding, $padding, $size - $padding, $size - $padding, (int)round($size * 0.18), $bg);
        }

        // Mortero (graduation cap) en blanco
        $this->drawMortarboard($img, $size, $white, $padding);

        ob_start();
        imagepng($img);
        $png = ob_get_clean();
        imagedestroy($img);

        return $png;
    }

    private function drawMortarboard($img, int $size, $color, int $padding): void
    {
        $cx = $size / 2;
        $cy = $size / 2;
        $s  = ($size - $padding * 2) * 0.55; // escala del ícono dentro

        // Tablero (rombo superior)
        $topY    = (int) round($cy - $s * 0.32);
        $midY    = (int) round($cy);
        $halfW   = (int) round($s * 0.44);
        $diamond = [
            (int)round($cx),       $topY - (int)round($s * 0.12),
            (int)round($cx + $halfW), $midY - (int)round($s * 0.08),
            (int)round($cx),       $midY + (int)round($s * 0.04),
            (int)round($cx - $halfW), $midY - (int)round($s * 0.08),
        ];
        imagefilledpolygon($img, $diamond, $color);

        // Cuerpo semicircular (birrete)
        $capTop = $midY - (int)round($s * 0.06);
        $capBot = $midY + (int)round($s * 0.28);
        $capW   = (int)round($s * 0.32);
        imagefilledarc($img, (int)$cx, (int)round(($capTop + $capBot) / 2), $capW * 2, $capBot - $capTop, 0, 180, $color, IMG_ARC_PIE);

        // Borla (cordón derecho)
        $taselX = (int)round($cx + $halfW + $s * 0.06);
        $taselY = $midY - (int)round($s * 0.08);
        $taselB = $taselY + (int)round($s * 0.25);
        imageline($img, $taselX, $taselY, $taselX, $taselB, $color);
        imagefilledellipse($img, $taselX, $taselB, (int)round($s * 0.1), (int)round($s * 0.1), $color);
    }

    private function roundedRect($img, int $x1, int $y1, int $x2, int $y2, int $r, $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($img, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private function shortName(string $name): string
    {
        $words = explode(' ', $name);
        if (count($words) === 1) return substr($name, 0, 12);
        // Usar iniciales si el nombre es largo
        if (strlen($name) > 15) {
            return implode('', array_map(fn($w) => strtoupper($w[0]), array_slice($words, 0, 3)));
        }
        return $name;
    }
}
