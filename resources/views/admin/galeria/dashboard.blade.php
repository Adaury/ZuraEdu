@extends('layouts.admin')

@section('title', 'Dashboard Galería')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-images me-2" style="color:#8b5cf6"></i>Galería Institucional
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.galeria.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-grid me-1"></i>Ver álbumes
            </a>
            <a href="{{ route('admin.galeria.create') }}"
               class="btn btn-sm" style="background:#8b5cf6;color:white">
                <i class="bi bi-plus-lg me-1"></i>Nuevo álbum
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total Álbumes</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-collection-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ $totalAlbumes }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ $activos }} activos</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total Fotos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-image-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-primary">{{ $totalFotos }}</div>
                    <div class="text-muted" style="font-size:.78rem">Archivo multimedia</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Fotos este mes</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-calendar-check-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ $fotosEsteMes }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ now()->translatedFormat('F') }}</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Fotos este año</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-graph-up text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-warning">{{ $fotosAnio }}</div>
                    <div class="text-muted" style="font-size:.78rem">{{ now()->year }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        {{-- Álbumes más grandes --}}
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Álbumes con más fotos
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxF = $albumesMasFotos->max('fotos_count') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($albumesMasFotos as $i => $album)
                        @php $pct = round($album->fotos_count / $maxF * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <div>
                                        <div class="fw-semibold small text-truncate" style="max-width:160px">
                                            {{ $album->titulo }}
                                        </div>
                                    </div>
                                </div>
                                <span class="badge bg-primary-subtle text-primary rounded-pill">
                                    <i class="bi bi-image me-1"></i>{{ $album->fotos_count }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar" style="width:{{ $pct }}%;background:#8b5cf6"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin álbumes aún.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>

        {{-- Álbumes recientes --}}
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0 d-flex justify-content-between align-items-center">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-clock-history me-2 text-secondary"></i>Álbumes recientes
                    </h6>
                    <a href="{{ route('admin.galeria.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                            <thead class="table-light">
                                <tr>
                                    <th>Álbum</th>
                                    <th class="text-center">Fotos</th>
                                    <th>Creado</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($albumesRecien as $album)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($album->portada)
                                            <img src="{{ Storage::disk('public')->url($album->portada) }}"
                                                 class="rounded" width="36" height="36"
                                                 style="object-fit:cover" alt="">
                                            @else
                                            <div class="rounded d-flex align-items-center justify-content-center"
                                                 style="width:36px;height:36px;background:#ede9fe">
                                                <i class="bi bi-images" style="color:#8b5cf6;font-size:.9rem"></i>
                                            </div>
                                            @endif
                                            <div>
                                                <div class="fw-semibold">{{ \Illuminate\Support\Str::limit($album->titulo, 35) }}</div>
                                                @if($album->descripcion)
                                                <div class="text-muted" style="font-size:.72rem">{{ \Illuminate\Support\Str::limit($album->descripcion, 40) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-secondary">
                                            <i class="bi bi-image me-1"></i>{{ $album->fotos_count }}
                                        </span>
                                    </td>
                                    <td>{{ $album->created_at->format('d/m/Y') }}</td>
                                    <td>
                                        @if($album->activo)
                                        <span class="badge bg-success-subtle text-success">Activo</span>
                                        @else
                                        <span class="badge bg-secondary-subtle text-secondary">Oculto</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.galeria.show', $album->id) }}"
                                           class="btn btn-link btn-sm p-0 text-muted me-1">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.galeria.edit', $album->id) }}"
                                           class="btn btn-link btn-sm p-0 text-muted">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Sin álbumes creados aún.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="row g-3 mt-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#8b5cf6','bg'=>'#ede9fe','label'=>'Nuevo álbum','href'=>route('admin.galeria.create')],
            ['icon'=>'bi-grid-fill','color'=>'#3b82f6','bg'=>'#dbeafe','label'=>'Ver galería','href'=>route('admin.galeria.index')],
        ];
        @endphp
        @foreach($actions as $a)
        <div class="col-6 col-md-3">
            <a href="{{ $a['href'] }}" class="card border-0 shadow-sm text-decoration-none h-100">
                <div class="card-body p-3 d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                         style="width:42px;height:42px;background:{{ $a['bg'] }}">
                        <i class="bi {{ $a['icon'] }} fs-5" style="color:{{ $a['color'] }}"></i>
                    </div>
                    <span class="fw-semibold text-dark small">{{ $a['label'] }}</span>
                </div>
            </a>
        </div>
        @endforeach
    </div>

</div>
@endsection
