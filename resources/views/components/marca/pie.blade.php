@props([
    // auto (por defecto): el texto hereda el color de la página (siempre contrasta con SU fondo) y el logo va sobre una pastilla blanca
    //                     que se ve bien sobre cualquier fondo, claro u oscuro.
    // claro:  texto gris sobre fondo claro · oscuro: texto claro sobre fondo oscuro (usa el logo blanco)
    'tono' => 'auto',
    'logo' => true,
])
{{--
    Pie de página de la plataforma, el MISMO en todas las pantallas. Estilos en línea a propósito: así funciona igual en el panel,
    los portales, el acceso, las páginas de error y las públicas sin depender del CSS de cada plantilla.
--}}
@php
    $color = match ($tono) {
        'oscuro' => 'color:rgba(255,255,255,.72);',
        'claro'  => 'color:#64748b;',
        default  => 'color:inherit;opacity:.72;',
    };
@endphp
<footer data-marca-pie="1" role="contentinfo"
        style="text-align:center;padding:14px 12px;margin-top:auto;font-size:.74rem;line-height:1.5;{{ $color }}">
    @if($logo)
        <a href="{{ url('/') }}" aria-label="{{ \App\Support\Marca::nombre() }}"
           style="display:inline-block;margin-bottom:6px;{{ $tono === 'auto' ? 'background:#fff;padding:4px 10px;border-radius:9px;box-shadow:0 1px 3px rgba(0,0,0,.12);' : 'opacity:.92;' }}">
            <x-marca.logo :variante="$tono === 'oscuro' ? 'blanco' : 'color'" :alto="20" />
        </a><br>
    @endif
    <span>{{ \App\Support\Marca::copyright() }}</span>
</footer>
