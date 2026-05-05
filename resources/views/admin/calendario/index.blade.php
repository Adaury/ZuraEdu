@extends('layouts.admin')

@section('page-title', 'Calendario Académico')

@push('styles')
<style>
    .evento-card {
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        background: #fff;
        padding: .9rem 1.1rem;
        margin-bottom: .5rem;
        border-left: 4px solid;
        transition: box-shadow .15s;
    }
    .evento-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.07); }
    .tipo-badge {
        font-size: .68rem; font-weight: 700; text-transform: uppercase;
        letter-spacing: .04em; padding: .2rem .55rem; border-radius: 20px;
    }

    [data-theme="dark"] .evento-card { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .evento-card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.3); }
</style>
@endpush

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0" style="color:#1e3a6e;">
            <i class="bi bi-calendar-event me-2"></i>Calendario Académico
        </h4>
        <p class="text-muted mb-0" style="font-size:.875rem;">{{ $schoolYear->nombre ?? 'Sin año activo' }}</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.calendario.excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        <a href="{{ route('admin.calendario.pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
        </a>
        @if(Auth::user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
        <a href="{{ route('admin.calendario.create') }}" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>Agregar Evento
        </a>
        @endif
    </div>
</div>

<div class="row g-4">
    {{-- Próximos eventos --}}
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-clock me-2 text-warning"></i>Próximos Eventos</h6>
            </div>
            <div class="card-body">
                @forelse($proximos as $evento)
                <div style="border-left: 3px solid {{ $evento->color }};padding-left:.75rem;margin-bottom:1rem;">
                    <div class="fw-semibold" style="font-size:.85rem;color:#1e293b;">{{ $evento->titulo }}</div>
                    <div style="font-size:.75rem;color:#6b7280;">
                        <i class="bi bi-calendar3 me-1"></i>{{ $evento->fecha_inicio->format('d/m/Y') }}
                        @if($evento->dias_restantes == 0)
                            <span class="badge bg-danger ms-1" style="font-size:.6rem;">Hoy</span>
                        @elseif($evento->dias_restantes <= 7)
                            <span class="badge bg-warning text-dark ms-1" style="font-size:.6rem;">En {{ $evento->dias_restantes }} días</span>
                        @endif
                    </div>
                </div>
                @empty
                <p class="text-muted" style="font-size:.83rem;">No hay eventos próximos.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Lista completa --}}
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Todos los Eventos</h6>
                <span class="badge bg-secondary">{{ $eventos->count() }} eventos</span>
            </div>
            <div class="card-body p-3">
                @forelse($eventos as $evento)
                <div class="evento-card" style="border-left-color:{{ $evento->color }};">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                                <span class="fw-semibold" style="font-size:.88rem;color:#1e293b;">{{ $evento->titulo }}</span>
                                <span class="tipo-badge" style="background:{{ $evento->color }}22;color:{{ $evento->color }};">
                                    {{ \App\Models\CalendarioAcademico::tiposLabels()[$evento->tipo] ?? $evento->tipo }}
                                </span>
                            </div>
                            <div style="font-size:.78rem;color:#6b7280;">
                                <i class="bi bi-calendar3 me-1"></i>
                                {{ $evento->fecha_inicio->format('d/m/Y') }}
                                @if($evento->fecha_fin)
                                 – {{ $evento->fecha_fin->format('d/m/Y') }}
                                @endif
                                @if($evento->hora_inicio)
                                · {{ $evento->hora_inicio }}
                                @endif
                                · Aplica a: <strong>{{ $evento->aplica_a }}</strong>
                            </div>
                            @if($evento->descripcion)
                            <div class="mt-1 text-muted" style="font-size:.78rem;">{{ Str::limit($evento->descripcion, 80) }}</div>
                            @endif
                        </div>
                        @if(Auth::user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
                        <div class="d-flex gap-1 flex-shrink-0">
                            <a href="{{ route('admin.calendario.edit', $evento) }}"
                               class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;padding:.2rem .5rem;">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.calendario.destroy', $evento) }}"
                                  onsubmit="return confirm('¿Eliminar este evento?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" style="font-size:.7rem;padding:.2rem .5rem;">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </div>
                </div>
                @empty
                <div class="empty-state-enhanced">
                    <div class="empty-illustration"><i class="bi bi-calendar-x"></i></div>
                    <div class="empty-title">Sin eventos</div>
                    <div class="empty-desc">No hay eventos registrados en el calendario para este año escolar.</div>
                </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
