@extends('layouts.portal')

@section('page-title', 'Mis Estudiantes — Portal Docente')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    <div class="prt-sidebar-section">Mi Portal</div>
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-sidebar-link">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.mis-estadisticas') }}" class="prt-sidebar-link">
        <i class="bi bi-bar-chart-fill"></i>Mis Estadísticas
    </a>
    <a href="{{ route('portal.docente.mis-planificaciones') }}" class="prt-sidebar-link">
        <i class="bi bi-journal-text"></i>Mis Planificaciones
    </a>
    <a href="{{ route('portal.docente.mis-estudiantes') }}" class="prt-sidebar-link active">
        <i class="bi bi-people-fill"></i>Mis Estudiantes
    </a>
    @if(auth()->user()->hasAnyRole(['Administrador','Director','Coordinador Académico','Coordinador Primer Ciclo','Coordinador Segundo Ciclo']))
    <div class="prt-sidebar-section mt-2">Dirección</div>
    <a href="{{ route('admin.ejecutivo.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.ejecutivo*') ? 'active' : '' }}">
        <i class="bi bi-bar-chart-line-fill" style="color:#f59e0b;"></i>Dashboard Ejecutivo
    </a>
    <a href="{{ route('admin.rubricas.index') }}" class="prt-sidebar-link {{ request()->routeIs('admin.rubricas*') ? 'active' : '' }}">
        <i class="bi bi-grid-3x3-gap-fill"></i>Rúbricas
    </a>
    @endif
    <div class="prt-sidebar-section mt-2">Cuenta</div>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="prt-sidebar-link w-100 border-0" style="cursor:pointer;text-align:left;">
            <i class="bi bi-box-arrow-right" style="color:#ef4444;"></i>Cerrar sesión
        </button>
    </form>
@endsection

@section('content')

{{-- Encabezado --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Mis Estudiantes</h1>
        <div style="font-size:.75rem;color:#64748b;">
            {{ $docente->nombre_completo }} &middot; {{ $schoolYear?->nombre ?? 'Sin año escolar' }}
        </div>
    </div>
    <div style="font-size:.8rem;color:#64748b;font-weight:600;">
        {{ $matriculas->count() }} {{ $matriculas->count() === 1 ? 'estudiante' : 'estudiantes' }}
    </div>
</div>

{{-- Tarjetas resumen --}}
<div class="prt-stats" style="margin-bottom:1.25rem;">
    @php
        $total   = $matriculas->count();
        $rojos   = $matriculas->where('_semaforo','rojo')->count();
        $amarillos = $matriculas->where('_semaforo','amarillo')->count();
        $verdes  = $matriculas->where('_semaforo','verde')->count();
        $promedioGlobal = $matriculas->filter(fn($m) => $m->_promedio !== null)->avg('_promedio');
    @endphp
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#ede9fe;color:#5b21b6;"><i class="bi bi-people-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $total }}</div>
            <div class="prt-stat-lbl">Total</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dcfce7;color:#15803d;"><i class="bi bi-check-circle-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $verdes }}</div>
            <div class="prt-stat-lbl">En buen estado</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fef9c3;color:#b45309;"><i class="bi bi-exclamation-circle-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $amarillos }}</div>
            <div class="prt-stat-lbl">En seguimiento</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#fee2e2;color:#b91c1c;"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $rojos }}</div>
            <div class="prt-stat-lbl">En riesgo</div>
        </div>
    </div>
    <div class="prt-stat">
        <div class="prt-stat-icon" style="background:#dbeafe;color:#1d4ed8;"><i class="bi bi-star-fill"></i></div>
        <div>
            <div class="prt-stat-val">{{ $promedioGlobal !== null ? number_format($promedioGlobal, 1) : '—' }}</div>
            <div class="prt-stat-lbl">Promedio general</div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div x-data="{ open: false }" style="margin-bottom:1rem;">
    <form method="GET" action="{{ route('portal.docente.mis-estudiantes') }}"
          style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:flex-end;">

        {{-- Búsqueda por nombre --}}
        <div style="flex:1;min-width:180px;">
            <label style="font-size:.72rem;font-weight:600;color:#475569;display:block;margin-bottom:.2rem;">Buscar estudiante</label>
            <input type="text" name="q" value="{{ $filtroBusqueda }}"
                   placeholder="Nombre o apellido…"
                   style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;">
        </div>

        {{-- Filtro por asignación --}}
        <div style="min-width:200px;">
            <label style="font-size:.72rem;font-weight:600;color:#475569;display:block;margin-bottom:.2rem;">Asignatura</label>
            <select name="asignacion_id"
                    style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;">
                <option value="">Todas las asignaturas</option>
                @foreach($asignaciones as $asig)
                    <option value="{{ $asig->id }}" {{ $filtroAsignacion == $asig->id ? 'selected' : '' }}>
                        {{ $asig->asignatura?->nombre }} — {{ $asig->grupo?->nombre_completo }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Filtro por grupo --}}
        <div style="min-width:160px;">
            <label style="font-size:.72rem;font-weight:600;color:#475569;display:block;margin-bottom:.2rem;">Grupo</label>
            <select name="grupo_id"
                    style="width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;">
                <option value="">Todos los grupos</option>
                @foreach($asignaciones->pluck('grupo')->unique('id')->filter() as $grp)
                    <option value="{{ $grp->id }}" {{ $filtroGrupo == $grp->id ? 'selected' : '' }}>
                        {{ $grp->nombre_completo ?? $grp->nombre_corto }}
                    </option>
                @endforeach
            </select>
        </div>

        <div style="display:flex;gap:.4rem;">
            <button type="submit"
                    style="background:#1e3a6e;color:#fff;border:none;border-radius:8px;padding:.42rem 1rem;font-size:.82rem;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-search"></i>Filtrar
            </button>
            <a href="{{ route('portal.docente.mis-estudiantes') }}"
               style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.42rem .9rem;font-size:.82rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-x-circle"></i>Limpiar
            </a>
        </div>
    </form>
</div>

{{-- Leyenda semáforo --}}
<div style="display:flex;gap:1rem;margin-bottom:.75rem;flex-wrap:wrap;">
    <span style="font-size:.73rem;display:flex;align-items:center;gap:.3rem;">
        <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
        Verde: Promedio ≥ 75 y asistencia ≥ 80%
    </span>
    <span style="font-size:.73rem;display:flex;align-items:center;gap:.3rem;">
        <span style="width:10px;height:10px;border-radius:50%;background:#f59e0b;display:inline-block;"></span>
        Amarillo: Promedio 65–74 o asistencia 70–79%
    </span>
    <span style="font-size:.73rem;display:flex;align-items:center;gap:.3rem;">
        <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;display:inline-block;"></span>
        Rojo: Promedio < 65 o asistencia < 70%
    </span>
</div>

{{-- Tabla --}}
@if($matriculas->isEmpty())
    <div style="background:#f8fafc;border:1px dashed #cbd5e1;border-radius:12px;padding:2.5rem;text-align:center;color:#94a3b8;">
        <i class="bi bi-people" style="font-size:2rem;display:block;margin-bottom:.5rem;"></i>
        <div style="font-weight:600;">Sin estudiantes</div>
        <div style="font-size:.8rem;margin-top:.25rem;">No se encontraron estudiantes con los filtros aplicados.</div>
    </div>
@else
    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:.82rem;">
            <thead>
                <tr style="background:#1e3a6e;color:#fff;">
                    <th style="padding:.6rem .75rem;text-align:left;border-radius:8px 0 0 0;">#</th>
                    <th style="padding:.6rem .75rem;text-align:left;">Estudiante</th>
                    <th style="padding:.6rem .75rem;text-align:left;">Grupo</th>
                    <th style="padding:.6rem .75rem;text-align:center;">Promedio</th>
                    <th style="padding:.6rem .75rem;text-align:center;">Asistencia</th>
                    <th style="padding:.6rem .75rem;text-align:center;">Estado</th>
                    <th style="padding:.6rem .75rem;text-align:center;border-radius:0 8px 0 0;">Alertas</th>
                </tr>
            </thead>
            <tbody>
                @foreach($matriculas as $i => $mat)
                    @php
                        $est   = $mat->estudiante;
                        $color = match($mat->_semaforo) {
                            'rojo'     => ['bg' => '#fee2e2', 'dot' => '#ef4444', 'label' => 'En riesgo'],
                            'amarillo' => ['bg' => '#fef9c3', 'dot' => '#f59e0b', 'label' => 'Seguimiento'],
                            default    => ['bg' => '#f0fdf4', 'dot' => '#22c55e', 'label' => 'Normal'],
                        };
                        $rowBg = $mat->_semaforo === 'rojo' ? '#fff5f5' : ($i % 2 === 0 ? '#fff' : '#f8fafc');
                    @endphp
                    <tr style="background:{{ $rowBg }};border-bottom:1px solid #e2e8f0;">
                        {{-- Número --}}
                        <td style="padding:.55rem .75rem;color:#94a3b8;font-size:.75rem;">{{ $i + 1 }}</td>

                        {{-- Avatar + Nombre --}}
                        <td style="padding:.55rem .75rem;">
                            <div style="display:flex;align-items:center;gap:.6rem;">
                                {{-- Avatar --}}
                                @if($est?->foto)
                                    <img src="{{ asset('storage/' . $est->foto) }}" alt=""
                                         style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid {{ $color['dot'] }};">
                                @else
                                    <div style="width:34px;height:34px;border-radius:50%;background:{{ $color['bg'] }};border:2px solid {{ $color['dot'] }};display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;color:{{ $color['dot'] }};">
                                        {{ strtoupper(substr($est?->nombres ?? '?', 0, 1)) }}{{ strtoupper(substr($est?->apellidos ?? '', 0, 1)) }}
                                    </div>
                                @endif
                                <div>
                                    <div style="font-weight:700;color:#1e293b;line-height:1.2;">
                                        {{ $est?->apellidos }}, {{ $est?->nombres }}
                                    </div>
                                    @if($est?->cedula)
                                        <div style="font-size:.72rem;color:#94a3b8;">{{ $est->cedula }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>

                        {{-- Grupo --}}
                        <td style="padding:.55rem .75rem;">
                            @php $grp = $mat->_grupo; @endphp
                            <span style="background:#ede9fe;color:#5b21b6;border-radius:6px;padding:.15rem .5rem;font-size:.75rem;font-weight:600;">
                                {{ $grp?->nombre_completo ?? $grp?->nombre_corto ?? '—' }}
                            </span>
                        </td>

                        {{-- Promedio --}}
                        <td style="padding:.55rem .75rem;text-align:center;">
                            @if($mat->_promedio !== null)
                                @php
                                    $notaColor = $mat->_promedio >= 75 ? '#15803d' : ($mat->_promedio >= 65 ? '#b45309' : '#b91c1c');
                                    $notaBg    = $mat->_promedio >= 75 ? '#dcfce7' : ($mat->_promedio >= 65 ? '#fef9c3' : '#fee2e2');
                                @endphp
                                <span style="background:{{ $notaBg }};color:{{ $notaColor }};border-radius:6px;padding:.2rem .6rem;font-weight:700;font-size:.85rem;">
                                    {{ number_format($mat->_promedio, 1) }}
                                </span>
                            @else
                                <span style="color:#94a3b8;font-size:.78rem;">Sin notas</span>
                            @endif
                        </td>

                        {{-- Asistencia --}}
                        <td style="padding:.55rem .75rem;text-align:center;">
                            @if($mat->_asist !== null)
                                @php
                                    $asistColor = $mat->_asist >= 80 ? '#15803d' : ($mat->_asist >= 70 ? '#b45309' : '#b91c1c');
                                    $asistBg    = $mat->_asist >= 80 ? '#dcfce7' : ($mat->_asist >= 70 ? '#fef9c3' : '#fee2e2');
                                @endphp
                                <div style="display:flex;flex-direction:column;align-items:center;gap:.2rem;">
                                    <span style="background:{{ $asistBg }};color:{{ $asistColor }};border-radius:6px;padding:.2rem .6rem;font-weight:700;font-size:.85rem;">
                                        {{ $mat->_asist }}%
                                    </span>
                                    {{-- Mini barra de progreso --}}
                                    <div style="width:60px;height:4px;background:#e2e8f0;border-radius:9999px;overflow:hidden;">
                                        <div style="width:{{ min($mat->_asist, 100) }}%;height:100%;background:{{ $asistColor }};border-radius:9999px;"></div>
                                    </div>
                                </div>
                            @else
                                <span style="color:#94a3b8;font-size:.78rem;">Sin datos</span>
                            @endif
                        </td>

                        {{-- Semáforo --}}
                        <td style="padding:.55rem .75rem;text-align:center;">
                            <span style="display:inline-flex;align-items:center;gap:.3rem;background:{{ $color['bg'] }};color:{{ $color['dot'] }};border-radius:20px;padding:.2rem .7rem;font-size:.75rem;font-weight:700;">
                                <span style="width:8px;height:8px;border-radius:50%;background:{{ $color['dot'] }};display:inline-block;"></span>
                                {{ $color['label'] }}
                            </span>
                        </td>

                        {{-- Alertas --}}
                        <td style="padding:.55rem .75rem;text-align:center;">
                            @if($mat->_alertas->isNotEmpty())
                                <span title="{{ $mat->_alertas->count() }} alerta(s) activa(s)"
                                      style="display:inline-flex;align-items:center;gap:.25rem;background:#fee2e2;color:#b91c1c;border-radius:20px;padding:.2rem .6rem;font-size:.75rem;font-weight:700;">
                                    <i class="bi bi-bell-fill"></i>{{ $mat->_alertas->count() }}
                                </span>
                            @else
                                <span style="color:#d1d5db;font-size:.8rem;"><i class="bi bi-bell"></i></span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Pie de tabla --}}
    <div style="margin-top:.75rem;font-size:.75rem;color:#94a3b8;text-align:right;">
        Mostrando {{ $matriculas->count() }} {{ $matriculas->count() === 1 ? 'estudiante' : 'estudiantes' }}
        @if($filtroAsignacion || $filtroGrupo || $filtroBusqueda)
            (con filtros aplicados)
        @endif
    </div>
@endif

@endsection
