@extends('layouts.portal')
@section('page-title', $planClase->titulo)
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planes
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h2 class="h4 mb-1">{{ $planClase->titulo }}</h2>
            <p class="text-muted small mb-0">
                {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.docente.planes-clase.pdf', [$asignacion, $planClase]) }}"
               target="_blank" class="btn btn-sm btn-danger">
                <i class="bi bi-file-earmark-pdf-fill me-1"></i>PDF
            </a>
            <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="row g-4">
        {{-- Columna principal --}}
        <div class="col-lg-8">

            {{-- Info General + Estado --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold">Información General</span>
                    <form method="POST"
                          action="{{ route('portal.docente.planes-clase.toggle', [$asignacion, $planClase]) }}">
                        @csrf @method('PATCH')
                        <button type="submit"
                            class="btn btn-sm {{ $planClase->publicado ? 'btn-success' : 'btn-secondary' }}">
                            @if($planClase->publicado)
                                <i class="bi bi-eye-fill me-1"></i> Publicado — clic para ocultar
                            @else
                                <i class="bi bi-eye-slash me-1"></i> Borrador — clic para publicar
                            @endif
                        </button>
                    </form>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-4">Área</dt>
                        <dd class="col-sm-8">
                            <span class="badge {{ $planClase->area === 'academica' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                {{ ucfirst($planClase->area) }}
                            </span>
                        </dd>
                        <dt class="col-sm-4">Tipo</dt>
                        <dd class="col-sm-8 text-capitalize">{{ $planClase->tipo_plan }}</dd>
                        @if($planClase->semana)
                        <dt class="col-sm-4">Semana</dt>
                        <dd class="col-sm-8"># {{ $planClase->semana }}</dd>
                        @endif
                        @if($planClase->fecha_inicio)
                        <dt class="col-sm-4">Fechas</dt>
                        <dd class="col-sm-8">{{ $planClase->fecha_inicio->format('d/m/Y') }}
                            @if($planClase->fecha_fin) – {{ $planClase->fecha_fin->format('d/m/Y') }} @endif</dd>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Vista previa del archivo --}}
            @if($planClase->tieneArchivo())
            @php
                $ext      = strtolower(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION));
                $fileUrl  = Storage::disk('public')->url($planClase->archivo_path);
                $isPdf    = $ext === 'pdf';
                $isImage  = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                $isOffice = in_array($ext, ['doc','docx','ppt','pptx','xls','xlsx']);
                $viewerUrl = $isOffice
                    ? 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode(url(Storage::url($planClase->archivo_path)))
                    : null;
            @endphp
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-file-earmark me-1"></i>Vista Previa del Documento</span>
                    <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $planClase]) }}"
                        class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Descargar
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($isPdf)
                        {{-- Vista previa PDF nativa --}}
                        <iframe src="{{ $fileUrl }}"
                                style="width:100%;height:650px;border:0;"
                                loading="lazy"
                                title="{{ $planClase->archivo_nombre }}">
                            <p class="p-3 text-muted small">
                                Tu navegador no puede mostrar el PDF.
                                <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $planClase]) }}">Descárgalo aquí</a>.
                            </p>
                        </iframe>
                    @elseif($isImage)
                        {{-- Vista previa imagen --}}
                        <div class="text-center p-3">
                            <img src="{{ $fileUrl }}"
                                 alt="{{ $planClase->archivo_nombre }}"
                                 class="img-fluid rounded"
                                 style="max-height:600px;">
                        </div>
                    @elseif($isOffice && $viewerUrl)
                        {{-- Vista previa Office via Microsoft Viewer --}}
                        <iframe src="{{ $viewerUrl }}"
                                style="width:100%;height:650px;border:0;"
                                loading="lazy"
                                title="{{ $planClase->archivo_nombre }}">
                        </iframe>
                        <div class="p-2 text-center">
                            <small class="text-muted">
                                Si la vista previa no carga,
                                <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $planClase]) }}">descarga el archivo</a>.
                            </small>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-file-earmark-x display-5 d-block mb-2"></i>
                            Vista previa no disponible para este tipo de archivo.
                            <div class="mt-3">
                                <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $planClase]) }}"
                                    class="btn btn-primary btn-sm">
                                    <i class="bi bi-download me-1"></i> Descargar {{ strtoupper($ext) }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($planClase->intencion_pedagogica)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-bullseye me-1"></i>Intención Pedagógica</div>
                <div class="card-body small">{{ $planClase->intencion_pedagogica }}</div>
            </div>
            @endif

            {{-- Momentos --}}
            @php $colores = ['inicio'=>'success','desarrollo'=>'primary','cierre'=>'warning']; @endphp
            @foreach(['inicio','desarrollo','cierre'] as $tipo)
            @php $momento = $planClase->momentos->firstWhere('tipo', $tipo); @endphp
            @if($momento)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold text-{{ $colores[$tipo] }}">
                    <i class="bi bi-{{ ['inicio'=>'play-circle','desarrollo'=>'arrow-right-circle','cierre'=>'stop-circle'][$tipo] }} me-1"></i>
                    {{ ucfirst($tipo) }}
                    <span class="text-muted fw-normal ms-2 small">({{ $momento->duracion_minutos }} min)</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        @if($momento->area_curricular)
                        <dt class="col-sm-4">Área Curricular</dt>
                        <dd class="col-sm-8">{{ $momento->area_curricular }}</dd>
                        @endif
                        @if($momento->competencias_especificas)
                        <dt class="col-sm-4">Competencias</dt>
                        <dd class="col-sm-8" style="white-space:pre-wrap">{{ $momento->competencias_especificas }}</dd>
                        @endif
                        @if($momento->contenidos)
                        <dt class="col-sm-4">Contenidos</dt>
                        <dd class="col-sm-8" style="white-space:pre-wrap">{{ $momento->contenidos }}</dd>
                        @endif
                        @if($momento->actividades)
                        <dt class="col-sm-4">Actividades</dt>
                        <dd class="col-sm-8" style="white-space:pre-wrap">{{ $momento->actividades }}</dd>
                        @endif
                        @if($momento->indicador_logro)
                        <dt class="col-sm-4">Indicador de Logro</dt>
                        <dd class="col-sm-8" style="white-space:pre-wrap">{{ $momento->indicador_logro }}</dd>
                        @endif
                        @if($momento->recursos)
                        <dt class="col-sm-4">Recursos</dt>
                        <dd class="col-sm-8" style="white-space:pre-wrap">{{ $momento->recursos }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
            @endif
            @endforeach

        </div>

        {{-- Columna lateral --}}
        <div class="col-lg-4">

            @if($planClase->estrategias_nombres && count($planClase->estrategias_nombres))
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-lightbulb me-1"></i>Estrategias</div>
                <div class="card-body">
                    @foreach($planClase->estrategias_nombres as $nombre)
                        <span class="badge bg-light text-dark border mb-1 small">{{ $nombre }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($planClase->tieneArchivo())
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-paperclip me-1"></i>Archivo Adjunto</div>
                <div class="card-body">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="bi bi-file-earmark-{{ in_array(strtolower(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION)), ['pdf']) ? 'pdf text-danger' : (in_array(strtolower(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION)), ['doc','docx']) ? 'word text-primary' : (in_array(strtolower(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION)), ['xls','xlsx']) ? 'excel text-success' : (in_array(strtolower(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION)), ['ppt','pptx']) ? 'ppt text-warning' : 'image text-info'))) }} fs-3"></i>
                        <div>
                            <div class="small fw-semibold">{{ $planClase->archivo_nombre }}</div>
                            <div class="text-muted" style="font-size:.7rem;">{{ strtoupper(pathinfo($planClase->archivo_nombre, PATHINFO_EXTENSION)) }}</div>
                        </div>
                    </div>
                    <a href="{{ route('portal.docente.planes-clase.download', [$asignacion, $planClase]) }}"
                        class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-download me-1"></i> Descargar
                    </a>
                </div>
            </div>
            @endif

            @if($planClase->observacion)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold small"><i class="bi bi-chat-left-text me-1"></i>Observación</div>
                <div class="card-body small" style="white-space:pre-wrap">{{ $planClase->observacion }}</div>
            </div>
            @endif

            <form method="POST" action="{{ route('portal.docente.planes-clase.destroy', [$asignacion, $planClase]) }}"
                  onsubmit="return confirm('¿Eliminar este plan permanentemente?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm w-100">
                    <i class="bi bi-trash me-1"></i> Eliminar Plan
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
