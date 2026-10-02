{{--
    Pie de marca para PDF e impresiones (dompdf).
    POSICIONADO AL FONDO de la última página (position:absolute), NO en el flujo: así nunca cambia la paginación. Probado con dompdf real:
    un documento que cabe justo en una página pasaba a dos páginas si el pie iba en el flujo, y con este pie sigue siendo una.
    No usar en documentos de diseño a página completa o formato físico (carnets, diplomas, certificados): ahí el pie no corresponde.
--}}
<div style="position:absolute;bottom:0;left:0;right:0;padding-top:4px;border-top:0.5pt solid #cbd5e1;text-align:center;font-family:DejaVu Sans, Arial, sans-serif;font-size:7pt;color:#64748b;">
    @if(($logoUri = \App\Support\Marca::logoDataUri('png')) !== '')
        <img src="{{ $logoUri }}" alt="{{ \App\Support\Marca::nombre() }}" style="height:11px;vertical-align:middle;margin-right:5px;">
    @endif
    <span style="vertical-align:middle;">{{ \App\Support\Marca::copyright() }}</span>
</div>
