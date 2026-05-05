@extends('layouts.admin')
@section('page-title', 'Personas — '.$claseVirtual->nombre)
@section('content')

@php $color = $claseVirtual->portada_color ?? '#3B82F6'; @endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver al Aula
    </a>
    <div>
        <h5 class="fw-bold mb-0">Personas</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }} &bull; {{ $matriculas->count() }} estudiante{{ $matriculas->count() !== 1 ? 's' : '' }}</small>
    </div>
</div>

{{-- Docente --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
    <div class="card-body">
        <h6 class="fw-bold mb-3" style="color:#6B7280;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">Docente</h6>
        @php $asig = $claseVirtual->asignacion; @endphp
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.1rem;font-weight:700;color:#fff;flex-shrink:0;background:{{ $color }};">
                {{ strtoupper(substr($asig->docente->nombres ?? 'D', 0, 1)) }}
            </div>
            <div>
                <div class="fw-semibold">{{ $asig->docente->nombre_completo ?? ($asig->docente->nombres.' '.$asig->docente->apellidos) }}</div>
                <small class="text-muted">{{ $asig->asignatura?->nombre }} &bull; {{ $asig->grupo?->nombre }}</small>
            </div>
            <span class="badge ms-auto" style="background:{{ $color }}18;color:{{ $color }};">Docente</span>
        </div>
    </div>
</div>

{{-- Estudiantes --}}
<div class="card border-0 shadow-sm" style="border-radius:14px;">
    <div class="card-body p-0">
        <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom:1px solid #F1F5F9;">
            <h6 class="fw-bold mb-0" style="color:#6B7280;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;">
                Estudiantes ({{ $matriculas->count() }})
            </h6>
            <input type="text" id="buscarEstudiante" class="form-control form-control-sm" style="max-width:220px;border-radius:8px;" placeholder="Buscar estudiante...">
        </div>

        @forelse($matriculas as $matricula)
        @php
            $est = $matricula->estudiante;
            $nombre = $est->nombre_completo ?? ($est->nombres.' '.$est->apellidos);
            $inicial = strtoupper(substr($nombre, 0, 1));
            $colores = ['#EF4444','#F59E0B','#10B981','#3B82F6','#8B5CF6','#EC4899','#14B8A6'];
            $bg = $colores[crc32($nombre) % count($colores)];
        @endphp
        <div class="estudiante-row d-flex align-items-center gap-3 px-4 py-3" style="border-bottom:1px solid #F8FAFC;">
            <div style="width:40px;height:40px;border-radius:50%;background:{{ $bg }};color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.95rem;flex-shrink:0;">
                {{ $inicial }}
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold" style="font-size:.9rem;">{{ $nombre }}</div>
                <small class="text-muted">{{ $est->user?->email ?? ('Mat. '.$est->matricula ?? '—') }}</small>
            </div>
            <span class="badge rounded-pill" style="background:#F0FDF4;color:#16A34A;font-size:.7rem;">Activo</span>
        </div>
        @empty
        <div class="text-center py-5">
            <div style="width:60px;height:60px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="bi bi-people" style="font-size:1.5rem;color:#94A3B8;"></i>
            </div>
            <p class="text-muted mb-0">No hay estudiantes matriculados en este grupo.</p>
        </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
document.getElementById('buscarEstudiante').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.estudiante-row').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>
@endpush

@endsection
