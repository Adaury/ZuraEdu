<?php

namespace App\Services;

use App\Models\CarnetIdentidad;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CarnetQrService
{
    private const PREFIX       = 'ZR';
    private const TOKEN_TTL    = 300;   // segundos que dura el token dinámico en caché
    private const CACHE_PREFIX = 'carnet_qr_';

    // ── Generar número de carnet ──────────────────────────────────────────────

    public static function generarNumeroCarnet(int $tenantId): string
    {
        $year = now()->year;
        $key  = "carnet_seq_{$tenantId}_{$year}";

        $seq = Cache::increment($key);

        // Fallback a COUNT de BD si la caché se pierde
        if ($seq === 1) {
            $dbCount = CarnetIdentidad::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->whereYear('created_at', $year)
                ->count();
            if ($dbCount > 0) {
                Cache::put($key, $dbCount + 1, now()->addYear());
                $seq = $dbCount + 1;
            }
        }

        return sprintf('%s-%d-%05d', self::PREFIX, $year, $seq);
    }

    // ── Generar token QR permanente para el carnet ────────────────────────────

    public static function generarQrToken(int $tenantId, int $userId): string
    {
        return hash('sha256', "{$tenantId}:{$userId}:" . Str::random(32));
    }

    // ── Token dinámico (corta vida, para kiosco) ──────────────────────────────

    public static function generarTokenDinamico(CarnetIdentidad $carnet): string
    {
        $token = Str::random(12) . base64_encode($carnet->id . ':' . now()->timestamp);
        $cacheKey = self::CACHE_PREFIX . 'dynamic:' . $token;
        Cache::put($cacheKey, $carnet->id, self::TOKEN_TTL);
        return $token;
    }

    public static function resolverTokenDinamico(string $token): ?int
    {
        return Cache::get(self::CACHE_PREFIX . 'dynamic:' . $token);
    }

    // ── Validar QR token permanente ───────────────────────────────────────────

    public static function resolverQrPermanente(string $qrToken, int $tenantId): ?CarnetIdentidad
    {
        $cacheKey = self::CACHE_PREFIX . "perm:{$tenantId}:{$qrToken}";

        return Cache::remember($cacheKey, 60, function () use ($qrToken, $tenantId) {
            return CarnetIdentidad::withoutTenant()
                ->where('tenant_id', $tenantId)
                ->where('qr_token', $qrToken)
                ->where('estado', 'activo')
                ->with(['user', 'matricula.grupo.grado', 'matricula.grupo.seccion'])
                ->first();
        });
    }

    // ── Invalidar caché de un carnet al suspenderlo ───────────────────────────

    public static function invalidarCache(CarnetIdentidad $carnet): void
    {
        Cache::forget(self::CACHE_PREFIX . "perm:{$carnet->tenant_id}:{$carnet->qr_token}");
    }

    // ── Crear carnet para un usuario (o devolver el existente) ────────────────

    public static function obtenerOCrear(User $user, string $tipo = 'estudiante', ?int $matriculaId = null): CarnetIdentidad
    {
        $tenant = app()->bound('tenant') ? app('tenant') : null;
        $tenantId = $tenant?->id ?? 0;

        $existing = CarnetIdentidad::withoutTenant()
            ->where('tenant_id', $tenantId)
            ->where('user_id', $user->id)
            ->where('tipo', $tipo)
            ->first();

        if ($existing) {
            return $existing;
        }

        return CarnetIdentidad::withoutTenant()->create([
            'tenant_id'      => $tenantId,
            'tipo'           => $tipo,
            'user_id'        => $user->id,
            'matricula_id'   => $matriculaId,
            'numero_carnet'  => self::generarNumeroCarnet($tenantId),
            'qr_token'       => self::generarQrToken($tenantId, $user->id),
            'estado'         => 'activo',
            'vigencia_hasta' => now()->addYear()->endOfYear(),
        ]);
    }

    // ── Contenido del QR (URL para escanear) ─────────────────────────────────

    public static function qrContent(CarnetIdentidad $carnet): string
    {
        return url("/checkin/scan/{$carnet->qr_token}");
    }
}
