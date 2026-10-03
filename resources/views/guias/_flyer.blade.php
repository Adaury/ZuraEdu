{{--
    Hoja (A4) de la guía rápida de un rol. Diseño con TABLAS y colores sólidos para que se vea igual en pantalla, al imprimir y en PDF (dompdf no
    soporta flex/grid ni transparencias). Variables: $g (contenido de GuiasRol), $pdf (true en el PDF: el pie lo pone partials.marca.pie-pdf).
--}}
@php
    $pdf = $pdf ?? false;
    $color = $g['color'];
    $logo = \App\Support\Marca::logoDataUri('png-blanco');
    $mitad = (int) ceil(count($g['hacer']) / 2);
    $columnas = [array_slice($g['hacer'], 0, $mitad), array_slice($g['hacer'], $mitad)];
@endphp
<table class="fl" cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;font-family:'DejaVu Sans',Arial,sans-serif;color:#0f172a;background:#ffffff;">
    {{-- Encabezado --}}
    <tr><td style="background:{{ $color }};padding:26px 34px 24px;color:#ffffff;">
        <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><tr>
            <td style="vertical-align:middle;">@if($logo)<img src="{{ $logo }}" alt="ZuraEdu" style="height:30px;width:auto;">@endif</td>
            <td style="vertical-align:middle;text-align:right;font-size:10px;letter-spacing:2px;text-transform:uppercase;color:#e2e8f0;">Guía rápida</td>
        </tr></table>
        <div style="font-size:11px;letter-spacing:2px;text-transform:uppercase;margin-top:22px;color:#cbd5e1;">Para</div>
        <div style="font-size:30px;font-weight:bold;line-height:1.1;margin-top:3px;">{{ $g['nombre'] }}</div>
        <div style="font-size:12px;margin-top:6px;color:#e2e8f0;">{{ $g['perfiles'] }}</div>
        <div style="font-size:16px;margin-top:14px;font-style:italic;color:#ffffff;">«{{ $g['lema'] }}»</div>
    </td></tr>

    {{-- Qué puedes hacer --}}
    <tr><td style="padding:22px 34px 4px;">
        <div style="font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:{{ $color }};padding-bottom:8px;border-bottom:2px solid {{ $color }};">Lo que puedes hacer</div>
        <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin-top:10px;"><tr>
            @foreach($columnas as $i => $col)
            <td style="width:50%;vertical-align:top;{{ $i === 0 ? 'padding-right:14px;' : 'padding-left:14px;' }}">
                @foreach($col as $item)
                <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin-bottom:9px;"><tr>
                    <td style="width:20px;vertical-align:top;font-size:13px;font-weight:bold;color:{{ $color }};">&#10003;</td>
                    <td style="vertical-align:top;font-size:11.5px;line-height:1.45;color:#1e293b;">{{ $item }}</td>
                </tr></table>
                @endforeach
            </td>
            @endforeach
        </tr></table>
    </td></tr>

    {{-- Empieza en 3 pasos --}}
    <tr><td style="padding:14px 34px 4px;">
        <div style="font-size:13px;font-weight:bold;letter-spacing:1px;text-transform:uppercase;color:{{ $color }};padding-bottom:8px;border-bottom:2px solid {{ $color }};">Empieza en 3 pasos</div>
        <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:separate;border-spacing:8px 0;margin:10px -8px 0;"><tr>
            @foreach($g['pasos'] as $n => [$titulo, $texto])
            <td style="width:33.33%;vertical-align:top;background:#f1f5f9;padding:12px 12px 13px;border-radius:8px;">
                <div style="width:24px;height:19px;padding-top:5px;line-height:1;text-align:center;background:{{ $color }};color:#ffffff;font-weight:bold;font-size:12px;border-radius:12px;">{{ $n + 1 }}</div>
                <div style="font-size:12px;font-weight:bold;margin-top:7px;color:#0f172a;">{{ $titulo }}</div>
                <div style="font-size:10.5px;line-height:1.45;margin-top:3px;color:#475569;">{{ $texto }}</div>
            </td>
            @endforeach
        </tr></table>
    </td></tr>

    {{-- Atajos y consejos --}}
    <tr><td style="padding:18px 34px 6px;">
        <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;"><tr>
            <td style="width:42%;vertical-align:top;padding-right:14px;">
                <div style="background:#fffbeb;border:1px solid #fcd34d;border-radius:8px;padding:12px 13px;">
                    <div style="font-size:12px;font-weight:bold;color:#92400e;">Atajo: accesos rápidos</div>
                    <div style="font-size:10.5px;line-height:1.5;margin-top:4px;color:#78350f;">Pulsa el icono de cuadritos de la barra superior (o <strong>Alt + Q</strong>) y salta a lo que más usas, desde cualquier pantalla.</div>
                </div>
            </td>
            <td style="width:58%;vertical-align:top;padding-left:2px;">
                <div style="font-size:12px;font-weight:bold;color:{{ $color }};margin-bottom:5px;">Consejos</div>
                @foreach($g['consejos'] as $c)
                <table cellspacing="0" cellpadding="0" style="width:100%;border-collapse:collapse;margin-bottom:5px;"><tr>
                    <td style="width:14px;vertical-align:top;font-size:11px;color:{{ $color }};">&bull;</td>
                    <td style="vertical-align:top;font-size:10.5px;line-height:1.45;color:#334155;">{{ $c }}</td>
                </tr></table>
                @endforeach
            </td>
        </tr></table>
    </td></tr>

    @unless($pdf)
    {{-- Pie propio de la hoja (en el PDF lo pone partials.marca.pie-pdf, anclado al fondo de la página) --}}
    <tr><td style="padding:14px 34px 18px;border-top:1px solid #e2e8f0;font-size:10px;color:#64748b;text-align:center;">
        {{ \App\Support\Marca::copyright() }} &nbsp;·&nbsp; ¿Dudas? Abre el Centro de Ayuda desde tu panel.
    </td></tr>
    @endunless
</table>
