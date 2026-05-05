@extends('layouts.admin')
@section('page-title', $claseVirtual->nombre)
@section('content')

@php
$color = $claseVirtual->portada_color ?? '#4f46e5';
$asig  = $claseVirtual->asignacion;
$tab   = $tab ?? 'materiales';
@endphp

{{-- Header portada --}}
<div class="mb-4 p-4" style="background:{{ $color }};border-radius:18px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.07);border-radius:50%;"></div>
    <div style="position:absolute;bottom:-30px;left:45%;width:120px;height:120px;background:rgba(255,255,255,.05);border-radius:50%;"></div>
    <div class="d-flex align-items-center gap-3 flex-wrap" style="position:relative;z-index:1;">
        <a href="{{ route('admin.classroom.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;">
            <i class="bi bi-arrow-left me-1"></i>Classroom
        </a>
        <div class="flex-grow-1">
            <h4 class="text-white fw-bold mb-0">{{ $claseVirtual->nombre }}</h4>
            <small class="text-white opacity-75">
                {{ $asig->asignatura?->nombre }}
                &bull; Prof. {{ $asig->docente?->user?->name }}
                &bull; {{ $asig->grupo?->nombre }}
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.classroom.edit', $claseVirtual) }}" class="btn btn-sm btn-light">
                <i class="bi bi-pencil me-1"></i>Editar
            </a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-3" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Stats rápidas --}}
<div class="row g-3 mb-4">
    @foreach([
        ['Materiales', $stats['total_materiales'], '#4f46e5', 'bi-files'],
        ['Tareas/Eval.', $stats['total_tareas'], '#f59e0b', 'bi-pencil-fill'],
        ['Entregas', $stats['total_entregas'], '#10b981', 'bi-send-check-fill'],
        ['Calificados', $stats['total_calificados'], '#2563eb', 'bi-check-circle-fill'],
        ['Por Calificar', $stats['total_entregas'] - $stats['total_calificados'], $stats['total_entregas']-$stats['total_calificados']>0?'#dc2626':'#10b981', 'bi-inbox'],
        ['Estudiantes', $totalEstudiantes, '#7c3aed', 'bi-people-fill'],
    ] as [$lbl,$val,$clr,$icn])
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card border-0 shadow-sm text-center h-100" style="border-radius:12px;border-top:3px solid {{ $clr }} !important;">
            <div class="card-body py-3 px-2">
                <i class="bi {{ $icn }} mb-1" style="color:{{ $clr }};font-size:1.3rem;display:block;"></i>
                <div class="fw-bold" style="font-size:1.2rem;color:{{ $clr }};">{{ $val ?? 0 }}</div>
                <div class="text-muted" style="font-size:.72rem;">{{ $lbl }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Tabs --}}
<ul class="nav nav-pills mb-4 gap-1" style="background:#F1F5F9;border-radius:12px;padding:6px;">
    @foreach([
        ['materiales','bi-files','Materiales'],
        ['progreso','bi-people-fill','Progreso Estudiantes'],
        ['estadisticas','bi-bar-chart-fill','Estadísticas'],
    ] as [$t,$i,$l])
    <li class="nav-item">
        <a class="nav-link {{ $tab===$t?'active shadow-sm':'text-muted' }}"
           href="{{ request()->fullUrlWithQuery(['tab'=>$t]) }}"
           style="border-radius:8px;font-size:.875rem;{{ $tab===$t?'background:'.$color.';color:#fff !important;':'' }}">
            <i class="bi {{ $i }} me-1"></i>{{ $l }}
        </a>
    </li>
    @endforeach
</ul>

{{-- ═══ TAB MATERIALES ═══ --}}
@if($tab === 'materiales')
@if($materiales->isEmpty())
<div class="card border-0 shadow-sm" style="border-radius:16px;">
    <div class="card-body text-center py-5 text-muted">
        <i class="bi bi-inbox" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
        <p class="fw-semibold mb-1">Sin materiales publicados</p>
        <small>El docente aún no ha publicado materiales en esta aula.</small>
    </div>
</div>
@else
@foreach($materiales as $material)
@php
$cfg = ['anuncio'=>['#6366F1','bi-megaphone-fill','Anuncio'],
        'material'=>['#10B981','bi-book-fill','Material'],
        'tarea'=>['#F59E0B','bi-pencil-fill','Tarea'],
        'evaluacion'=>['#EF4444','bi-clipboard-check-fill','Evaluación']][$material->tipo] ?? ['#6B7280','bi-file-text','Otro'];
$clr=$cfg[0]; $icn=$cfg[1]; $lbl=$cfg[2];
$entCnt = $material->entregas->count();
$calCnt = $material->entregas->where('estado','calificado')->count();
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <div style="width:42px;height:42px;background:{{ $clr }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                    <span class="badge rounded-pill" style="background:{{ $clr }}18;color:{{ $clr }};font-size:.7rem;">{{ $lbl }}</span>
                    @if(!$material->publicado)<span class="badge bg-secondary rounded-pill" style="font-size:.7rem;">Borrador</span>@endif
                    @if($material->periodo)<span class="badge rounded-pill" style="background:#F0FDF4;color:#16A34A;font-size:.7rem;">{{ $material->periodo->nombre ?? 'P'.$material->periodo->numero }}</span>@endif
                </div>
                <h6 class="fw-semibold mb-1">{{ $material->titulo }}</h6>
                @if($material->contenido)<p class="text-muted small mb-2">{{ Str::limit($material->contenido,120) }}</p>@endif

                <div class="d-flex flex-wrap gap-3 small text-muted align-items-center">
                    @if($material->fecha_limite)<span><i class="bi bi-calendar me-1"></i>{{ $material->fecha_limite->format('d/m/Y H:i') }}</span>@endif
                    @if($material->puntos)<span><i class="bi bi-star me-1"></i>{{ $material->puntos }} pts</span>@endif
                    @if($material->archivos->isNotEmpty())
                    <span><i class="bi bi-paperclip me-1"></i>{{ $material->archivos->count() }} archivo(s)</span>
                    @endif
                </div>

                @if($material->esTareaOEvaluacion())
                <div class="mt-2 pt-2 border-top d-flex align-items-center gap-3">
                    @php $pct = $totalEstudiantes > 0 ? round(($entCnt/$totalEstudiantes)*100) : 0; @endphp
                    <div class="small text-muted">
                        <i class="bi bi-send-check me-1"></i><strong>{{ $entCnt }}</strong>/{{ $totalEstudiantes }} entregas ({{ $pct }}%)
                    </div>
                    @if($calCnt > 0)
                    <div class="small text-success">
                        <i class="bi bi-check-circle me-1"></i>{{ $calCnt }} calificados
                    </div>
                    @endif
                    @if($entCnt - $calCnt > 0)
                    <div class="small text-warning">
                        <i class="bi bi-inbox me-1"></i>{{ $entCnt - $calCnt }} por calificar
                    </div>
                    @endif
                    {{-- Barra de progreso --}}
                    <div class="flex-grow-1" style="max-width:150px;">
                        <div style="height:4px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                            <div style="width:{{ $pct }}%;height:100%;background:{{ $pct>=80?'#10b981':($pct>=50?'#f59e0b':'#ef4444') }};border-radius:99px;"></div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endforeach
@endif
@endif

{{-- ═══ TAB PROGRESO ESTUDIANTES ═══ --}}
@if($tab === 'progreso')
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
<div class="table-responsive">
<table class="table table-hover align-middle mb-0" style="font-size:.875rem;">
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">#</th>
        <th class="py-3 fw-semibold text-muted" style="font-size:.78rem;text-transform:uppercase;">Estudiante</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Entregadas</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Calificadas</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Pendientes</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Promedio</th>
        <th class="py-3 fw-semibold text-muted text-center" style="font-size:.78rem;text-transform:uppercase;">Participación</th>
    </tr>
</thead>
<tbody>
@forelse($progresoEstudiantes as $i => $prog)
@php
$est    = $prog['matricula']->estudiante;
$total  = $stats['total_tareas'];
$pct    = $total > 0 ? round(($prog['entregadas']/$total)*100) : 0;
$avgClr = $prog['promedio'] ? ($prog['promedio']>=90?'#16a34a':($prog['promedio']>=70?'#2563eb':($prog['promedio']>=60?'#d97706':'#dc2626'))) : '#94a3b8';
@endphp
<tr>
    <td class="px-4 text-muted small">{{ $i+1 }}</td>
    <td class="py-2">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;background:{{ $color }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;font-size:.75rem;color:{{ $color }};">
                {{ strtoupper(substr($est?->nombres ?? '?', 0, 1)) }}
            </div>
            <span class="fw-semibold" style="font-size:.875rem;">{{ $est?->nombres }} {{ $est?->apellidos }}</span>
        </div>
    </td>
    <td class="text-center py-2">
        <span class="badge bg-success">{{ $prog['entregadas'] }}</span>
    </td>
    <td class="text-center py-2">
        <span class="badge bg-primary">{{ $prog['calificadas'] }}</span>
    </td>
    <td class="text-center py-2">
        @if($prog['pendientes'] > 0)
        <span class="badge bg-warning text-dark">{{ $prog['pendientes'] }}</span>
        @else
        <span class="badge bg-success"><i class="bi bi-check"></i></span>
        @endif
    </td>
    <td class="text-center py-2">
        @if($prog['promedio'] !== null)
        <strong style="color:{{ $avgClr }};">{{ $prog['promedio'] }}</strong>
        @else
        <span class="text-muted small">—</span>
        @endif
    </td>
    <td class="text-center py-2" style="min-width:100px;">
        <div style="height:6px;background:#E5E7EB;border-radius:99px;overflow:hidden;width:80px;margin:0 auto;">
            <div style="width:{{ $pct }}%;height:100%;background:{{ $pct>=80?'#10b981':($pct>=50?'#f59e0b':'#ef4444') }};border-radius:99px;"></div>
        </div>
        <div class="text-muted" style="font-size:.7rem;margin-top:2px;">{{ $pct }}%</div>
    </td>
</tr>
@empty
<tr>
    <td colspan="7" class="text-center py-4 text-muted">No hay estudiantes matriculados</td>
</tr>
@endforelse
</tbody>
</table>
</div>
</div>
@endif

{{-- ═══ TAB ESTADÍSTICAS ═══ --}}
@if($tab === 'estadisticas')
<div class="row g-4">

    {{-- Tasa de participación --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill me-2" style="color:#4f46e5;"></i>Participación General</h6>
            @php
            $tasaEntrega   = $stats['total_tareas'] > 0 && $totalEstudiantes > 0
                ? round(($stats['total_entregas'] / ($stats['total_tareas'] * $totalEstudiantes)) * 100, 1)
                : 0;
            $tasaCalif     = $stats['total_entregas'] > 0
                ? round(($stats['total_calificados'] / $stats['total_entregas']) * 100, 1)
                : 0;
            $promClr = $stats['promedio_notas'] ? ($stats['promedio_notas']>=90?'#16a34a':($stats['promedio_notas']>=70?'#2563eb':'#dc2626')) : '#94a3b8';
            @endphp
            <div class="d-flex flex-column gap-3">
                <div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="fw-semibold">Tasa de entrega</span>
                        <span class="fw-bold">{{ $tasaEntrega }}%</span>
                    </div>
                    <div style="height:8px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                        <div style="width:{{ $tasaEntrega }}%;height:100%;background:{{ $tasaEntrega>=80?'#10b981':($tasaEntrega>=50?'#f59e0b':'#ef4444') }};border-radius:99px;"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1 small">
                        <span class="fw-semibold">Tasa de calificación</span>
                        <span class="fw-bold">{{ $tasaCalif }}%</span>
                    </div>
                    <div style="height:8px;background:#E5E7EB;border-radius:99px;overflow:hidden;">
                        <div style="width:{{ $tasaCalif }}%;height:100%;background:#2563eb;border-radius:99px;"></div>
                    </div>
                </div>
                <div class="text-center p-3 rounded-3" style="background:#F0FDF4;border:1px solid #86EFAC;">
                    <div class="text-muted small mb-1">Promedio general del aula</div>
                    <div class="fw-bold" style="font-size:2rem;color:{{ $promClr }};">
                        {{ $stats['promedio_notas'] ? number_format($stats['promedio_notas'],1) : '—' }}
                    </div>
                    <div class="text-muted small">sobre {{ $materiales->whereIn('tipo',['tarea','evaluacion'])->avg('puntos') ?? 100 }} puntos</div>
                </div>
            </div>
        </div>
        </div>
    </div>

    {{-- Materiales por tipo --}}
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100" style="border-radius:16px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-bar-chart-fill me-2" style="color:#10b981;"></i>Materiales por Tipo</h6>
            @foreach(['anuncio'=>['Anuncios','#6366F1'],'material'=>['Materiales','#10B981'],'tarea'=>['Tareas','#F59E0B'],'evaluacion'=>['Evaluaciones','#EF4444']] as $tipo=>[$lbl,$clr])
            @php $cnt = $materiales->where('tipo',$tipo)->count(); $pctT = $materiales->count() > 0 ? round(($cnt/$materiales->count())*100) : 0; @endphp
            <div class="d-flex align-items-center gap-3 mb-2">
                <div style="width:70px;text-align:right;font-size:.8rem;color:#6b7280;">{{ $lbl }}</div>
                <div class="flex-grow-1" style="height:20px;background:#F3F4F6;border-radius:6px;overflow:hidden;">
                    <div style="width:{{ $pctT }}%;height:100%;background:{{ $clr }};border-radius:6px;display:flex;align-items:center;justify-content:flex-end;padding-right:6px;">
                        @if($pctT > 15)<span style="color:#fff;font-size:.72rem;font-weight:700;">{{ $cnt }}</span>@endif
                    </div>
                </div>
                <div style="width:30px;font-size:.8rem;font-weight:700;color:{{ $clr }};">{{ $cnt }}</div>
            </div>
            @endforeach

            <hr class="my-3">

            <h6 class="fw-semibold mb-2" style="font-size:.85rem;">Top Estudiantes</h6>
            @foreach($progresoEstudiantes->take(3) as $i => $prog)
            @php $est = $prog['matricula']->estudiante; @endphp
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="badge" style="background:{{ ['#fbbf24','#94a3b8','#cd7f32'][$i] ?? '#6b7280' }};font-size:.7rem;">{{ $i+1 }}°</span>
                <span style="font-size:.82rem;">{{ $est?->nombres }} {{ $est?->apellidos }}</span>
                <span class="ms-auto fw-bold" style="font-size:.82rem;color:#16a34a;">{{ $prog['promedio'] ?? '—' }}</span>
            </div>
            @endforeach
        </div>
        </div>
    </div>

</div>
@endif

@endsection
