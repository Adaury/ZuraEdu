@extends('layouts.admin')

@section('page-title', 'Seguimiento Social')

@section('content')

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}" class="text-decoration-none">Dashboard</a></li>
        <li class="breadcrumb-item active">Seguimiento Social</li>
    </ol>
</nav>

<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h1 class="h4 fw-bold mb-0" style="color:var(--primary);">
            <i class="bi bi-people-fill me-2"></i>Seguimiento Social
        </h1>
        <p class="text-muted mb-0 mt-1" style="font-size:.82rem;">Gestión de casos y seguimiento de estudiantes</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.seguimiento-social.lista-pdf', request()->query()) }}"
           class="btn btn-outline-danger fw-semibold"
           style="border-radius:8px;padding:.45rem 1rem;font-size:.85rem;">
            <i class="bi bi-file-earmark-pdf me-1"></i>PDF
        </a>
        <a href="{{ route('admin.seguimiento-social.lista-excel', request()->query()) }}"
           class="btn btn-outline-success fw-semibold"
           style="border-radius:8px;padding:.45rem 1rem;font-size:.85rem;">
            <i class="bi bi-file-earmark-excel me-1"></i>Excel
        </a>
        <a href="{{ route('admin.seguimiento-social.create') }}"
           class="btn fw-semibold"
           style="background:var(--primary);color:#fff;border-radius:8px;padding:.45rem 1.1rem;font-size:.85rem;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Caso
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 mb-4" style="border-radius:10px;font-size:.85rem;">
        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    </div>
@endif

{{-- Tarjetas resumen --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-3">
                <div class="text-uppercase fw-bold mb-1" style="font-size:.63rem;letter-spacing:.08em;color:#6b7280;">Abiertos</div>
                <div class="fw-bold" style="font-size:2rem;color:#3b82f6;line-height:1.1;">{{ $totales['abiertos'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-3">
                <div class="text-uppercase fw-bold mb-1" style="font-size:.63rem;letter-spacing:.08em;color:#6b7280;">En Seguimiento</div>
                <div class="fw-bold" style="font-size:2rem;color:#6366f1;line-height:1.1;">{{ $totales['en_seguimiento'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-3">
                <div class="text-uppercase fw-bold mb-1" style="font-size:.63rem;letter-spacing:.08em;color:#6b7280;">Cerrados</div>
                <div class="fw-bold" style="font-size:2rem;color:#6b7280;line-height:1.1;">{{ $totales['cerrados'] }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm h-100" style="border-radius:12px;">
            <div class="card-body p-3">
                <div class="text-uppercase fw-bold mb-1" style="font-size:.63rem;letter-spacing:.08em;color:#6b7280;">Críticos Activos</div>
                <div class="fw-bold" style="font-size:2rem;color:#ef4444;line-height:1.1;">{{ $totales['criticos'] }}</div>
            </div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('admin.seguimiento-social.index') }}" class="row g-2 align-items-end">
            <div class="col-12 col-md">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#374151;">Buscar estudiante</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Nombre o matrícula…"
                       class="form-control form-control-sm" style="border-radius:7px;">
            </div>
            <div class="col-6 col-sm-4 col-md-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#374151;">Estado</label>
                <select name="estado" class="form-select form-select-sm" style="border-radius:7px;min-width:130px;">
                    <option value="">Todos</option>
                    <option value="abierto"        @selected(request('estado') === 'abierto')>Abierto</option>
                    <option value="en_seguimiento" @selected(request('estado') === 'en_seguimiento')>En Seguimiento</option>
                    <option value="cerrado"        @selected(request('estado') === 'cerrado')>Cerrado</option>
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#374151;">Tipo</label>
                <select name="tipo" class="form-select form-select-sm" style="border-radius:7px;min-width:120px;">
                    <option value="">Todos</option>
                    @foreach(\App\Models\CasoSeguimiento::TIPOS as $val => $lbl)
                        <option value="{{ $val }}" @selected(request('tipo') === $val)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-sm-4 col-md-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#374151;">Nivel de Riesgo</label>
                <select name="nivel_riesgo" class="form-select form-select-sm" style="border-radius:7px;min-width:130px;">
                    <option value="">Todos</option>
                    @foreach(\App\Models\CasoSeguimiento::NIVELES_RIESGO as $val => $info)
                        <option value="{{ $val }}" @selected(request('nivel_riesgo') === $val)>{{ $info['label'] }}</option>
                    @endforeach
                </select>
            </div>
            @if($responsables->isNotEmpty())
            <div class="col-6 col-sm-4 col-md-auto">
                <label class="form-label mb-1" style="font-size:.75rem;font-weight:600;color:#374151;">Responsable</label>
                <select name="responsable_id" class="form-select form-select-sm" style="border-radius:7px;min-width:140px;">
                    <option value="">Todos</option>
                    @foreach($responsables as $resp)
                        <option value="{{ $resp->id }}" @selected(request('responsable_id') == $resp->id)>
                            {{ $resp->nombre_completo }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-auto">
                <button type="submit" class="btn btn-sm fw-semibold"
                        style="background:var(--primary);color:#fff;border-radius:7px;">
                    <i class="bi bi-search me-1"></i>Filtrar
                </button>
                <a href="{{ route('admin.seguimiento-social.index') }}"
                   class="btn btn-sm btn-outline-secondary ms-1 fw-semibold" style="border-radius:7px;">
                    Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card border-0 shadow-sm" style="border-radius:12px;overflow:hidden;">
    @if($casos->isEmpty())
        <div class="card-body text-center py-5">
            <i class="bi bi-folder2-open" style="font-size:2.8rem;color:#d1d5db;display:block;margin-bottom:.75rem;"></i>
            <div class="fw-bold text-muted mb-1">No hay casos registrados</div>
            <div class="text-muted" style="font-size:.82rem;">Crea el primer caso con el botón "Nuevo Caso"</div>
        </div>
    @else
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:.83rem;">
            <thead style="background:#f8faff;border-bottom:2px solid #e5e7eb;">
                <tr>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">#</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Estudiante</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Tipo</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Riesgo</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Estado</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Responsable</th>
                    <th class="py-3 px-3 fw-bold text-uppercase" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Apertura</th>
                    <th class="py-3 px-3 fw-bold text-uppercase text-center" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Interv.</th>
                    <th class="py-3 px-3 fw-bold text-uppercase text-end" style="font-size:.62rem;letter-spacing:.08em;color:#6b7280;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($casos as $caso)
                @php
                    $nivelInfo  = $caso->nivel_riesgo_info;
                    $estadoInfo = $caso->estado_info;
                    $nivelStyle = [
                        'green'  => 'background:#dcfce7;color:#15803d;',
                        'yellow' => 'background:#fef9c3;color:#854d0e;',
                        'orange' => 'background:#ffedd5;color:#c2410c;',
                        'red'    => 'background:#fee2e2;color:#b91c1c;',
                    ];
                    $estadoStyle = [
                        'blue'   => 'background:#dbeafe;color:#1d4ed8;',
                        'indigo' => 'background:#e0e7ff;color:#4338ca;',
                        'gray'   => 'background:#f3f4f6;color:#374151;',
                    ];
                @endphp
                <tr>
                    <td class="py-3 px-3" style="color:#9ca3af;font-family:monospace;font-size:.75rem;">#{{ $caso->id }}</td>
                    <td class="py-3 px-3">
                        <div class="fw-semibold" style="color:#1e293b;">{{ $caso->estudiante->nombre_completo ?? '—' }}</div>
                        <div style="font-size:.72rem;color:#9ca3af;">{{ $caso->estudiante->numero_matricula ?? '' }}</div>
                    </td>
                    <td class="py-3 px-3" style="color:#374151;">{{ $caso->tipo_label }}</td>
                    <td class="py-3 px-3">
                        <span class="badge rounded-pill fw-semibold"
                              style="font-size:.7rem;{{ $nivelStyle[$nivelInfo['color']] ?? 'background:#f3f4f6;color:#374151;' }}">
                            {{ $nivelInfo['label'] }}
                        </span>
                    </td>
                    <td class="py-3 px-3">
                        <span class="badge rounded-pill fw-semibold"
                              style="font-size:.7rem;{{ $estadoStyle[$estadoInfo['color']] ?? 'background:#f3f4f6;color:#374151;' }}">
                            {{ $estadoInfo['label'] }}
                        </span>
                    </td>
                    <td class="py-3 px-3" style="font-size:.78rem;color:#374151;">{{ $caso->responsable->nombre_completo ?? '—' }}</td>
                    <td class="py-3 px-3" style="font-size:.78rem;color:#374151;white-space:nowrap;">
                        {{ $caso->fecha_apertura?->format('d/m/Y') ?? '—' }}
                    </td>
                    <td class="py-3 px-3 text-center">
                        <span class="badge rounded-pill fw-bold"
                              style="background:#e0e7ff;color:#4338ca;font-size:.72rem;">
                            {{ $caso->intervenciones_count }}
                        </span>
                    </td>
                    <td class="py-3 px-3">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('admin.seguimiento-social.show', $caso) }}"
                               class="btn btn-sm fw-semibold"
                               style="background:#dbeafe;color:#1d4ed8;border:none;font-size:.72rem;padding:.25rem .65rem;border-radius:6px;">
                                Ver
                            </a>
                            <a href="{{ route('admin.seguimiento-social.informe-pdf', $caso) }}"
                               target="_blank"
                               class="btn btn-sm fw-semibold"
                               style="background:#f3f4f6;color:#374151;border:none;font-size:.72rem;padding:.25rem .65rem;border-radius:6px;">
                                PDF
                            </a>
                            <form method="POST" action="{{ route('admin.seguimiento-social.destroy', $caso) }}"
                                  onsubmit="return confirm('¿Eliminar este caso y todas sus intervenciones?')" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm fw-semibold"
                                        style="background:#fee2e2;color:#b91c1c;border:none;font-size:.72rem;padding:.25rem .65rem;border-radius:6px;">
                                    Eliminar
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($casos->hasPages())
        <div class="px-3 py-2 border-top" style="font-size:.82rem;">
            {{ $casos->links() }}
        </div>
    @endif
    @endif
</div>

@endsection
