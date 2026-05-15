@extends('layouts.portal-estudiante')

@section('title', 'Mis Logros — Portal Estudiante')

@section('activeKey', 'logros')

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.estudiante.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Mis Logros</h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $estudiante->nombre_completo }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
        </div>
    </div>
</div>

{{-- Banner motivacional --}}
@php
    $obtenidos = collect($logros)->where('obtenido', true)->count();
    $total     = count($logros);
@endphp
<div style="background:linear-gradient(135deg,#1e3a5f 0%,#6366f1 100%);border-radius:14px;padding:1.2rem 1.5rem;color:#fff;margin-bottom:1.25rem;display:flex;align-items:center;gap:1rem;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-15px;top:-15px;width:110px;height:110px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;"></div>
    <div style="width:50px;height:50px;border-radius:50%;background:rgba(255,255,255,.18);border:2px solid rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0;">
        <i class="bi bi-trophy-fill"></i>
    </div>
    <div style="flex:1;">
        <div style="font-size:1rem;font-weight:800;margin-bottom:.15rem;">
            {{ $obtenidos }} de {{ $total }} logros obtenidos
        </div>
        <div style="font-size:.75rem;color:rgba(255,255,255,.75);">
            @if($obtenidos === $total)
                ¡Felicitaciones! Has obtenido todos los logros disponibles.
            @elseif($obtenidos === 0)
                Sigue esforzándote para desbloquear tus logros.
            @else
                Sigue así, estás en el camino correcto.
            @endif
        </div>
        {{-- Barra de progreso --}}
        <div style="margin-top:.65rem;background:rgba(255,255,255,.2);border-radius:99px;height:8px;overflow:hidden;">
            <div style="width:{{ $total > 0 ? round($obtenidos/$total*100) : 0 }}%;height:100%;background:#fff;border-radius:99px;transition:width .4s;"></div>
        </div>
    </div>
    @if($promedioGeneral !== null)
    <div style="background:rgba(255,255,255,.15);border-radius:10px;padding:.55rem .9rem;text-align:center;flex-shrink:0;">
        <div style="font-size:1.5rem;font-weight:900;line-height:1;">{{ $promedioGeneral }}</div>
        <div style="font-size:.62rem;color:rgba(255,255,255,.75);">Promedio</div>
    </div>
    @endif
</div>

{{-- Tarjetas de logros --}}
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(270px,1fr));gap:.85rem;margin-bottom:1rem;">

    @foreach($logros as $key => $logro)
    @php
        $obtenido = $logro['obtenido'];

        // Colores según logro
        $colorMap = [
            'asistencia_perfecta' => ['bg'=>'#dcfce7','border'=>'#86efac','icon'=>'#15803d','text'=>'#166534'],
            'estudiante_destacado'=> ['bg'=>'#fef9c3','border'=>'#fde68a','icon'=>'#b45309','text'=>'#92400e'],
            'mejora_continua'     => ['bg'=>'#dbeafe','border'=>'#93c5fd','icon'=>'#1d4ed8','text'=>'#1e3a8a'],
            'sin_faltas'          => ['bg'=>'#ede9fe','border'=>'#c4b5fd','icon'=>'#6d28d9','text'=>'#4c1d95'],
        ];
        $c = $colorMap[$key] ?? ['bg'=>'#f1f5f9','border'=>'#cbd5e1','icon'=>'#475569','text'=>'#334155'];

        $bgCard     = $obtenido ? $c['bg']     : '#f8fafc';
        $borderCard = $obtenido ? $c['border'] : '#e2e8f0';
        $iconColor  = $obtenido ? $c['icon']   : '#9ca3af';
        $textColor  = $obtenido ? $c['text']   : '#6b7280';
    @endphp
    <div style="background:{{ $bgCard }};border:1.5px solid {{ $borderCard }};border-radius:14px;padding:1.1rem 1.2rem;display:flex;gap:.85rem;align-items:flex-start;position:relative;overflow:hidden;transition:box-shadow .15s;"
         @if($obtenido) onmouseover="this.style.boxShadow='0 4px 18px rgba(0,0,0,.09)'" onmouseout="this.style.boxShadow='none'" @endif>

        {{-- Marca de obtenido --}}
        @if($obtenido)
        <div style="position:absolute;top:10px;right:10px;background:#15803d;color:#fff;border-radius:99px;font-size:.58rem;padding:.15rem .45rem;font-weight:700;display:flex;align-items:center;gap:.2rem;">
            <i class="bi bi-check-lg"></i> Obtenido
        </div>
        @else
        <div style="position:absolute;top:10px;right:10px;background:#e5e7eb;color:#6b7280;border-radius:99px;font-size:.58rem;padding:.15rem .45rem;font-weight:600;">
            Pendiente
        </div>
        @endif

        {{-- Ícono --}}
        <div style="width:46px;height:46px;border-radius:12px;background:{{ $obtenido ? 'rgba(255,255,255,.65)' : '#e5e7eb' }};display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1.5px solid {{ $borderCard }};">
            <i class="bi {{ $logro['icono'] }}" style="font-size:1.25rem;color:{{ $iconColor }};{{ !$obtenido ? 'opacity:.5;' : '' }}"></i>
        </div>

        {{-- Contenido --}}
        <div style="flex:1;min-width:0;padding-top:.1rem;">
            <div style="font-size:.87rem;font-weight:800;color:{{ $textColor }};margin-bottom:.2rem;padding-right:3rem;">
                {{ $logro['titulo'] }}
            </div>
            <div style="font-size:.74rem;color:{{ $obtenido ? $textColor : '#9ca3af' }};line-height:1.45;margin-bottom:.4rem;">
                {{ $logro['descripcion'] }}
            </div>
            @if($obtenido && $logro['valor'] !== null)
            <div style="display:inline-flex;align-items:center;gap:.3rem;background:rgba(255,255,255,.65);border-radius:6px;padding:.2rem .55rem;font-size:.72rem;font-weight:700;color:{{ $textColor }};border:1px solid {{ $borderCard }};">
                <i class="bi bi-graph-up"></i> {{ $logro['valor'] }}
            </div>
            @endif
        </div>
    </div>
    @endforeach

</div>

@endsection
