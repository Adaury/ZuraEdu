@extends('layouts.admin')
@section('page-title', 'Revisar Entrega')
@section('content')

@php
$color = $claseVirtual->portada_color ?? '#3B82F6';
$est   = $entrega->matricula?->estudiante;
$cfg   = $entrega->estado_info;
@endphp

{{-- Breadcrumb --}}
<div class="mb-4 d-flex align-items-center gap-2 text-muted small">
    <a href="{{ route('portal.docente.classroom.index') }}" class="text-decoration-none text-muted">Mis Aulas</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="text-decoration-none text-muted">{{ $claseVirtual->nombre }}</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <a href="{{ route('portal.docente.classroom.entregas', [$claseVirtual, $material]) }}" class="text-decoration-none text-muted">{{ $material->titulo }}</a>
    <i class="bi bi-chevron-right" style="font-size:.7rem;"></i>
    <span class="fw-semibold text-dark">{{ $est?->nombres }} {{ $est?->apellidos }}</span>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row g-4">

{{-- ═══ PANEL IZQUIERDO: ENTREGA DEL ESTUDIANTE ═══ --}}
<div class="col-lg-7">
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        {{-- Header --}}
        <div class="p-4 border-bottom d-flex align-items-start gap-3">
            <div style="width:46px;height:46px;background:{{ $color }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:1rem;color:{{ $color }};">
                {{ strtoupper(substr($est?->nombres ?? '?', 0, 1)) }}
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">{{ $est?->nombres }} {{ $est?->apellidos }}</div>
                <small class="text-muted">
                    @if($entrega->fecha_entrega)
                        Entregado {{ $entrega->fecha_entrega->format('d/m/Y \a \l\a\s H:i') }}
                        @if($material->fecha_limite && $entrega->fecha_entrega > $material->fecha_limite)
                            <span class="text-danger ms-1">(con retraso)</span>
                        @endif
                    @else
                        Sin entrega
                    @endif
                </small>
            </div>
            <span class="badge rounded-pill bg-{{ $cfg['color'] }}" style="font-size:.8rem;">
                <i class="bi {{ $cfg['icon'] }} me-1"></i>{{ $cfg['label'] }}
            </span>
        </div>

        <div class="card-body">
            {{-- Respuesta en texto --}}
            @if($entrega->contenido)
            <div class="mb-4">
                <h6 class="fw-semibold text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;">Respuesta del estudiante</h6>
                <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;line-height:1.7;font-size:.9rem;">
                    {!! nl2br(e($entrega->contenido)) !!}
                </div>
            </div>
            @endif

            {{-- URL entrega --}}
            @if($entrega->url_entrega)
            <div class="mb-4">
                <h6 class="fw-semibold text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;">Enlace entregado</h6>
                <a href="{{ $entrega->url_entrega }}" target="_blank" class="btn btn-outline-primary btn-sm" style="border-radius:8px;">
                    <i class="bi bi-link-45deg me-1"></i>Abrir enlace
                </a>
            </div>
            @endif

            {{-- Archivos de entrega --}}
            @if($entrega->archivos->isNotEmpty())
            <div class="mb-4">
                <h6 class="fw-semibold text-muted mb-2" style="font-size:.8rem;text-transform:uppercase;letter-spacing:.5px;">Archivos adjuntos</h6>
                <div class="d-flex flex-column gap-2">
                    @foreach($entrega->archivos as $arch)
                    <div class="d-flex align-items-center gap-3 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;">
                        <i class="bi {{ $arch->esPdf() ? 'bi-file-pdf-fill text-danger' : ($arch->esImagen() ? 'bi-image-fill text-success' : 'bi-file-earmark-fill text-secondary') }}" style="font-size:1.4rem;flex-shrink:0;"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold" style="font-size:.85rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $arch->nombre_original }}</div>
                            <small class="text-muted">{{ $arch->tamanio_humano }}</small>
                        </div>
                        <a href="{{ $arch->url }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;flex-shrink:0;">
                            <i class="bi bi-download"></i>
                        </a>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!$entrega->contenido && !$entrega->url_entrega && $entrega->archivos->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size:2rem;color:#CBD5E1;display:block;margin-bottom:.5rem;"></i>
                <p class="mb-0 small">El estudiante aún no ha entregado nada</p>
            </div>
            @endif

            {{-- Retroalimentación del docente (si ya existe) --}}
            @if($entrega->retroalimentacion)
            <div class="p-3 rounded-3 mt-3" style="background:#ECFDF5;border:1px solid #A7F3D0;">
                <div class="fw-semibold small text-success mb-1"><i class="bi bi-chat-left-text me-1"></i>Retroalimentación enviada</div>
                <div class="small">{{ $entrega->retroalimentacion }}</div>
            </div>
            @endif
        </div>
    </div>

    {{-- Material de referencia --}}
    <div class="card border-0 shadow-sm mt-3" style="border-radius:14px;">
        <div class="card-body">
            <h6 class="fw-bold mb-2">Instrucciones de la actividad</h6>
            <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge" style="background:{{ $material->tipo==='tarea' ? '#F59E0B' : '#EF4444' }}20;color:{{ $material->tipo==='tarea' ? '#B45309' : '#B91C1C' }};">{{ ucfirst($material->tipo) }}</span>
                @if($material->puntos)<span class="text-muted small"><i class="bi bi-star me-1"></i>{{ $material->puntos }} pts</span>@endif
                @if($material->fecha_limite)<span class="text-muted small"><i class="bi bi-calendar me-1"></i>{{ $material->fecha_limite->format('d/m/Y H:i') }}</span>@endif
            </div>
            @if($material->contenido)<p class="text-muted small mb-0">{{ $material->contenido }}</p>@endif

            @if($material->archivos->isNotEmpty())
            <div class="mt-2 d-flex flex-wrap gap-2">
                @foreach($material->archivos as $arch)
                <a href="{{ Storage::disk('public')->url($arch->ruta) }}" target="_blank" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.75rem;">
                    <i class="bi bi-paperclip me-1"></i>{{ Str::limit($arch->nombre_original,20) }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══ PANEL DERECHO: CALIFICACIÓN ═══ --}}
<div class="col-lg-5">
    <div class="card border-0 shadow-sm" style="border-radius:16px;position:sticky;top:80px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-4">Calificación</h6>

            <form method="POST" action="{{ route('portal.docente.classroom.calificar_entrega', [$claseVirtual, $entrega]) }}">
                @csrf @method('PATCH')

                {{-- Rúbrica (si existe) --}}
                @if($material->rubric?->criterios?->isNotEmpty())
                <div class="mb-4">
                    <h6 class="fw-semibold mb-3" style="font-size:.85rem;color:#6366F1;">
                        <i class="bi bi-grid-3x3-gap me-1"></i>Rúbrica: {{ $material->rubric->nombre }}
                    </h6>
                    @foreach($material->rubric->criterios as $criterio)
                    @php $califPrevia = $entrega->rubricCalificaciones->where('criterio_id', $criterio->id)->first(); @endphp
                    <div class="mb-3 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="fw-semibold small">{{ $criterio->nombre }}</span>
                            <span class="text-muted small">/ {{ $criterio->puntaje_max }}</span>
                        </div>
                        @if($criterio->descripcion)<p class="text-muted" style="font-size:.75rem;margin-bottom:.5rem;">{{ $criterio->descripcion }}</p>@endif
                        <div class="d-flex gap-2">
                            <input type="number" name="rubrica[{{ $loop->index }}][puntaje]"
                                   class="form-control form-control-sm" style="width:80px;"
                                   min="0" max="{{ $criterio->puntaje_max }}" step="0.5"
                                   value="{{ $califPrevia?->puntaje ?? '' }}" placeholder="0">
                            <input type="hidden" name="rubrica[{{ $loop->index }}][criterio_id]" value="{{ $criterio->id }}">
                            <input type="text" name="rubrica[{{ $loop->index }}][comentario]"
                                   class="form-control form-control-sm"
                                   value="{{ $califPrevia?->comentario }}" placeholder="Comentario opcional">
                        </div>
                    </div>
                    @endforeach
                    <div class="text-muted small mb-3">La nota se calculará automáticamente desde la rúbrica.</div>
                </div>
                @endif

                {{-- Nota directa --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Calificación</label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="number" name="calificacion" id="inputNota"
                               class="form-control" style="max-width:100px;font-size:1.3rem;font-weight:700;text-align:center;"
                               min="0" max="{{ $material->puntos ?? 100 }}"
                               value="{{ $entrega->calificacion }}"
                               placeholder="0" required>
                        <span class="text-muted">/ {{ $material->puntos ?? 100 }}</span>
                        @if($entrega->calificacion)
                        @php $pct = $material->puntos ? ($entrega->calificacion/$material->puntos)*100 : $entrega->calificacion; @endphp
                        <span class="badge rounded-pill {{ $pct>=90?'bg-success':($pct>=70?'bg-warning text-dark':($pct>=60?'bg-info':'bg-danger')) }} ms-2">
                            {{ round($pct) }}%
                        </span>
                        @endif
                    </div>
                </div>

                {{-- Comentario --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Comentario al estudiante</label>
                    <textarea name="comentario_docente" class="form-control" rows="2"
                              placeholder="Comentario breve visible para el estudiante...">{{ $entrega->comentario_docente }}</textarea>
                </div>

                {{-- Retroalimentación --}}
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Retroalimentación detallada</label>
                    <textarea name="retroalimentacion" class="form-control" rows="3"
                              placeholder="Explicación, sugerencias, áreas de mejora...">{{ $entrega->retroalimentacion }}</textarea>
                </div>

                {{-- Opciones --}}
                @if($material->periodo_id)
                <div class="form-check mb-2">
                    <input type="checkbox" name="sincronizar_notas" id="chkSync" class="form-check-input" value="1" {{ $entrega->estado==='calificado' ? '' : 'checked' }}>
                    <label for="chkSync" class="form-check-label small">
                        <i class="bi bi-arrow-repeat me-1 text-success"></i>Sincronizar con libro de notas ({{ $material->periodo?->nombre }})
                    </label>
                </div>
                @endif

                {{-- Botones --}}
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success flex-fill" style="border-radius:10px;">
                        <i class="bi bi-check-circle me-1"></i>Calificar
                    </button>
                </div>
            </form>

            {{-- Devolver --}}
            @if($entrega->estado === 'entregado' || $entrega->estado === 'atrasado')
            <form method="POST" action="{{ route('portal.docente.classroom.devolver_entrega', [$claseVirtual, $entrega]) }}" class="mt-2">
                @csrf @method('PATCH')
                <input type="text" name="comentario_docente" class="form-control form-control-sm mb-2" placeholder="Motivo de devolución...">
                <button type="submit" class="btn btn-outline-warning btn-sm w-100" style="border-radius:10px;">
                    <i class="bi bi-arrow-return-left me-1"></i>Devolver para corrección
                </button>
            </form>
            @endif
        </div>
    </div>
</div>

</div>
@endsection
