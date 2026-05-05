@extends('layouts.admin')

@section('title', $plan->titulo)

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h1 class="h3 mb-0">{{ $plan->titulo }}</h1>
            <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.planes-clase.index') }}">Planes de Clase</a></li>
                <li class="breadcrumb-item active">Ver</li>
            </ol></nav>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.planes-clase.edit', $plan) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-pencil me-1"></i> Editar
            </a>
            @if($plan->tieneArchivo())
            <a href="{{ route('admin.planes-clase.download', $plan) }}" class="btn btn-outline-info btn-sm">
                <i class="bi bi-download me-1"></i> Descargar
            </a>
            @endif
            <form method="POST" action="{{ route('admin.planes-clase.destroy', $plan) }}"
                  onsubmit="return confirm('¿Eliminar este plan?')">
                @csrf @method('DELETE')
                <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i> Eliminar</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">

            {{-- Encabezado del plan --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-info-circle me-1"></i>Información General</span>
                    @if($plan->publicado)
                        <span class="badge bg-success">Publicado</span>
                    @else
                        <span class="badge bg-secondary">Borrador</span>
                    @endif
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-3">Área</dt>
                        <dd class="col-sm-9">
                            <span class="badge {{ $plan->area === 'academica' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                {{ ucfirst($plan->area) }}
                            </span>
                        </dd>
                        <dt class="col-sm-3">Tipo</dt>
                        <dd class="col-sm-9 text-capitalize">{{ $plan->tipo_plan }}</dd>
                        @if($plan->semana)
                        <dt class="col-sm-3">Semana</dt>
                        <dd class="col-sm-9"># {{ $plan->semana }}</dd>
                        @endif
                        @if($plan->fecha_inicio)
                        <dt class="col-sm-3">Fechas</dt>
                        <dd class="col-sm-9">
                            {{ $plan->fecha_inicio->format('d/m/Y') }}
                            @if($plan->fecha_fin) – {{ $plan->fecha_fin->format('d/m/Y') }} @endif
                        </dd>
                        @endif
                        @if($plan->asignacion)
                        <dt class="col-sm-3">Asignación</dt>
                        <dd class="col-sm-9">{{ $plan->asignacion->asignatura->nombre ?? '—' }} — {{ $plan->asignacion->grupo->nombre_completo ?? '' }}</dd>
                        @endif
                        @if($plan->grado_seccion)
                        <dt class="col-sm-3">Grado/Sección</dt>
                        <dd class="col-sm-9">{{ $plan->grado_seccion }}</dd>
                        @endif
                        @if($plan->docente)
                        <dt class="col-sm-3">Docente</dt>
                        <dd class="col-sm-9">{{ $plan->docente->nombre_completo }}</dd>
                        @endif
                        @if($plan->creadoPor)
                        <dt class="col-sm-3">Creado por</dt>
                        <dd class="col-sm-9">{{ $plan->creadoPor->name }} — {{ $plan->created_at->format('d/m/Y H:i') }}</dd>
                        @endif
                    </dl>
                </div>
            </div>

            @if($plan->intencion_pedagogica)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-bullseye me-1"></i>Intención Pedagógica</div>
                <div class="card-body">{{ $plan->intencion_pedagogica }}</div>
            </div>
            @endif

            {{-- Momentos --}}
            @php $colores = ['inicio'=>'success','desarrollo'=>'primary','cierre'=>'warning']; @endphp
            @foreach(['inicio','desarrollo','cierre'] as $tipo)
            @php $momento = $plan->momentos->firstWhere('tipo', $tipo); @endphp
            @if($momento)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold text-{{ $colores[$tipo] }}">
                    <i class="bi bi-{{ ['inicio'=>'play-circle','desarrollo'=>'arrow-right-circle','cierre'=>'stop-circle'][$tipo] }} me-1"></i>
                    {{ ucfirst($tipo) }}
                    <span class="text-muted fw-normal ms-2 small">({{ $momento->duracion_minutos }} min)</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        @if($momento->area_curricular)
                        <dt class="col-sm-3 small">Área Curricular</dt>
                        <dd class="col-sm-9 small">{{ $momento->area_curricular }}</dd>
                        @endif
                        @if($momento->competencias_especificas)
                        <dt class="col-sm-3 small">Competencias</dt>
                        <dd class="col-sm-9 small" style="white-space:pre-wrap">{{ $momento->competencias_especificas }}</dd>
                        @endif
                        @if($momento->contenidos)
                        <dt class="col-sm-3 small">Contenidos</dt>
                        <dd class="col-sm-9 small" style="white-space:pre-wrap">{{ $momento->contenidos }}</dd>
                        @endif
                        @if($momento->actividades)
                        <dt class="col-sm-3 small">Actividades</dt>
                        <dd class="col-sm-9 small" style="white-space:pre-wrap">{{ $momento->actividades }}</dd>
                        @endif
                        @if($momento->indicador_logro)
                        <dt class="col-sm-3 small">Indicador de Logro</dt>
                        <dd class="col-sm-9 small" style="white-space:pre-wrap">{{ $momento->indicador_logro }}</dd>
                        @endif
                        @if($momento->recursos)
                        <dt class="col-sm-3 small">Recursos</dt>
                        <dd class="col-sm-9 small" style="white-space:pre-wrap">{{ $momento->recursos }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
            @endif
            @endforeach

        </div>

        <div class="col-lg-4">
            @if($plan->estrategias_nombres && count($plan->estrategias_nombres))
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-lightbulb me-1"></i>Estrategias Didácticas</div>
                <div class="card-body">
                    @foreach($plan->estrategias_nombres as $nombre)
                        <span class="badge bg-light text-dark border mb-1">{{ $nombre }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($plan->tieneArchivo())
            @php
                $ext      = strtolower(pathinfo($plan->archivo_nombre, PATHINFO_EXTENSION));
                $fileUrl  = Storage::disk('public')->url($plan->archivo_path);
                $isPdf    = $ext === 'pdf';
                $isImage  = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                $isOffice = in_array($ext, ['doc','docx','ppt','pptx','xls','xlsx']);
                $viewerUrl = $isOffice
                    ? 'https://view.officeapps.live.com/op/embed.aspx?src=' . urlencode(url(Storage::url($plan->archivo_path)))
                    : null;
            @endphp
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span class="fw-semibold"><i class="bi bi-paperclip me-1"></i>Archivo Adjunto</span>
                    <a href="{{ route('admin.planes-clase.download', $plan) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-download me-1"></i> Descargar
                    </a>
                </div>
                <div class="card-body p-0">
                    @if($isPdf)
                        <iframe src="{{ $fileUrl }}"
                                style="width:100%;height:500px;border:0;"
                                loading="lazy"
                                title="{{ $plan->archivo_nombre }}">
                            <p class="p-3 text-muted small">
                                <a href="{{ route('admin.planes-clase.download', $plan) }}">Descargar PDF</a>
                            </p>
                        </iframe>
                    @elseif($isImage)
                        <div class="text-center p-3">
                            <img src="{{ $fileUrl }}" alt="{{ $plan->archivo_nombre }}"
                                 class="img-fluid rounded" style="max-height:500px;">
                        </div>
                    @elseif($isOffice && $viewerUrl)
                        <iframe src="{{ $viewerUrl }}"
                                style="width:100%;height:500px;border:0;"
                                loading="lazy"
                                title="{{ $plan->archivo_nombre }}">
                        </iframe>
                        <div class="p-2 text-center">
                            <small class="text-muted">Si no carga,
                                <a href="{{ route('admin.planes-clase.download', $plan) }}">descarga el archivo</a>.
                            </small>
                        </div>
                    @else
                        <div class="p-4 text-center text-muted">
                            <i class="bi bi-file-earmark-x display-5 d-block mb-2"></i>
                            Vista previa no disponible.
                            <div class="mt-3">
                                <a href="{{ route('admin.planes-clase.download', $plan) }}" class="btn btn-primary btn-sm">
                                    <i class="bi bi-download me-1"></i> Descargar {{ strtoupper($ext) }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            @if($plan->observacion)
            <div class="card shadow-sm mb-4">
                <div class="card-header fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Observación</div>
                <div class="card-body small" style="white-space:pre-wrap">{{ $plan->observacion }}</div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
