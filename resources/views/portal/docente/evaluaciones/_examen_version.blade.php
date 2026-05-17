{{--
    Partial: una versión del examen (A o B)
    Variables esperadas del contexto:
      $preguntas   – Collection de EvaPregunta ordenadas
      $secciones   – ['multiple'=>pts, 'verdadero_falso'=>pts, 'abierta'=>pts]
      $verLetra    – 'A' o 'B'
      $instNombre  – nombre institución
      $asignatura  – string
      $grupoNombre – string
      $logoUrl     – null|string
      $quiz        – EvaQuiz
      $asignacion  – Asignacion
      $letras      – ['A','B','C','D','E','F']
--}}

{{-- Cabecera institucional --}}
<div class="inst-header">
    <div class="inst-logo-cell">
        @if($logoUrl)
            <img src="{{ $logoUrl }}" alt="">
        @else
            <table style="width:44px;height:44px;"><tr><td class="logo-placeholder">{{ strtoupper(substr($instNombre,0,2)) }}</td></tr></table>
        @endif
    </div>
    <div class="inst-info-cell">
        <div class="inst-name">{{ $instNombre }}</div>
        <div class="inst-sub" style="margin-top:2px;font-weight:700;font-size:10px;color:#1e293b;">
            {{ strtoupper($quiz->titulo) }}
        </div>
        <div class="inst-sub">{{ $asignaturaNombre }} · {{ $grupoNombre }}</div>
    </div>
    <div class="version-cell">
        <div class="version-badge {{ $verLetra === 'A' ? 'version-a' : 'version-b' }}">VER. {{ $verLetra }}</div>
    </div>
</div>

{{-- Barra de datos del estudiante --}}
<div class="student-bar">
    <table>
        <tr>
            <td>Nombre y Apellido: <span class="field-line" style="min-width:160px;">&nbsp;</span></td>
            <td style="width:90px;">Fecha: <span class="field-line" style="min-width:60px;">&nbsp;</span></td>
            <td style="width:80px;">Nota: <span class="field-line" style="min-width:44px;">&nbsp;</span></td>
        </tr>
    </table>
</div>

{{-- Instrucciones --}}
@if($quiz->instrucciones)
<div class="instrucciones">
    <strong>Instrucciones:</strong> {{ $quiz->instrucciones }}
</div>
@endif

{{-- Puntaje total --}}
<div class="pts-total">
    Valor total: <strong>{{ $quiz->puntaje_total }} puntos</strong>
    @if($quiz->duracion_minutos) &nbsp;·&nbsp; Tiempo: {{ $quiz->duracion_minutos }} min @endif
</div>

@php
    // Agrupar preguntas por tipo para presentar en secciones
    $grupos = [
        'multiple'       => ['label' => 'I. Selección Múltiple', 'color' => '#4f46e5'],
        'verdadero_falso'=> ['label' => 'II. Verdadero o Falso', 'color' => '#0891b2'],
        'abierta'        => ['label' => 'III. Desarrollo',        'color' => '#7c3aed'],
    ];
    // Numeración global continua
    $numGlobal = 0;
    $pregsPorTipo = [
        'multiple'        => $preguntas->where('tipo','multiple')->values(),
        'verdadero_falso' => $preguntas->where('tipo','verdadero_falso')->values(),
        'abierta'         => $preguntas->where('tipo','abierta')->values(),
    ];
    // Calcular offset para la numeración por tipo
    $offsets = [
        'multiple'        => 0,
        'verdadero_falso' => $pregsPorTipo['multiple']->count(),
        'abierta'         => $pregsPorTipo['multiple']->count() + $pregsPorTipo['verdadero_falso']->count(),
    ];
@endphp

@foreach(['multiple','verdadero_falso','abierta'] as $tipo)
@if($pregsPorTipo[$tipo]->isNotEmpty())
@php
    $ptsSeccion = $secciones[$tipo];
    $offset     = $offsets[$tipo];
@endphp

<div class="seccion-title" style="background:{{ $grupos[$tipo]['color'] }};">
    {{ $grupos[$tipo]['label'] }}
    @if($ptsSeccion > 0)
    — {{ $ptsSeccion }} pts
    @endif
</div>

@foreach($pregsPorTipo[$tipo] as $pi => $p)
@php $num = $offset + $pi + 1; @endphp

<div class="pregunta">
    <div class="preg-header">
        <div class="preg-num">{{ $num }}.</div>
        <div class="preg-text">{{ $p->enunciado }}</div>
        <div class="preg-pts">({{ $p->puntos }} pt{{ $p->puntos != 1 ? 's' : '' }})</div>
    </div>

    @if($tipo === 'multiple')
    <div class="opciones-mc">
        @foreach($p->opciones ?? [] as $oi => $opcion)
        <div class="opcion-mc">
            <div class="opcion-circle">
                <svg class="circle-svg" width="9" height="9" viewBox="0 0 9 9">
                    <circle cx="4.5" cy="4.5" r="4" fill="none" stroke="#6b7280" stroke-width="1"/>
                </svg>
            </div>
            <div class="opcion-texto">
                <span class="opcion-letra">{{ $letras[$oi] ?? chr(65+$oi) }}.</span>{{ $opcion['texto'] }}
            </div>
        </div>
        @endforeach
    </div>

    @elseif($tipo === 'verdadero_falso')
    <div class="vf-row">
        <span class="vf-item">
            <svg width="9" height="9" viewBox="0 0 9 9" style="vertical-align:middle;margin-right:3px;">
                <circle cx="4.5" cy="4.5" r="4" fill="none" stroke="#6b7280" stroke-width="1"/>
            </svg>
            Verdadero
        </span>
        <span class="vf-item">
            <svg width="9" height="9" viewBox="0 0 9 9" style="vertical-align:middle;margin-right:3px;">
                <circle cx="4.5" cy="4.5" r="4" fill="none" stroke="#6b7280" stroke-width="1"/>
            </svg>
            Falso
        </span>
    </div>

    @else
    {{-- Abierta: líneas en blanco --}}
    @php $lineas = max(3, (int)ceil($p->puntos * 1.5)); @endphp
    <div class="blank-lines">
        @for($l = 0; $l < $lineas; $l++)
        <div class="blank-line"></div>
        @endfor
    </div>
    @endif
</div>
@endforeach
@endif
@endforeach

{{-- Pie del examen --}}
<div style="margin-top:14px;border-top:1px solid #e2e8f0;padding-top:5px;display:table;width:100%;font-size:7.5px;color:#94a3b8;">
    <span style="display:table-cell;">{{ $instNombre }}</span>
    <span style="display:table-cell;text-align:center;">{{ $asignaturaNombre }}</span>
    <span style="display:table-cell;text-align:right;">Versión {{ $verLetra }} — {{ $quiz->titulo }}</span>
</div>
