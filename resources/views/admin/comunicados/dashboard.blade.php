@extends('layouts.admin')

@section('title', 'Dashboard Comunicados')

@section('content')
<div class="container-fluid px-4 py-3">

    {{-- Cabecera --}}
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-0 text-dark">
                <i class="bi bi-megaphone-fill me-2 text-primary"></i>Comunicados
            </h4>
            <p class="text-muted mb-0 small">Resumen ejecutivo • {{ now()->translatedFormat('d F Y') }}</p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.comunicados.index') }}"
               class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-list-ul me-1"></i>Lista
            </a>
            <a href="{{ route('admin.comunicados.create') }}"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-lg me-1"></i>Nuevo comunicado
            </a>
            <a href="{{ route('admin.comunicados.lista-excel') }}"
               class="btn btn-outline-success btn-sm">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.comunicados.lista-pdf') }}"
               class="btn btn-outline-danger btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="row g-3 mb-4">
        {{-- Total --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #3b82f6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Total Comunicados</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#dbeafe">
                            <i class="bi bi-megaphone-fill text-primary"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-dark">{{ number_format($total) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Historial completo</div>
                </div>
            </div>
        </div>

        {{-- Publicados --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #10b981!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Publicados activos</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#d1fae5">
                            <i class="bi bi-send-check-fill text-success"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-success">{{ number_format($publicados) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Visibles ahora</div>
                </div>
            </div>
        </div>

        {{-- Lecturas --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #8b5cf6!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Lecturas registradas</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#ede9fe">
                            <i class="bi bi-eye-fill" style="color:#8b5cf6"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3" style="color:#8b5cf6">{{ number_format($totalLecturas) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Confirmaciones de lectura</div>
                </div>
            </div>
        </div>

        {{-- Últimos 30 días --}}
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm h-100" style="border-left:4px solid #f59e0b!important">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted small">Últimos 30 días</span>
                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                             style="width:36px;height:36px;background:#fef3c7">
                            <i class="bi bi-calendar-week-fill text-warning"></i>
                        </div>
                    </div>
                    <div class="fw-bold fs-3 text-warning">{{ number_format($recientes30) }}</div>
                    <div class="text-muted" style="font-size:.78rem">Nuevos comunicados</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        {{-- Por tipo destinatarios --}}
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-people-fill me-2 text-primary"></i>Distribución por destinatarios
                    </h6>
                </div>
                <div class="card-body pt-3">
                    @php
                        $tiposLabel = [
                            'todos'         => ['label'=>'Todos',          'color'=>'#3b82f6','bg'=>'#dbeafe','icon'=>'bi-globe'],
                            'docentes'      => ['label'=>'Docentes',        'color'=>'#10b981','bg'=>'#d1fae5','icon'=>'bi-person-workspace'],
                            'coordinadores' => ['label'=>'Coordinadores',   'color'=>'#f59e0b','bg'=>'#fef3c7','icon'=>'bi-people'],
                            'grupo'         => ['label'=>'Grupo específico','color'=>'#8b5cf6','bg'=>'#ede9fe','icon'=>'bi-collection'],
                        ];
                        $totalTipos = $porTipo->sum() ?: 1;
                    @endphp
                    @foreach($tiposLabel as $clave => $info)
                    @php
                        $count = $porTipo[$clave] ?? 0;
                        $pct   = round($count / $totalTipos * 100);
                    @endphp
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge rounded-2 small"
                                  style="background:{{ $info['bg'] }};color:{{ $info['color'] }}">
                                <i class="bi {{ $info['icon'] }} me-1"></i>{{ $info['label'] }}
                            </span>
                            <span class="small text-muted">{{ $count }} ({{ $pct }}%)</span>
                        </div>
                        <div class="progress" style="height:8px;border-radius:4px">
                            <div class="progress-bar" role="progressbar"
                                 style="width:{{ $pct }}%;background:{{ $info['color'] }};border-radius:4px">
                            </div>
                        </div>
                    </div>
                    @endforeach

                    <hr class="my-3">

                    {{-- Interno vs externo --}}
                    <div class="d-flex gap-3 justify-content-center">
                        <div class="text-center">
                            <div class="fw-bold fs-5 text-primary">{{ $internos }}</div>
                            <div class="text-muted small"><i class="bi bi-lock-fill me-1"></i>Internos</div>
                        </div>
                        <div class="vr"></div>
                        <div class="text-center">
                            <div class="fw-bold fs-5 text-success">{{ $externos }}</div>
                            <div class="text-muted small"><i class="bi bi-globe me-1"></i>Externos</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Más leídos --}}
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pb-0">
                    <h6 class="fw-semibold mb-0">
                        <i class="bi bi-trophy-fill me-2 text-warning"></i>Comunicados más leídos
                    </h6>
                </div>
                <div class="card-body p-0">
                    @php $maxL = $masLeidos->max('lecturas_count') ?: 1; @endphp
                    <ul class="list-group list-group-flush">
                        @forelse($masLeidos as $i => $com)
                        @php $pctL = round($com->lecturas_count / $maxL * 100); @endphp
                        <li class="list-group-item px-3 py-2">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <div class="d-flex align-items-center gap-2 flex-fill">
                                    <span class="badge text-white rounded-circle"
                                          style="background:{{ ['#f59e0b','#94a3b8','#cd7f32','#6b7280','#6b7280'][$i] ?? '#6b7280' }};width:22px;height:22px;font-size:.7rem;display:flex;align-items:center;justify-content:center">
                                        {{ $i + 1 }}
                                    </span>
                                    <span class="fw-semibold small text-truncate" style="max-width:260px">
                                        {{ $com->titulo }}
                                    </span>
                                </div>
                                <span class="badge bg-primary-subtle text-primary rounded-pill ms-2">
                                    <i class="bi bi-eye me-1"></i>{{ $com->lecturas_count }}
                                </span>
                            </div>
                            <div class="progress" style="height:4px;border-radius:2px">
                                <div class="progress-bar bg-primary" style="width:{{ $pctL }}%"></div>
                            </div>
                        </li>
                        @empty
                        <li class="list-group-item text-muted text-center py-4 small">Sin lecturas registradas aún.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Últimos comunicados --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-semibold mb-0">
                <i class="bi bi-clock-history me-2 text-secondary"></i>Últimos comunicados publicados
            </h6>
            <a href="{{ route('admin.comunicados.index') }}" class="btn btn-link btn-sm p-0 text-muted">Ver todos</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0" style="font-size:.82rem">
                    <thead class="table-light">
                        <tr>
                            <th>Título</th>
                            <th>Autor</th>
                            <th>Destinatarios</th>
                            <th>Lecturas</th>
                            <th>Publicado</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recientes as $com)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ \Illuminate\Support\Str::limit($com->titulo, 45) }}</span>
                                @if($com->es_interno)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size:.65rem">Interno</span>
                                @endif
                            </td>
                            <td>{{ $com->autor?->name ?? '—' }}</td>
                            <td>
                                @php
                                    $tl = $tiposLabel[$com->tipo_destinatarios] ?? ['label'=>$com->tipo_destinatarios,'color'=>'#6b7280','bg'=>'#f1f5f9','icon'=>'bi-person'];
                                @endphp
                                <span class="badge rounded-2 small"
                                      style="background:{{ $tl['bg'] }};color:{{ $tl['color'] }}">
                                    <i class="bi {{ $tl['icon'] }} me-1"></i>{{ $tl['label'] }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-light text-secondary">
                                    <i class="bi bi-eye me-1"></i>{{ $com->lecturas_count }}
                                </span>
                            </td>
                            <td>{{ $com->published_at?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if($com->es_publicado)
                                <span class="badge bg-success-subtle text-success">Activo</span>
                                @else
                                <span class="badge bg-secondary-subtle text-secondary">Inactivo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.comunicados.edit', $com->id) }}"
                                   class="btn btn-link btn-sm p-0 text-muted">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Sin comunicados registrados.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Acciones rápidas --}}
    <div class="row g-3">
        @php
        $actions = [
            ['icon'=>'bi-plus-circle-fill','color'=>'#3b82f6','bg'=>'#dbeafe','label'=>'Nuevo comunicado','href'=>route('admin.comunicados.create')],
            ['icon'=>'bi-megaphone-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Mis comunicados','href'=>route('admin.comunicados.mis')],
            ['icon'=>'bi-file-earmark-excel-fill','color'=>'#10b981','bg'=>'#d1fae5','label'=>'Exportar Excel','href'=>route('admin.comunicados.lista-excel')],
            ['icon'=>'bi-envelope-fill','color'=>'#8b5cf6','bg'=>'#ede9fe','label'=>'Mensajes internos','href'=>route('admin.mensajes.index')],
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
