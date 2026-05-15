<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre_institucion', 'dominio', 'dominio_personalizado',
        'logo', 'tipo', 'estado', 'plan', 'plan_id',
        'email_contacto', 'telefono_contacto',
        'pais', 'ciudad', 'direccion',
        'color_primario', 'color_secundario',
        'fecha_registro', 'fecha_vencimiento',
        'max_estudiantes', 'max_docentes', 'max_usuarios',
        'metadatos', 'is_demo_temporal',
        'onboarding_completado', 'onboarding_paso',
        'stripe_customer_id',
    ];

    protected $casts = [
        'fecha_registro'        => 'date',
        'fecha_vencimiento'     => 'date',
        'metadatos'             => 'array',
        'is_demo_temporal'      => 'boolean',
        'onboarding_completado' => 'boolean',
    ];

    // ── Relaciones ────────────────────────────────────────────────────────

    public function planInfo(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function subscriptionActiva(): ?\App\Models\Subscription
    {
        return $this->subscriptions()->activas()->latest('fecha_inicio')->first();
    }

    public function modules(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
                    ->withPivot('activo', 'config')
                    ->withTimestamps();
    }

    public function features(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(TenantFeature::class);
    }

    public function users(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(User::class);
    }

    // ── Feature flags ─────────────────────────────────────────────────────

    public function can(string $feature): bool
    {
        return Cache::remember("tenant_{$this->id}_feature_{$feature}", 300, function () use ($feature) {
            return $this->features()
                ->where('feature', $feature)
                ->where('activo', true)
                ->exists();
        });
    }

    public function featureConfig(string $feature): ?array
    {
        $f = $this->features()->where('feature', $feature)->first();
        return $f?->config;
    }

    public function enableFeature(string $feature): void
    {
        $this->features()->updateOrCreate(
            ['feature' => $feature],
            ['activo' => true]
        );
        Cache::forget("tenant_{$this->id}_feature_{$feature}");
    }

    public function disableFeature(string $feature): void
    {
        $this->features()->where('feature', $feature)->update(['activo' => false]);
        Cache::forget("tenant_{$this->id}_feature_{$feature}");
    }

    // ── Resolvers ─────────────────────────────────────────────────────────

    public static function resolveFromHost(string $host): ?self
    {
        // Intentar por dominio personalizado primero
        $tenant = Cache::remember("tenant_host_{$host}", 300, function () use ($host) {
            return static::where('dominio_personalizado', $host)
                ->orWhere('dominio', static::extractSubdomain($host))
                ->where('estado', '!=', 'cancelado')
                ->first();
        });

        return $tenant;
    }

    private static function extractSubdomain(string $host): string
    {
        // colegio1.zuraedu.com → colegio1
        // localhost → localhost
        $parts = explode('.', $host);
        return count($parts) >= 3 ? $parts[0] : $host;
    }

    // ── Estado ───────────────────────────────────────────────────────────

    public function estaActivo(): bool
    {
        return $this->estado === 'activo' || $this->estado === 'prueba';
    }

    public function estaVencido(): bool
    {
        return $this->fecha_vencimiento && $this->fecha_vencimiento->isPast();
    }

    public function getLabelEstadoAttribute(): string
    {
        return match ($this->estado) {
            'activo'    => 'Activo',
            'prueba'    => 'En prueba',
            'suspendido'=> 'Suspendido',
            'cancelado' => 'Cancelado',
            default     => $this->estado,
        };
    }

    public function getLabelPlanAttribute(): string
    {
        return match ($this->plan) {
            'free'   => 'Free',
            'basico' => 'Básico',
            'pro'    => 'Pro',
            default  => ucfirst($this->plan ?? '—'),
        };
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? asset('storage/' . $this->logo) : null;
    }

    public function getUrlAttribute(): string
    {
        if ($this->dominio_personalizado) {
            return 'https://' . $this->dominio_personalizado;
        }
        $base = config('tenancy.base_domain', 'zuraedu.com');
        return 'https://' . $this->dominio . '.' . $base;
    }

    // ── Stats rápidas ────────────────────────────────────────────────────

    public function statsEstudiantes(): int
    {
        return \App\Models\Estudiante::where('tenant_id', $this->id)->count();
    }

    public function statsDocentes(): int
    {
        return \App\Models\Docente::where('tenant_id', $this->id)->count();
    }
}
