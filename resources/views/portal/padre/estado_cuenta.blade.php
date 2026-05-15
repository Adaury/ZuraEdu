@extends('layouts.portal')
@section('page-title', 'Estado de Cuenta — ' . ($estudiante->nombre_completo ?? ''))
@section('portal-name', 'Portal del Representante')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'estado-cuenta', 'estudiante' => $estudiante])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.hijo', $estudiante) }}" class="prt-nav-item">
        <i class="bi bi-person-fill"></i>Resumen
    </a>
    <a href="{{ route('portal.padre.hijo.estado-cuenta', $estudiante) }}" class="prt-nav-item active">
        <i class="bi bi-receipt"></i>Pagos
    </a>
@endsection

@section('content')

@php
    $totalGeneral = $totales['pagado'] + $totales['pendiente'] + $totales['vencido'];
    $porcPagado   = $totalGeneral > 0 ? round(($totales['pagado'] / $totalGeneral) * 100) : 0;
    $proximoPago  = $matricula->pagos->whereIn('estado', ['pendiente','vencido'])->sortBy('fecha_vencimiento')->first();
    $diasAlProximo = $proximoPago ? (int) now()->diffInDays($proximoPago->fecha_vencimiento, false) : null;
    $cardnetActivo = \App\Services\CardNetService::isConfigured();
@endphp

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.padre.hijo', $estudiante) }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Estado de Cuenta</h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $estudiante->nombre_completo }}
            @if($matricula->grupo) · {{ $matricula->grupo->nombre_completo }} @endif
            @if($sy) · {{ $sy->nombre }} @endif
        </div>
    </div>
    <a href="{{ route('portal.padre.hijo.estado-cuenta.pdf', $estudiante) }}" target="_blank"
       style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;">
        <i class="bi bi-file-earmark-pdf"></i>Descargar PDF
    </a>
</div>

{{-- Hero gradiente --}}
<div style="background:linear-gradient(135deg,#065f46 0%,#10b981 100%);border-radius:14px;padding:1.25rem 1.5rem;color:#fff;margin-bottom:1rem;position:relative;overflow:hidden;">
    <div style="position:absolute;right:-20px;top:-20px;width:120px;height:120px;background:rgba(255,255,255,.06);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <div style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.7);margin-bottom:.3rem;">Estado de Cuenta</div>
        <div style="font-size:.95rem;font-weight:800;">{{ $estudiante->nombre_completo }}</div>
        @if($matricula->grupo)
        <div style="font-size:.78rem;color:rgba(255,255,255,.75);">{{ $matricula->grupo->nombre_completo }}</div>
        @endif
    </div>
</div>

{{-- Chips resumen --}}
<div style="display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1.25rem;">
    @foreach([
        ['Pagado',    $totales['pagado'],    '#d1fae5','#065f46','bi-check-circle-fill'],
        ['Pendiente', $totales['pendiente'], '#fef9c3','#92400e','bi-hourglass-split'],
        ['Vencido',   $totales['vencido'],   '#fee2e2','#991b1b','bi-exclamation-triangle-fill'],
    ] as [$lbl, $val, $bg, $col, $ico])
    <div style="background:#fff;border-radius:12px;padding:.85rem .9rem;box-shadow:0 2px 10px rgba(15,23,42,.06);display:flex;align-items:center;gap:.65rem;">
        <div style="width:38px;height:38px;border-radius:9px;background:{{ $bg }};display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi {{ $ico }}" style="color:{{ $col }};font-size:1rem;"></i>
        </div>
        <div>
            <div style="font-size:.65rem;font-weight:600;text-transform:uppercase;color:#6b7280;letter-spacing:.04em;">{{ $lbl }}</div>
            <div style="font-size:.88rem;font-weight:800;color:{{ $col }};">{{ $mon }} {{ number_format($val, 2) }}</div>
        </div>
    </div>
    @endforeach
</div>

{{-- Barra de progreso --}}
@if($totalGeneral > 0)
<div style="background:#fff;border-radius:12px;padding:1rem 1.25rem;box-shadow:0 2px 10px rgba(15,23,42,.06);margin-bottom:1.25rem;">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.5rem;">
        <span style="font-size:.82rem;font-weight:700;color:#374151;">Progreso del año escolar</span>
        <span style="font-size:.95rem;font-weight:800;color:#065f46;">{{ $porcPagado }}%</span>
    </div>
    <div style="height:10px;background:#e5e7eb;border-radius:99px;overflow:hidden;">
        <div style="height:100%;width:{{ $porcPagado }}%;background:linear-gradient(90deg,#10b981,#059669);border-radius:99px;"></div>
    </div>
    <div style="display:flex;justify-content:space-between;margin-top:.35rem;font-size:.7rem;color:#6b7280;">
        <span>{{ $mon }} {{ number_format($totales['pagado'], 2) }} pagado</span>
        <span>Total: {{ $mon }} {{ number_format($totalGeneral, 2) }}</span>
    </div>
</div>
@endif

{{-- Próximo vencimiento --}}
@if($proximoPago)
@php
    $esVencido = $proximoPago->estado === 'vencido';
    $bgAlert   = $esVencido ? '#fef2f2' : '#eff6ff';
    $bdAlert   = $esVencido ? '#fca5a5' : '#bfdbfe';
    $colAlert  = $esVencido ? '#991b1b' : '#1d4ed8';
    $icoAlert  = $esVencido ? 'bi-exclamation-triangle-fill' : 'bi-calendar-event-fill';
    $txtAlert  = $esVencido
        ? '¡Pago vencido hace ' . abs($diasAlProximo) . ' día(s)!'
        : ($diasAlProximo <= 0 ? '¡Vence hoy!' : 'Vence en ' . $diasAlProximo . ' día(s)');
@endphp
<div style="background:{{ $bgAlert }};border:1px solid {{ $bdAlert }};border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:.9rem;flex-wrap:wrap;">
    <i class="bi {{ $icoAlert }}" style="color:{{ $colAlert }};font-size:1.3rem;flex-shrink:0;"></i>
    <div style="flex:1;">
        <div style="font-weight:700;color:{{ $colAlert }};font-size:.87rem;">{{ $txtAlert }}</div>
        <div style="font-size:.79rem;color:#4b5563;margin-top:.12rem;">
            <strong>{{ $proximoPago->concepto }}</strong> — {{ $mon }} {{ number_format($proximoPago->monto, 2) }}
            · Vence: {{ $proximoPago->fecha_vencimiento->format('d/m/Y') }}
        </div>
    </div>
    @if($cardnetActivo && in_array($proximoPago->estado, ['pendiente','vencido']))
    <form method="POST" action="{{ route('portal.padre.hijo.pagos.pagar-online', [$estudiante, $proximoPago]) }}">
        @csrf
        <button type="submit"
            style="background:{{ $esVencido ? '#dc2626' : '#1d4ed8' }};color:#fff;border:none;border-radius:8px;font-size:.78rem;padding:.4rem 1rem;font-weight:700;cursor:pointer;white-space:nowrap;">
            <i class="bi bi-credit-card-2-front me-1"></i>Pagar ahora
        </button>
    </form>
    @endif
</div>
@endif

{{-- Alerta vencidos --}}
@php $vencidos = $matricula->pagos->where('estado','vencido'); @endphp
@if($vencidos->count() > 0)
<div style="background:#fef2f2;border:1px solid #fca5a5;border-radius:12px;padding:.85rem 1.1rem;margin-bottom:1.25rem;display:flex;align-items:flex-start;gap:.75rem;">
    <i class="bi bi-exclamation-triangle-fill" style="color:#dc2626;font-size:1.1rem;flex-shrink:0;margin-top:.1rem;"></i>
    <div>
        <div style="font-weight:700;color:#991b1b;font-size:.86rem;">{{ $vencidos->count() }} pago(s) vencido(s)</div>
        <div style="font-size:.8rem;color:#7f1d1d;">Comuníquese con la administración del centro para regularizar la situación.</div>
    </div>
</div>
@endif

{{-- Tabla de pagos --}}
<div style="background:#fff;border-radius:14px;box-shadow:0 2px 10px rgba(15,23,42,.06);overflow:hidden;">
    <div style="padding:.9rem 1.25rem;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem;">
        <i class="bi bi-list-ul" style="color:#10b981;font-size:1rem;"></i>
        <h3 style="font-size:.9rem;font-weight:700;margin:0;color:#1e293b;">
            Historial de Pagos
            <span style="font-weight:400;color:#94a3b8;font-size:.8rem;">({{ $matricula->pagos->count() }} registros)</span>
        </h3>
    </div>

    @if($matricula->pagos->isEmpty())
    <div style="text-align:center;padding:3rem 1rem;color:#94a3b8;">
        <i class="bi bi-receipt" style="font-size:2.5rem;display:block;margin-bottom:.5rem;color:#cbd5e1;"></i>
        <div>Sin registros de pagos para este año escolar.</div>
    </div>
    @else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.83rem;">
            <thead>
                <tr style="background:#f8fafc;">
                    <th style="padding:.7rem 1rem;text-align:left;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">Concepto</th>
                    <th style="padding:.7rem;text-align:right;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">Monto</th>
                    <th style="padding:.7rem;text-align:center;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">Vencimiento</th>
                    <th style="padding:.7rem;text-align:center;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">F. Pago</th>
                    <th style="padding:.7rem;text-align:center;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">Estado</th>
                    <th style="padding:.7rem;text-align:center;font-size:.68rem;font-weight:700;color:#6b7280;text-transform:uppercase;border-bottom:1px solid #f1f5f9;">Acción</th>
                </tr>
            </thead>
            <tbody>
            @foreach($matricula->pagos as $pago)
            @php
                $esPagado    = $pago->estado === 'pagado';
                $esVenc      = $pago->estado === 'vencido';
                $esPend      = $pago->estado === 'pendiente';
                $trBg        = $esVenc ? 'background:#fff5f5;' : '';
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;{{ $trBg }}">
                <td style="padding:.7rem 1rem;">
                    <div style="font-weight:600;color:#1e293b;">{{ $pago->concepto }}</div>
                    @if($pago->notas)
                    <div style="font-size:.72rem;color:#94a3b8;font-style:italic;">{{ $pago->notas }}</div>
                    @endif
                </td>
                <td style="padding:.7rem;text-align:right;font-weight:700;color:#1e293b;white-space:nowrap;">
                    {{ $mon }} {{ number_format($pago->monto, 2) }}
                </td>
                <td style="padding:.7rem;text-align:center;color:#64748b;white-space:nowrap;">
                    {{ $pago->fecha_vencimiento?->format('d/m/Y') }}
                </td>
                <td style="padding:.7rem;text-align:center;color:#64748b;white-space:nowrap;">
                    {{ $pago->fecha_pago ? $pago->fecha_pago->format('d/m/Y') : '—' }}
                </td>
                <td style="padding:.7rem;text-align:center;">
                    @if($esPagado)
                    <span style="background:#d1fae5;color:#065f46;border-radius:99px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-check-circle-fill"></i>Pagado
                    </span>
                    @elseif($esVenc)
                    <span style="background:#fee2e2;color:#dc2626;border-radius:99px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-exclamation-triangle-fill"></i>Vencido
                    </span>
                    @elseif($esPend)
                    <span style="background:#fef9c3;color:#92400e;border-radius:99px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;display:inline-flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-clock-fill"></i>Pendiente
                    </span>
                    @else
                    <span style="background:#f1f5f9;color:#64748b;border-radius:99px;padding:.2rem .65rem;font-size:.7rem;font-weight:700;">{{ ucfirst($pago->estado) }}</span>
                    @endif
                </td>
                <td style="padding:.7rem;text-align:center;">
                    @if($esPagado && $pago->fecha_pago)
                    <span style="font-size:.7rem;color:#64748b;">
                        <i class="bi bi-check2"></i> {{ ucfirst($pago->metodo_pago ?? 'confirmado') }}
                    </span>
                    @elseif(($esPend || $esVenc) && $cardnetActivo)
                    <form method="POST" action="{{ route('portal.padre.hijo.pagos.pagar-online', [$estudiante, $pago]) }}" style="margin:0;">
                        @csrf
                        <button type="submit"
                            style="background:{{ $esVenc ? '#dc2626' : '#2563eb' }};color:#fff;border:none;border-radius:7px;font-size:.72rem;padding:.3rem .8rem;font-weight:700;cursor:pointer;white-space:nowrap;">
                            <i class="bi bi-credit-card me-1"></i>Pagar
                        </button>
                    </form>
                    @else
                    <span style="font-size:.7rem;color:#94a3b8;">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                    <td colspan="2" style="padding:.75rem 1rem;font-weight:800;color:#1e293b;text-align:right;">
                        TOTAL: {{ $mon }} {{ number_format($totalGeneral, 2) }}
                    </td>
                    <td colspan="4"></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @endif
</div>

@endsection
