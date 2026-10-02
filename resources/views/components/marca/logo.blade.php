@props([
    // color | blanco (nombre completo) · icono | icono-blanco (solo la insignia)
    'variante' => 'color',
    'alto' => 32,
])
<img src="{{ \App\Support\Marca::logoUrl($variante) }}" alt="{{ \App\Support\Marca::nombre() }}" height="{{ $alto }}"
     {{ $attributes->merge(['style' => "height:{$alto}px;width:auto;display:inline-block;vertical-align:middle;", 'loading' => 'lazy', 'decoding' => 'async']) }}>
