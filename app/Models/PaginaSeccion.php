<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PaginaSeccion extends Model
{
    use BelongsToTenant;

    protected $table = 'pagina_secciones';

    protected $fillable = [
        'tenant_id', 'tipo', 'orden', 'activo', 'contenido',
    ];

    protected $casts = [
        'activo'    => 'boolean',
        'contenido' => 'array',
        'orden'     => 'integer',
    ];

    /**
     * Datos ya resueltos desde otro modelo (Album, colección de Publicacion)
     * para los tipos "query-backed" (carrusel, noticias). NO es un atributo
     * Eloquent -- es una propiedad PHP declarada, así que nunca entra a
     * $attributes ni se persiste por accidente. La rellena el controlador
     * que arma la vista pública, siempre re-aplicando los scopes reales del
     * modelo de origen (Album::carruselDelSitio(), Publicacion::
     * visiblesPublico()) -- "contenido" solo guarda el id de referencia
     * (album_id) o parámetros de presentación (limite), nunca una copia.
     */
    public $datos = null;

    public const TIPOS = [
        'hero'     => 'Portada (Hero)',
        'carrusel' => 'Carrusel de Fotos',
        'about'    => 'Sobre la Institución',
        'stats'    => 'Estadísticas',
        'features' => 'Características',
        'noticias' => 'Noticias y Publicaciones',
        'contacto' => 'Contacto y Redes',
    ];

    public const ICONOS = [
        'hero'     => 'bi-display',
        'carrusel' => 'bi-images',
        'about'    => 'bi-building',
        'stats'    => 'bi-bar-chart',
        'features' => 'bi-stars',
        'noticias' => 'bi-newspaper',
        'contacto' => 'bi-geo-alt',
    ];

    public const CONTENIDO_INICIAL = [
        'hero'     => ['titulo' => '', 'subtitulo' => '', 'btn_texto' => '', 'btn_url' => '', 'btn2_texto' => '', 'btn2_url' => ''],
        'carrusel' => ['album_id' => null],
        'about'    => ['titulo' => '', 'texto' => ''],
        'stats'    => ['items' => []],
        'features' => ['titulo' => '', 'items' => []],
        'noticias' => ['titulo' => '', 'limite' => 6],
        'contacto' => ['titulo' => '', 'direccion' => '', 'telefono' => '', 'email' => '', 'facebook' => '', 'instagram' => '', 'twitter' => ''],
    ];

    public const LIMITE_NOTICIAS_MAX = 12;

    public function scopeOrdenadas(Builder $q): Builder
    {
        return $q->orderBy('orden')->orderBy('id');
    }

    public function scopeActivas(Builder $q): Builder
    {
        return $q->where('activo', true);
    }

    /** Filtra tipos desconocidos/obsoletos -- evita que @include("...{$tipo}") reviente por una vista faltante. */
    public function scopeTiposValidos(Builder $q): Builder
    {
        return $q->whereIn('tipo', array_keys(self::TIPOS));
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? ucfirst($this->tipo);
    }

    public function getIconoAttribute(): string
    {
        return self::ICONOS[$this->tipo] ?? 'bi-square';
    }

    public function dato(string $clave, $default = null)
    {
        return $this->contenido[$clave] ?? $default;
    }

    /** Corto resumen para la lista del admin (título si hay, si no un genérico). */
    public function resumenCorto(): string
    {
        return $this->dato('titulo') ?: ($this->dato('texto') ? \Illuminate\Support\Str::limit(strip_tags($this->dato('texto')), 60) : '');
    }

    /**
     * "Flag activo Y contenido real" -- porta la lógica hoy inline en
     * sitio.blade.php ($mostrar), pero por-instancia en vez de por-tipo.
     */
    public function tieneContenido(): bool
    {
        if (! $this->activo) {
            return false;
        }

        return match ($this->tipo) {
            'hero'     => true,
            'about'    => filled($this->dato('titulo')) || filled($this->dato('texto')),
            'stats'    => filled($this->dato('items')),
            'features' => filled($this->dato('items')),
            'carrusel' => $this->datos && $this->datos->fotos->count() >= Album::MIN_FOTOS_CARRUSEL,
            'noticias' => $this->datos && $this->datos->isNotEmpty(),
            'contacto' => filled($this->dato('direccion')) || filled($this->dato('telefono')) || filled($this->dato('email')),
            default    => false,
        };
    }
}
