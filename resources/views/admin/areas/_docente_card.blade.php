<div class="d-flex align-items-start gap-3 mb-3">
    <div class="docente-avatar" style="background:{{ $color ?? '#1e3a6e' }};">
        {{ strtoupper(substr($docente->nombres, 0, 1) . substr($docente->apellidos, 0, 1)) }}
    </div>
    <div class="flex-grow-1 min-w-0">
        <div class="fw-bold text-truncate" style="color:#1e293b;font-size:.88rem;">
            {{ $docente->nombre_completo }}
        </div>
        <div class="text-muted" style="font-size:.76rem;">{{ $docente->especialidad ?? $docente->cargo ?? 'Docente' }}</div>
    </div>
    <a href="{{ route('admin.perfiles.docente', $docente) }}"
       class="btn btn-sm btn-outline-secondary" style="font-size:.7rem;padding:.2rem .45rem;" title="Ver perfil">
        <i class="bi bi-person"></i>
    </a>
</div>

<div class="mb-2" style="font-size:.75rem;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;">
    Asignaturas
</div>
<div>
    @forelse($asigs as $asig)
    <div class="asig-chip" style="background:{{ $color ?? '#1e3a6e' }}18;border-color:{{ $color ?? '#1e3a6e' }}30;color:#1e293b;">
        {{ $asig->asignatura->nombre ?? 'N/A' }}
        <span style="opacity:.5;">|</span>
        {{ optional($asig->grupo)->nombre_corto ?? '—' }}
    </div>
    @empty
    <span class="text-muted" style="font-size:.78rem;">Sin asignaciones este año.</span>
    @endforelse
</div>
