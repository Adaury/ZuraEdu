@extends('layouts.portal-estudiante')
@section('title', 'Mis Préstamos de Biblioteca')

@section('activeKey', 'mis-prestamos')

@section('content')

{{-- Header --}}
<div class="mb-4 p-4" style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);border-radius:16px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-20px;right:-20px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <h4 class="text-white fw-bold mb-1"><i class="bi bi-book-half me-2"></i>Mis Préstamos de Biblioteca</h4>
        <small class="text-white opacity-75">Libros que tienes prestados actualmente e historial de devoluciones</small>
    </div>
</div>

{{-- Préstamos activos --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-bookmark-check me-2" style="color:#3b82f6;"></i>
            Préstamos Activos
            @if($prestamosActivos->isNotEmpty())
            <span class="badge bg-primary ms-1">{{ $prestamosActivos->count() }}</span>
            @endif
        </h6>
    </div>

    @if($prestamosActivos->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-book" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
        <small>No tienes préstamos activos en este momento.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-4 py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Libro</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Préstamo</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Vence</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Estado</th>
                </tr>
            </thead>
            <tbody>
            @foreach($prestamosActivos as $prestamo)
            @php
                $vencido    = $prestamo->estado === 'vencido' || ($prestamo->fecha_vencimiento < now());
                $diasRestantes = now()->diffInDays($prestamo->fecha_vencimiento, false);
            @endphp
            <tr class="{{ $vencido ? 'table-danger' : ($diasRestantes <= 3 ? 'table-warning' : '') }}">
                <td class="px-4 py-3">
                    <div class="fw-semibold" style="color:#1e293b;">{{ $prestamo->libro?->titulo ?? '—' }}</div>
                    <small class="text-muted">{{ $prestamo->libro?->autor }}</small>
                    @if($prestamo->notas)
                    <br><small class="text-muted fst-italic">{{ $prestamo->notas }}</small>
                    @endif
                </td>
                <td class="py-3 text-center small text-muted">
                    {{ $prestamo->fecha_prestamo?->format('d/m/Y') }}
                </td>
                <td class="py-3 text-center">
                    <span class="fw-bold {{ $vencido ? 'text-danger' : ($diasRestantes <= 3 ? 'text-warning' : 'text-success') }}" style="font-size:.85rem;">
                        {{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}
                    </span>
                    <br>
                    @if($vencido)
                    <small class="text-danger fw-bold">Vencido hace {{ abs($diasRestantes) }} día(s)</small>
                    @elseif($diasRestantes <= 3)
                    <small class="text-warning fw-bold">Vence en {{ $diasRestantes }} día(s)</small>
                    @else
                    <small class="text-muted">{{ $diasRestantes }} días restantes</small>
                    @endif
                </td>
                <td class="py-3 text-center">
                    @if($vencido)
                    <span class="badge" style="background:#fee2e2;color:#dc2626;font-size:.72rem;">
                        <i class="bi bi-exclamation-triangle me-1"></i>Vencido
                    </span>
                    @elseif($diasRestantes <= 3)
                    <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;">
                        <i class="bi bi-clock me-1"></i>Por vencer
                    </span>
                    @else
                    <span class="badge" style="background:#dbeafe;color:#1d4ed8;font-size:.72rem;">
                        <i class="bi bi-bookmark-check me-1"></i>Activo
                    </span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

{{-- Historial --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-header bg-white border-bottom py-3 px-4" style="border-radius:14px 14px 0 0;">
        <h6 class="fw-bold mb-0">
            <i class="bi bi-clock-history me-2" style="color:#10b981;"></i>
            Historial de Devoluciones
            <small class="text-muted fw-normal">(últimos {{ $historial->count() }} registros)</small>
        </h6>
    </div>

    @if($historial->isEmpty())
    <div class="text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
        <small>Sin historial de préstamos devueltos.</small>
    </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="px-4 py-3 text-muted fw-semibold" style="font-size:.72rem;text-transform:uppercase;">Libro</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Préstamo</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Vencimiento</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Devuelto</th>
                    <th class="py-3 text-muted fw-semibold text-center" style="font-size:.72rem;text-transform:uppercase;">Puntual</th>
                </tr>
            </thead>
            <tbody>
            @foreach($historial as $prestamo)
            @php
                $aTiempo = $prestamo->fecha_devolucion && $prestamo->fecha_vencimiento
                    && $prestamo->fecha_devolucion <= $prestamo->fecha_vencimiento;
            @endphp
            <tr>
                <td class="px-4 py-3">
                    <div class="fw-semibold" style="color:#1e293b;">{{ $prestamo->libro?->titulo ?? '—' }}</div>
                    <small class="text-muted">{{ $prestamo->libro?->autor }}</small>
                </td>
                <td class="py-3 text-center small text-muted">{{ $prestamo->fecha_prestamo?->format('d/m/Y') }}</td>
                <td class="py-3 text-center small text-muted">{{ $prestamo->fecha_vencimiento?->format('d/m/Y') }}</td>
                <td class="py-3 text-center small text-muted">{{ $prestamo->fecha_devolucion?->format('d/m/Y') }}</td>
                <td class="py-3 text-center">
                    @if($aTiempo)
                    <span class="badge" style="background:#d1fae5;color:#065f46;font-size:.72rem;">
                        <i class="bi bi-check-circle me-1"></i>A tiempo
                    </span>
                    @else
                    <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;">
                        <i class="bi bi-exclamation-circle me-1"></i>Tardío
                    </span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

@endsection
