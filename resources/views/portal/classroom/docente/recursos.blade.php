@extends('layouts.admin')
@section('page-title', 'Recursos — '.$claseVirtual->nombre)
@section('content')

@php
$color = $claseVirtual->portada_color ?? '#3B82F6';
$tiposIconos = [
    'pdf'          => ['bi-file-pdf-fill',     '#EF4444', 'PDF'],
    'video'        => ['bi-play-circle-fill',   '#8B5CF6', 'Video'],
    'enlace'       => ['bi-link-45deg',         '#3B82F6', 'Enlace'],
    'imagen'       => ['bi-image-fill',         '#10B981', 'Imagen'],
    'presentacion' => ['bi-easel-fill',         '#F59E0B', 'Presentación'],
    'otro'         => ['bi-file-earmark-fill',  '#6B7280', 'Archivo'],
];
@endphp

<div class="mb-4 d-flex align-items-center gap-3">
    <a href="{{ route('portal.docente.classroom.show', $claseVirtual) }}" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver al Aula
    </a>
    <div class="flex-grow-1">
        <h5 class="fw-bold mb-0">Recursos</h5>
        <small class="text-muted">{{ $claseVirtual->nombre }} &bull; {{ $recursos->count() }} recurso{{ $recursos->count() !== 1 ? 's' : '' }}</small>
    </div>
    <button class="btn btn-primary btn-sm" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalRecurso">
        <i class="bi bi-plus-lg me-1"></i>Agregar recurso
    </button>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Grid de recursos --}}
@if($recursos->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5">
        <div style="width:70px;height:70px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
            <i class="bi bi-folder2-open" style="font-size:1.8rem;color:#94A3B8;"></i>
        </div>
        <h6 class="fw-semibold text-muted mb-1">Sin recursos aún</h6>
        <p class="text-muted small mb-3">Agrega PDFs, videos, enlaces o presentaciones para compartir con tus estudiantes.</p>
        <button class="btn btn-primary btn-sm" style="border-radius:8px;" data-bs-toggle="modal" data-bs-target="#modalRecurso">
            <i class="bi bi-plus-lg me-1"></i>Agregar primer recurso
        </button>
    </div>
</div>
@else
<div class="row g-3">
@foreach($recursos as $recurso)
@php
    [$icono, $clr, $label] = $tiposIconos[$recurso->tipo] ?? $tiposIconos['otro'];
    $url = $recurso->url ?? ($recurso->ruta_archivo ? \Illuminate\Support\Facades\Storage::disk('public')->url($recurso->ruta_archivo) : null);
@endphp
<div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
        <div class="card-body d-flex gap-3 align-items-start">
            <div style="width:48px;height:48px;background:{{ $clr }}15;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $icono }}" style="font-size:1.3rem;color:{{ $clr }};"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold mb-1" style="font-size:.9rem;">{{ $recurso->titulo }}</div>
                @if($recurso->descripcion)
                <p class="text-muted mb-2" style="font-size:.8rem;line-height:1.4;">{{ Str::limit($recurso->descripcion, 80) }}</p>
                @endif
                <span class="badge rounded-pill" style="background:{{ $clr }}15;color:{{ $clr }};font-size:.7rem;">{{ $label }}</span>
            </div>
        </div>
        <div class="card-footer bg-transparent border-0 pt-0 pb-3 px-3 d-flex gap-2">
            @if($url)
            <a href="{{ $url }}" target="_blank" class="btn btn-sm flex-grow-1" style="border-radius:8px;background:{{ $clr }}12;color:{{ $clr }};border:none;font-size:.8rem;">
                <i class="bi bi-box-arrow-up-right me-1"></i>Abrir
            </a>
            @endif
            <form method="POST" action="{{ route('portal.docente.classroom.eliminar_recurso', [$claseVirtual, $recurso]) }}"
                  onsubmit="return confirm('¿Eliminar este recurso?')">
                @csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;" title="Eliminar">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
    </div>
</div>
@endforeach
</div>
@endif

{{-- Modal agregar recurso --}}
<div class="modal fade" id="modalRecurso" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius:16px;">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Agregar recurso</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('portal.docente.classroom.guardar_recurso', $claseVirtual) }}" enctype="multipart/form-data">
            @csrf
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Título <span class="text-danger">*</span></label>
                    <input type="text" name="titulo" class="form-control" style="border-radius:8px;" required maxlength="200" placeholder="Ej: Guía de estudio Unidad 3">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Tipo <span class="text-danger">*</span></label>
                    <select name="tipo" id="tipoRecurso" class="form-select" style="border-radius:8px;" required onchange="toggleCampos(this.value)">
                        <option value="enlace">Enlace web</option>
                        <option value="pdf">PDF</option>
                        <option value="video">Video</option>
                        <option value="imagen">Imagen</option>
                        <option value="presentacion">Presentación</option>
                        <option value="otro">Otro archivo</option>
                    </select>
                </div>

                <div class="mb-3" id="campoUrl">
                    <label class="form-label fw-semibold small">URL</label>
                    <input type="url" name="url" class="form-control" style="border-radius:8px;" placeholder="https://...">
                </div>

                <div class="mb-3 d-none" id="campoArchivo">
                    <label class="form-label fw-semibold small">Archivo</label>
                    <input type="file" name="archivo" class="form-control" style="border-radius:8px;" accept=".pdf,.jpg,.jpeg,.png,.gif,.ppt,.pptx,.mp4,.webm">
                    <div class="form-text">Máximo 20 MB</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Descripción</label>
                    <textarea name="descripcion" class="form-control" style="border-radius:8px;" rows="2" maxlength="500" placeholder="Descripción breve (opcional)"></textarea>
                </div>

            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
                    <i class="bi bi-cloud-upload me-1"></i>Guardar recurso
                </button>
            </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function toggleCampos(tipo) {
    const archivos = ['pdf','imagen','presentacion','otro'];
    document.getElementById('campoUrl').classList.toggle('d-none', archivos.includes(tipo) && tipo !== 'enlace' && tipo !== 'video');
    document.getElementById('campoArchivo').classList.toggle('d-none', !archivos.includes(tipo));
}
// Init al cargar
toggleCampos(document.getElementById('tipoRecurso').value);
</script>
@endpush

@endsection
