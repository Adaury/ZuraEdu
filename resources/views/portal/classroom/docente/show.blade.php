@extends('layouts.admin')
@section('page-title', $claseVirtual->nombre)
@section('content')

@php
$color = $claseVirtual->portada_color ?? '#3B82F6';
$tab   = request('tab', 'muro');
$asig  = $claseVirtual->asignacion;
@endphp

{{-- ══ HEADER PORTADA ══ --}}
<div class="mb-4 p-4" style="background:{{ $color }};border-radius:18px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-40px;right:-40px;width:200px;height:200px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;"></div>
    <div style="position:absolute;bottom:-30px;left:40%;width:120px;height:120px;background:rgba(255,255,255,.05);border-radius:50%;pointer-events:none;"></div>
    <div class="d-flex align-items-center gap-3" style="position:relative;z-index:1;">
        <a href="{{ route('portal.docente.classroom.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;backdrop-filter:blur(4px);">
            <i class="bi bi-arrow-left me-1"></i>Mis Aulas
        </a>
        <div class="flex-grow-1">
            <h4 class="text-white fw-bold mb-0">{{ $claseVirtual->nombre }}</h4>
            <small class="text-white opacity-75">
                {{ $asig->asignatura?->nombre }} &bull; {{ $asig->grupo?->nombre }}
                @if($claseVirtual->codigo_clase)
                <span class="ms-2 px-2 py-0 rounded" style="background:rgba(255,255,255,.2);font-family:monospace;font-size:.8rem;">
                    {{ $claseVirtual->codigo_clase }}
                </span>
                @endif
            </small>
        </div>
        {{-- Indicador de presencia online --}}
        <div id="presence-badge" style="display:none;align-items:center;gap:.4rem;background:rgba(255,255,255,.18);backdrop-filter:blur(4px);border-radius:99px;padding:.25rem .75rem;font-size:.78rem;color:#fff;font-weight:600;">
            <span style="width:8px;height:8px;background:#4ade80;border-radius:50%;display:inline-block;animation:presencePulse 2s infinite;"></span>
            <span id="presence-count">1</span> en línea
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}" class="btn btn-sm btn-light fw-semibold">
                <i class="bi bi-plus-lg me-1"></i>Agregar
            </a>
            <div class="dropdown">
                <button class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0" style="border-radius:12px;min-width:200px;">
                    <li><a class="dropdown-item" href="{{ route('admin.classroom.edit', $claseVirtual) }}"><i class="bi bi-pencil me-2"></i>Editar aula</a></li>
                    <li>
                        <form method="POST" action="{{ route('portal.docente.classroom.sincronizar_notas', $claseVirtual) }}">
                            @csrf
                            <button type="submit" class="dropdown-item"><i class="bi bi-arrow-repeat me-2 text-success"></i>Sincronizar notas</button>
                        </form>
                    </li>
                    <li>
                        <button class="dropdown-item" onclick="generarCodigo()"><i class="bi bi-qr-code me-2 text-info"></i>Generar código</button>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- ══ TABS ══ --}}
<ul class="nav nav-pills mb-4 gap-1 flex-wrap" id="classroom-tabs" style="background:#F1F5F9;border-radius:12px;padding:6px;">
    @foreach([['muro','bi-layout-text-sidebar-reverse','Muro'],['actividades','bi-list-task','Actividades'],['personas','bi-people-fill','Personas'],['calificaciones','bi-bar-chart-fill','Calificaciones'],['recursos','bi-folder-fill','Recursos'],['chat','bi-chat-dots-fill','Chat'],['video','bi-camera-video-fill','En vivo']] as [$t,$i,$l])
    <li class="nav-item">
        <a class="nav-link classroom-tab-link {{ $tab === $t ? 'active shadow-sm' : 'text-muted' }}"
           href="#" data-tab="{{ $t }}"
           style="border-radius:8px;font-size:.875rem;{{ $tab===$t ? 'background:'.$color.';color:#fff !important;' : '' }}">
            <i class="bi {{ $i }} me-1"></i>{{ $l }}
            @if($t==='video' && $claseVirtual->meetingActiva())
            <span class="badge bg-danger ms-1" style="font-size:.6rem;animation:pulse 1.5s infinite;">LIVE</span>
            @endif
        </a>
    </li>
    @endforeach
</ul>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB MURO                                    --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-muro" class="classroom-tab-pane{{ $tab !== 'muro' ? ' d-none' : '' }}">
<div class="row g-4">
<div class="col-lg-8">
    {{-- Publicar anuncio rápido --}}
    <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div style="width:40px;height:40px;border-radius:50%;background:#E0E7FF;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi bi-pencil" style="color:#6366F1;"></i>
                </div>
                <div class="flex-grow-1">
                    <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}?tipo=anuncio"
                       class="d-block text-muted" style="background:#F8FAFC;border:1px solid #E5E7EB;border-radius:24px;padding:10px 16px;text-decoration:none;cursor:text;">
                        Anuncia algo a tu clase...
                    </a>
                </div>
            </div>
            <div class="d-flex gap-2 mt-3">
                @foreach([['tarea','bi-pencil-fill','#F59E0B','Tarea'],['material','bi-book-fill','#10B981','Material'],['evaluacion','bi-clipboard-check-fill','#EF4444','Evaluación']] as [$t,$i,$c,$l])
                <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}?tipo={{ $t }}"
                   class="btn btn-sm border-0 rounded-pill" style="background:{{ $c }}15;color:{{ $c }};font-size:.8rem;">
                    <i class="bi {{ $i }} me-1"></i>{{ $l }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Feed de materiales --}}
    @forelse($materiales as $material)
    @php
    $cfg = ['anuncio'=>['#6366F1','bi-megaphone-fill','Anuncio'],'material'=>['#10B981','bi-book-fill','Material'],'tarea'=>['#F59E0B','bi-pencil-fill','Tarea'],'evaluacion'=>['#EF4444','bi-clipboard-check-fill','Evaluación']][$material->tipo] ?? ['#6B7280','bi-file-text','Otro'];
    $clr = $cfg[0]; $icn = $cfg[1]; $lbl = $cfg[2];
    $entregasCnt = $material->getEntregasCount();
    $calificadosCnt = $material->getCalificadosCount();
    @endphp
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
        <div class="card-body">
            <div class="d-flex align-items-start gap-3">
                <div style="width:44px;height:44px;background:{{ $clr }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.15rem;"></i>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <span class="badge rounded-pill" style="background:{{ $clr }}18;color:{{ $clr }};font-size:.7rem;">{{ $lbl }}</span>
                        @if(!$material->publicado)<span class="badge bg-secondary rounded-pill" style="font-size:.7rem;">Borrador</span>@endif
                        @if($material->permite_reentrega)<span class="badge rounded-pill" style="background:#EFF6FF;color:#3B82F6;font-size:.7rem;">Reentrega</span>@endif
                        @if($material->periodo_id)<span class="badge rounded-pill" style="background:#F0FDF4;color:#16A34A;font-size:.7rem;">{{ $material->periodo?->nombre ?? 'P'.$material->periodo?->numero }}</span>@endif
                    </div>
                    <h6 class="fw-semibold mb-1">{{ $material->titulo }}</h6>
                    @if($material->contenido)<p class="text-muted small mb-2" style="font-size:.85rem;">{{ Str::limit($material->contenido, 120) }}</p>@endif

                    {{-- Archivos adjuntos --}}
                    @if($material->archivos->isNotEmpty())
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        @foreach($material->archivos->take(3) as $arch)
                        <a href="{{ Storage::disk('public')->url($arch->ruta) }}" target="_blank"
                           class="btn btn-sm border" style="font-size:.75rem;border-radius:8px;color:#4B5563;">
                            <i class="bi {{ $arch->esPdf() ? 'bi-file-pdf text-danger' : ($arch->esImagen() ? 'bi-image text-success' : 'bi-file-earmark') }} me-1"></i>
                            {{ Str::limit($arch->nombre_original, 20) }}
                        </a>
                        @endforeach
                    </div>
                    @endif

                    <div class="d-flex flex-wrap gap-3 small text-muted align-items-center">
                        @if($material->fecha_limite)
                        @php $vencido = $material->estaVencido(); @endphp
                        <span class="{{ $vencido ? 'text-danger fw-semibold' : '' }}">
                            <i class="bi bi-calendar-event me-1"></i>{{ $material->fecha_limite->format('d/m/Y H:i') }}
                            @if($vencido)<i class="bi bi-exclamation-triangle-fill ms-1" title="Vencido"></i>@endif
                        </span>
                        @endif
                        @if($material->puntos)<span><i class="bi bi-star me-1"></i>{{ $material->puntos }} pts</span>@endif
                        @if($material->url_externo)<a href="{{ $material->url_externo }}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-link-45deg me-1"></i>Enlace</a>@endif
                    </div>

                    @if($material->esTareaOEvaluacion())
                    <div class="mt-2 pt-2 border-top d-flex align-items-center gap-2 flex-wrap">
                        <a href="{{ route('portal.docente.classroom.entregas', [$claseVirtual, $material]) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.8rem;">
                            <i class="bi bi-people me-1"></i>Entregas
                            <span class="badge bg-primary ms-1">{{ $entregasCnt }}</span>
                        </a>
                        @if($material->quiz)
                        <a href="{{ route('portal.docente.classroom.quiz.resultados', [$claseVirtual, $material]) }}" class="btn btn-sm btn-outline-success" style="border-radius:8px;font-size:.8rem;">
                            <i class="bi bi-bar-chart me-1"></i>Quiz
                            <span class="badge bg-success ms-1">{{ $material->quiz->intentos()->where('estado','finalizado')->distinct('matricula_id')->count() }}</span>
                        </a>
                        @else
                        <a href="{{ route('portal.docente.classroom.quiz.crear', [$claseVirtual, $material]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;font-size:.8rem;">
                            <i class="bi bi-plus-lg me-1"></i>Crear Quiz
                        </a>
                        @endif
                        @if($calificadosCnt > 0)
                        <small class="text-success"><i class="bi bi-check-circle me-1"></i>{{ $calificadosCnt }} cal.</small>
                        @endif
                    </div>
                    @endif
                </div>
                <div class="d-flex gap-1 flex-shrink-0 ms-2">
                    <a href="{{ route('portal.docente.classroom.editar_material', [$claseVirtual, $material]) }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;" title="Editar"><i class="bi bi-pencil"></i></a>
                    <form method="POST" action="{{ route('portal.docente.classroom.eliminar_material', [$claseVirtual, $material]) }}" onsubmit="return confirm('¿Eliminar este material?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;" title="Eliminar"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body text-center py-5">
            <div style="width:70px;height:70px;background:#F1F5F9;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                <i class="bi bi-inbox" style="font-size:1.8rem;color:#94A3B8;"></i>
            </div>
            <h6 class="fw-semibold text-muted mb-1">No hay materiales publicados</h6>
            <p class="text-muted small">Agrega tu primer anuncio, material o tarea</p>
            <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}" class="btn btn-primary btn-sm mt-1">
                <i class="bi bi-plus-lg me-1"></i>Crear material
            </a>
        </div>
    </div>
    @endforelse
</div>

{{-- Sidebar --}}
<div class="col-lg-4">
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;position:sticky;top:80px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Resumen del Aula</h6>
            <div class="row g-2 mb-3">
                @foreach([['Anuncios','#6366F1','bi-megaphone-fill',$materiales->where('tipo','anuncio')->count()],['Materiales','#10B981','bi-book-fill',$materiales->where('tipo','material')->count()],['Tareas','#F59E0B','bi-pencil-fill',$materiales->where('tipo','tarea')->count()],['Evaluaciones','#EF4444','bi-clipboard-check-fill',$materiales->where('tipo','evaluacion')->count()]] as [$l,$c,$i,$n])
                <div class="col-6">
                    <div class="text-center p-2 rounded-3" style="background:{{ $c }}12;">
                        <i class="bi {{ $i }}" style="color:{{ $c }};font-size:1.2rem;"></i>
                        <div class="fw-bold mt-1" style="color:{{ $c }};">{{ $n }}</div>
                        <small class="text-muted" style="font-size:.72rem;">{{ $l }}</small>
                    </div>
                </div>
                @endforeach
            </div>
            <hr class="my-2">
            <div class="small text-muted">
                <div class="mb-1"><i class="bi bi-person me-2"></i><strong>Docente:</strong> {{ $asig->docente?->user?->name }}</div>
                <div class="mb-1"><i class="bi bi-people me-2"></i><strong>Grupo:</strong> {{ $asig->grupo?->nombre }}</div>
                <div class="mb-1"><i class="bi bi-book me-2"></i><strong>Asignatura:</strong> {{ $asig->asignatura?->nombre }}</div>
                @if($claseVirtual->codigo_clase)
                <div class="mt-2 p-2 rounded-3 text-center" style="background:#F0F9FF;">
                    <div style="font-family:monospace;font-size:1.2rem;font-weight:700;color:#0284C7;letter-spacing:2px;">{{ $claseVirtual->codigo_clase }}</div>
                    <small class="text-muted">Código de clase</small>
                </div>
                @else
                <button onclick="generarCodigo()" class="btn btn-outline-info btn-sm w-100 mt-2" style="border-radius:8px;font-size:.8rem;">
                    <i class="bi bi-qr-code me-1"></i>Generar código
                </button>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB ACTIVIDADES                             --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-actividades" class="classroom-tab-pane{{ $tab !== 'actividades' ? ' d-none' : '' }}">
<div class="card border-0 shadow-sm" style="border-radius:16px;">
<div class="card-body p-0">
    <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
        <h6 class="fw-bold mb-0">Todas las actividades</h6>
        <a href="{{ route('portal.docente.classroom.crear_material', $claseVirtual) }}" class="btn btn-primary btn-sm" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Nueva actividad
        </a>
    </div>
    @forelse($materiales->whereIn('tipo',['tarea','evaluacion']) as $material)
    @php $clr = $material->tipo==='tarea' ? '#F59E0B' : '#EF4444'; @endphp
    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom hover-bg">
        <div style="width:38px;height:38px;background:{{ $clr }}15;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi {{ $material->tipo==='tarea' ? 'bi-pencil-fill' : 'bi-clipboard-check-fill' }}" style="color:{{ $clr }};"></i>
        </div>
        <div class="flex-grow-1">
            <div class="fw-semibold" style="font-size:.9rem;">{{ $material->titulo }}</div>
            <div class="small text-muted d-flex gap-3 mt-1 flex-wrap">
                @if($material->fecha_limite)<span><i class="bi bi-calendar me-1"></i>{{ $material->fecha_limite->format('d/m/Y') }}</span>@endif
                @if($material->puntos)<span><i class="bi bi-star me-1"></i>{{ $material->puntos }} pts</span>@endif
                @if($material->periodo_id)<span style="color:#16A34A;"><i class="bi bi-calendar3 me-1"></i>{{ $material->periodo?->nombre ?? 'P'.$material->periodo?->numero }}</span>@endif
                <span style="color:#6366F1;"><i class="bi bi-people me-1"></i>{{ $material->getEntregasCount() }} entregas</span>
            </div>
        </div>
        <a href="{{ route('portal.docente.classroom.entregas', [$claseVirtual, $material]) }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;white-space:nowrap;">
            Ver entregas
        </a>
    </div>
    @empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-list-task" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
        <p class="mb-0">No hay actividades creadas</p>
    </div>
    @endforelse
</div>
</div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB PERSONAS                                --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-personas" class="classroom-tab-pane{{ $tab !== 'personas' ? ' d-none' : '' }}">
<div class="card border-0 shadow-sm" style="border-radius:16px;">
<div class="card-body p-0">
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">Docente</h6>
    </div>
    <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
        <div style="width:42px;height:42px;background:{{ $color }}20;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="bi bi-person-badge-fill" style="color:{{ $color }};"></i>
        </div>
        <div>
            <div class="fw-semibold">{{ $asig->docente?->user?->name }}</div>
            <small class="text-muted">{{ $asig->docente?->especialidad }}</small>
        </div>
    </div>
    <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between">
        <h6 class="fw-bold mb-0">Estudiantes <span class="badge bg-secondary ms-1">{{ $matriculas->count() }}</span></h6>
    </div>
    @forelse($matriculas as $mat)
    <div class="d-flex align-items-center gap-3 px-4 py-2 border-bottom" style="transition:.15s;" onmouseover="this.style.background='#F8FAFC'" onmouseout="this.style.background=''">
        <div style="width:36px;height:36px;background:#E0E7FF;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:600;color:#6366F1;font-size:.8rem;">
            {{ strtoupper(substr($mat->estudiante?->nombres ?? '?', 0, 1)) }}
        </div>
        <div class="flex-grow-1">
            <div style="font-size:.9rem;">{{ $mat->estudiante?->nombres }} {{ $mat->estudiante?->apellidos }}</div>
        </div>
        @php $pendTareas = $claseVirtual->tareasPendientes($mat->id); @endphp
        @if($pendTareas > 0)
        <span class="badge bg-warning text-dark" style="font-size:.7rem;">{{ $pendTareas }} pendiente{{ $pendTareas>1?'s':'' }}</span>
        @else
        <span class="badge bg-success" style="font-size:.7rem;">Al día</span>
        @endif
    </div>
    @empty
    <div class="text-center py-4 text-muted small">No hay estudiantes matriculados</div>
    @endforelse
</div>
</div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB CALIFICACIONES                          --}}
{{-- ══════════════════════════════════════════════ --}}
@php $actividadesCalif = $materiales->whereIn('tipo',['tarea','evaluacion'])->values(); @endphp
<div id="tab-calificaciones" class="classroom-tab-pane{{ $tab !== 'calificaciones' ? ' d-none' : '' }}">
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:auto;">
<div class="table-responsive">
<table class="table table-hover mb-0 align-middle" style="min-width:600px;">
<thead style="background:#F8FAFC;">
    <tr>
        <th class="px-4 py-3 fw-semibold text-muted" style="font-size:.8rem;text-transform:uppercase;min-width:180px;">Estudiante</th>
        @foreach($actividadesCalif as $act)
        <th class="py-3 text-center fw-semibold text-muted" style="font-size:.75rem;text-transform:uppercase;max-width:100px;" title="{{ $act->titulo }}">
            {{ Str::limit($act->titulo, 15) }}<br>
            <small style="font-weight:400;color:#94A3B8;">/ {{ $act->puntos ?? 100 }}</small>
        </th>
        @endforeach
        <th class="py-3 text-center fw-semibold" style="font-size:.8rem;text-transform:uppercase;color:{{ $color }};">Promedio</th>
    </tr>
</thead>
<tbody>
@foreach($matriculas as $mat)
@php
$notas = [];
foreach($actividadesCalif as $act) {
    $ent = $act->entregas->where('matricula_id',$mat->id)->first();
    $notas[] = $ent?->calificacion;
}
$notasValidas = array_filter($notas, fn($n) => $n !== null);
$promedio = count($notasValidas) ? round(array_sum($notasValidas)/count($notasValidas),1) : null;
@endphp
<tr>
    <td class="px-4 py-2">
        <div style="font-size:.875rem;">{{ $mat->estudiante?->nombres }} {{ $mat->estudiante?->apellidos }}</div>
    </td>
    @foreach($notas as $i => $nota)
    @php $act = $actividadesCalif[$i]; @endphp
    <td class="text-center py-2">
        @if($nota !== null)
        @php $pct = $act->puntos ? ($nota/$act->puntos)*100 : $nota; @endphp
        <span class="badge rounded-pill {{ $pct>=90?'bg-success':($pct>=70?'bg-warning text-dark':($pct>=60?'bg-info':'bg-danger')) }}">
            {{ $nota }}
        </span>
        @else
        <span class="text-muted small">—</span>
        @endif
    </td>
    @endforeach
    <td class="text-center py-2">
        @if($promedio !== null)
        <strong style="color:{{ $promedio>=90?'#16A34A':($promedio>=70?'#CA8A04':($promedio>=60?'#0284C7':'#DC2626')) }};">{{ $promedio }}</strong>
        @else
        <span class="text-muted">—</span>
        @endif
    </td>
</tr>
@endforeach
</tbody>
</table>
</div>
</div>

@if($actividadesCalif->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-bar-chart" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="mb-0">No hay actividades calificables aún</p>
</div>
@endif
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB RECURSOS                                --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-recursos" class="classroom-tab-pane{{ $tab !== 'recursos' ? ' d-none' : '' }}">
<div class="row g-4">
<div class="col-lg-8">
    @forelse($recursos as $recurso)
    @php $ti = $recurso->tipo_info; @endphp
    <div class="card border-0 shadow-sm mb-3" style="border-radius:14px;">
        <div class="card-body">
            <div class="d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;background:{{ $ti['color'] }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};font-size:1.1rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="fw-semibold">{{ $recurso->titulo }}</div>
                    @if($recurso->descripcion)<small class="text-muted">{{ $recurso->descripcion }}</small>@endif
                </div>
                <div class="d-flex gap-2">
                    @if($recurso->enlace !== '#')
                    <a href="{{ $recurso->enlace }}" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius:8px;"><i class="bi bi-download"></i></a>
                    @endif
                    <form method="POST" action="{{ route('portal.docente.classroom.recursos.eliminar', [$claseVirtual, $recurso]) }}" onsubmit="return confirm('¿Eliminar?')">
                        @csrf @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" style="border-radius:8px;"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="text-center py-5 text-muted">
        <i class="bi bi-folder2-open" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
        <p class="mb-1 fw-semibold">Biblioteca vacía</p>
        <small>Agrega guías, PDFs, videos y más</small>
    </div>
    @endforelse
</div>
<div class="col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3">Agregar recurso</h6>
            <form method="POST" action="{{ route('portal.docente.classroom.recursos.guardar', $claseVirtual) }}" enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <input type="text" name="titulo" class="form-control form-control-sm" placeholder="Título del recurso" required>
                </div>
                <div class="mb-2">
                    <select name="tipo" class="form-select form-select-sm">
                        @foreach(\App\Models\ZcRecurso::TIPOS as $k=>$v)
                        <option value="{{ $k }}">{{ $v['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-2">
                    <input type="url" name="url" class="form-control form-control-sm" placeholder="URL (opcional)">
                </div>
                <div class="mb-3">
                    <input type="file" name="archivo" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="bi bi-plus-lg me-1"></i>Agregar recurso
                </button>
            </form>
        </div>
    </div>
</div>
</div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB CHAT                                    --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-chat" class="classroom-tab-pane{{ $tab !== 'chat' ? ' d-none' : '' }}">
<div class="row g-4">
<div class="col-lg-8">
    <div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
        <div class="card-header border-0 d-flex align-items-center justify-content-between" style="background:#f8fafc;padding:1rem 1.25rem;">
            <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots-fill me-2" style="color:{{ $color }};"></i>Chat del aula</h6>
            <span class="badge" style="background:{{ $color }}20;color:{{ $color }};font-size:.72rem;">General</span>
        </div>

        {{-- Mensajes fijados --}}
        <div id="mensajes-fijados"></div>

        {{-- Área de mensajes --}}
        <div id="chat-box" style="height:420px;overflow-y:auto;padding:1rem;background:#fafafa;display:flex;flex-direction:column;gap:.5rem;">
            <div class="text-center text-muted small py-4" id="chat-loading">
                <div class="spinner-border spinner-border-sm me-2"></div>Cargando mensajes...
            </div>
        </div>

        {{-- Input --}}
        <div class="card-footer border-0 bg-white p-3">
            <form id="chat-form" class="d-flex gap-2">
                @csrf
                <input type="text" id="chat-input" class="form-control" placeholder="Escribe un mensaje al aula..." maxlength="1000" autocomplete="off" style="border-radius:24px;">
                <button type="submit" class="btn btn-primary px-3" style="border-radius:24px;background:{{ $color }};border-color:{{ $color }};">
                    <i class="bi bi-send-fill"></i>
                </button>
            </form>
        </div>
    </div>
</div>
<div class="col-lg-4">
    <div class="card border-0 shadow-sm" style="border-radius:14px;">
        <div class="card-body">
            <h6 class="fw-bold mb-3"><i class="bi bi-pin-angle-fill me-2 text-warning"></i>Mensajes fijados</h6>
            <div id="sidebar-fijados" class="small text-muted">Sin mensajes fijados</div>
        </div>
    </div>
    <div class="card border-0 shadow-sm mt-3" style="border-radius:14px;">
        <div class="card-body">
            <p class="small text-muted mb-2"><i class="bi bi-info-circle me-1"></i>Como docente puedes:</p>
            <ul class="small text-muted mb-0 ps-3">
                <li>Fijar mensajes importantes</li>
                <li>Eliminar cualquier mensaje</li>
                <li>Enviar al grupo completo</li>
            </ul>
        </div>
    </div>
</div>
</div>
</div>

{{-- ══════════════════════════════════════════════ --}}
{{--   TAB VIDEO — EN VIVO                         --}}
{{-- ══════════════════════════════════════════════ --}}
<div id="tab-video" class="classroom-tab-pane{{ $tab !== 'video' ? ' d-none' : '' }}">
<div class="row g-4 justify-content-center">
<div class="col-lg-7">
    <div class="card border-0 shadow-sm text-center" style="border-radius:20px;overflow:hidden;">
        <div class="card-body p-5">
            @if($claseVirtual->meetingActiva())
            {{-- Clase en vivo activa --}}
            <div class="mb-4">
                <div style="width:80px;height:80px;background:#fee2e2;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                    <i class="bi bi-camera-video-fill text-danger" style="font-size:2rem;"></i>
                </div>
                <span class="badge bg-danger px-3 py-2 mb-3" style="font-size:.85rem;border-radius:20px;animation:pulse 1.5s infinite;">
                    <i class="bi bi-circle-fill me-1" style="font-size:.5rem;"></i> CLASE EN VIVO
                </span>
                <h5 class="fw-bold mb-1">{{ $claseVirtual->nombre }}</h5>
                <p class="text-muted small mb-4">
                    Iniciada {{ $claseVirtual->meeting_started_at?->diffForHumans() }}
                </p>
                <a href="{{ $claseVirtual->meeting_url }}" target="_blank"
                   class="btn btn-success btn-lg px-5 mb-3" style="border-radius:12px;">
                    <i class="bi bi-camera-video-fill me-2"></i>Entrar a la clase
                </a>
                <div>
                    <button id="btn-terminar" class="btn btn-outline-danger btn-sm px-4" style="border-radius:10px;">
                        <i class="bi bi-stop-circle me-1"></i>Terminar clase
                    </button>
                </div>
            </div>
            <div class="p-3 rounded-3 text-start" style="background:#f0fdf4;border:1px solid #bbf7d0;">
                <p class="fw-semibold small text-success mb-1"><i class="bi bi-link-45deg me-1"></i>Enlace para compartir</p>
                <div class="d-flex gap-2 align-items-center">
                    <code style="font-size:.75rem;word-break:break-all;flex:1;">{{ $claseVirtual->meeting_url }}</code>
                    <button class="btn btn-sm btn-outline-success flex-shrink-0" onclick="copiarLink()">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
            @else
            {{-- Sin clase activa --}}
            <div style="width:80px;height:80px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                <i class="bi bi-camera-video" style="font-size:2rem;color:#3b82f6;"></i>
            </div>
            <h5 class="fw-bold mb-2">Iniciar clase en vivo</h5>
            <p class="text-muted mb-4" style="font-size:.9rem;">
                Se creará una sala de videoconferencia Jitsi para
                <strong>{{ $asig->asignatura?->nombre }}</strong>.
                Los estudiantes recibirán notificación automática.
            </p>
            <button id="btn-iniciar" class="btn btn-primary btn-lg px-5" style="border-radius:12px;background:{{ $color }};border-color:{{ $color }};">
                <i class="bi bi-camera-video-fill me-2"></i>Iniciar clase en vivo
            </button>
            @endif
        </div>
    </div>
</div>
<div class="col-lg-5">
    <div class="card border-0 shadow-sm" style="border-radius:16px;">
        <div class="card-body p-4">
            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Cómo funciona</h6>
            <div class="d-flex gap-3 mb-3">
                <div style="width:32px;height:32px;background:#eff6ff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;color:#3b82f6;font-size:.85rem;">1</div>
                <div>
                    <p class="fw-semibold mb-0 small">Presiona "Iniciar clase en vivo"</p>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Se genera una sala Jitsi única para tu aula</p>
                </div>
            </div>
            <div class="d-flex gap-3 mb-3">
                <div style="width:32px;height:32px;background:#f0fdf4;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;color:#16a34a;font-size:.85rem;">2</div>
                <div>
                    <p class="fw-semibold mb-0 small">Comparte el enlace</p>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Los estudiantes lo ven en su portal automáticamente</p>
                </div>
            </div>
            <div class="d-flex gap-3">
                <div style="width:32px;height:32px;background:#fefce8;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-weight:700;color:#ca8a04;font-size:.85rem;">3</div>
                <div>
                    <p class="fw-semibold mb-0 small">Enseña en vivo</p>
                    <p class="text-muted mb-0" style="font-size:.8rem;">Video, audio, pantalla compartida y chat integrado</p>
                </div>
            </div>
            <hr>
            <p class="text-muted mb-0" style="font-size:.78rem;">
                <i class="bi bi-shield-check me-1 text-success"></i>
                Jitsi Meet es gratuito, sin límite de participantes y no requiere cuenta.
            </p>
        </div>
    </div>
</div>
</div>
</div>

<style>
@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }
.chat-bubble { max-width:75%;padding:.5rem .85rem;border-radius:18px;font-size:.875rem;line-height:1.4;word-break:break-word; }
.chat-bubble.own { background:{{ $color }};color:#fff;border-bottom-right-radius:4px;align-self:flex-end; }
.chat-bubble.other { background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px;align-self:flex-start; }
.chat-bubble.pinned { background:#fef9c3;border:1px solid #fde047;align-self:flex-start; }
.chat-wrapper { display:flex;flex-direction:column;gap:.1rem; }
.chat-meta { font-size:.7rem;color:#94a3b8;margin-bottom:.2rem; }
</style>

<script>
// ── Generador de código ───────────────────────────────────────────────────
function generarCodigo() {
    fetch('{{ route('portal.docente.classroom.generar_codigo', $claseVirtual) }}', {
        method: 'POST',
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
    }).then(r => r.json()).then(d => {
        if (d.codigo) { alert('Código generado: ' + d.codigo); location.reload(); }
    });
}

// ── Copiar enlace de meeting ──────────────────────────────────────────────
function copiarLink() {
    navigator.clipboard.writeText('{{ $claseVirtual->meeting_url }}')
        .then(() => alert('¡Enlace copiado!'));
}

// ── Meeting: iniciar / terminar ───────────────────────────────────────────
const btnIniciar  = document.getElementById('btn-iniciar');
const btnTerminar = document.getElementById('btn-terminar');

if (btnIniciar) {
    btnIniciar.addEventListener('click', function () {
        btnIniciar.disabled = true;
        btnIniciar.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Iniciando...';
        fetch('{{ route('portal.docente.classroom.meeting.iniciar', $claseVirtual) }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
        }).then(r => r.json()).then(d => {
            if (d.meeting_url) {
                window.open(d.meeting_url, '_blank');
                location.reload();
            }
        }).catch(() => { btnIniciar.disabled = false; btnIniciar.innerHTML = '<i class="bi bi-camera-video-fill me-2"></i>Iniciar clase en vivo'; });
    });
}

if (btnTerminar) {
    btnTerminar.addEventListener('click', function () {
        if (!confirm('¿Terminar la clase en vivo?')) return;
        fetch('{{ route('portal.docente.classroom.meeting.terminar', $claseVirtual) }}', {
            method: 'POST',
            headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'}
        }).then(() => location.reload());
    });
}

// ── Tab switcher ──────────────────────────────────────────────────────────
const TAB_COLOR = '{{ $color }}';
let   chatInitialized = false;

function switchTab(name) {
    document.querySelectorAll('.classroom-tab-pane').forEach(el => el.classList.add('d-none'));
    document.getElementById('tab-' + name)?.classList.remove('d-none');
    document.querySelectorAll('.classroom-tab-link').forEach(el => {
        const active = el.dataset.tab === name;
        el.classList.toggle('active', active);
        el.classList.toggle('shadow-sm', active);
        el.classList.toggle('text-muted', !active);
        el.style.background = active ? TAB_COLOR : '';
        el.style.color      = active ? '#fff'    : '';
    });
    history.replaceState(null, '', '?tab=' + name);
    if (name === 'chat' && !chatInitialized) { initChat(); chatInitialized = true; }
}

document.querySelectorAll('.classroom-tab-link').forEach(el => {
    el.addEventListener('click', function(e) { e.preventDefault(); switchTab(this.dataset.tab); });
});

// ── Chat (lazy) ────────────────────────────────────────────────────────────
const CHAT_URL  = '{{ route('portal.docente.classroom.chat.index', $claseVirtual) }}';
const CHAT_POST = '{{ route('portal.docente.classroom.chat.store', $claseVirtual) }}';
const CSRF      = '{{ csrf_token() }}';
const ME_ID     = {{ auth()->id() }};
let   chatBox, chatForm, chatInput, lastId = 0;

function escHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function renderBubble(m) {
    const propio = m.user_id === ME_ID || m.es_propio;
    const wrap   = document.createElement('div');
    wrap.className = 'chat-wrapper ' + (propio ? 'align-items-end' : 'align-items-start');
    wrap.dataset.id = m.id;
    const pinBtn = propio ? '' : `<button onclick="togglePin(${m.id}, this)" class="btn btn-link btn-sm p-0 ms-1" style="font-size:.7rem;color:#94a3b8;" title="Fijar"><i class="bi bi-pin-angle"></i></button>`;
    const delBtn = `<button onclick="eliminarMsg(${m.id}, this)" class="btn btn-link btn-sm p-0 ms-1" style="font-size:.7rem;color:#94a3b8;" title="Eliminar"><i class="bi bi-trash"></i></button>`;
    wrap.innerHTML = `
        <div class="chat-meta">${propio ? '' : '<strong>' + escHtml(m.user_name) + '</strong> · '}${m.created_at}</div>
        <div class="d-flex align-items-center gap-1">
            <div class="chat-bubble ${propio ? 'own' : (m.fijado ? 'pinned' : 'other')}">${escHtml(m.mensaje)}</div>
            ${pinBtn}${delBtn}
        </div>`;
    return wrap;
}

function cargarMensajes() {
    fetch(CHAT_URL, { headers: {'Accept':'application/json','X-CSRF-TOKEN':CSRF} })
        .then(r => r.json())
        .then(data => {
            document.getElementById('chat-loading')?.remove();
            chatBox.innerHTML = '';
            const msgs = data.mensajes?.data ?? [];
            msgs.reverse().forEach(m => { chatBox.appendChild(renderBubble(m)); if (m.id > lastId) lastId = m.id; });
            chatBox.scrollTop = chatBox.scrollHeight;
            renderFijados(data.fijados ?? []);
        });
}

function renderFijados(fijados) {
    const el = document.getElementById('sidebar-fijados');
    if (!fijados.length) { el.innerHTML = '<span class="text-muted">Sin mensajes fijados</span>'; return; }
    el.innerHTML = fijados.map(f => `<div class="p-2 mb-1 rounded" style="background:#fef9c3;font-size:.8rem;border:1px solid #fde047;">
        <strong>${escHtml(f.user?.name ?? '')}</strong>: ${escHtml(f.mensaje)}</div>`).join('');
}

function togglePin(id, btn) {
    fetch(`{{ url('portal/docente/classroom/'.$claseVirtual->id.'/chat') }}/${id}/pin`, {
        method: 'PATCH', headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}
    }).then(r => r.json()).then(d => {
        btn.innerHTML = d.fijado ? '<i class="bi bi-pin-fill text-warning"></i>' : '<i class="bi bi-pin-angle"></i>';
        cargarMensajes();
    });
}

function eliminarMsg(id, btn) {
    if (!confirm('¿Eliminar mensaje?')) return;
    fetch(`{{ url('portal/docente/classroom/'.$claseVirtual->id.'/chat') }}/${id}`, {
        method: 'DELETE', headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}
    }).then(() => { btn.closest('[data-id]')?.remove(); });
}

function initChat() {
    chatBox   = document.getElementById('chat-box');
    chatForm  = document.getElementById('chat-form');
    chatInput = document.getElementById('chat-input');

    chatForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;
        chatInput.value = '';
        fetch(CHAT_POST, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ mensaje: msg, tipo: 'general' })
        }).then(r => r.json()).then(m => {
            chatBox.appendChild(renderBubble(m));
            chatBox.scrollTop = chatBox.scrollHeight;
        });
    });

    // Mensajes y eventos vía Echo (DOM events despachados por echo.js)
    // Usar addEventListener en lugar de Echo directamente evita problemas de orden de carga
    window.addEventListener('classroom:new-message', function(e) {
        if (e.detail.user_id !== ME_ID) {
            chatBox.appendChild(renderBubble(e.detail));
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
    window.addEventListener('classroom:meeting-updated', function(e) {
        const data = e.detail;
        if (data.status === 'active') {
            const alertEl = document.createElement('div');
            alertEl.className = 'alert alert-danger alert-dismissible fade show';
            alertEl.innerHTML = `<i class="bi bi-camera-video-fill me-2"></i><strong>¡Clase en vivo iniciada!</strong> <a href="${data.meeting_url}" target="_blank" class="btn btn-sm btn-danger ms-2">Entrar ahora</a><button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
            document.querySelector('.container-fluid')?.prepend(alertEl);
        }
    });

    // Polling de respaldo (solo si Reverb no está conectado después de 4s)
    setTimeout(function() {
        if (window.Echo?.connector?.pusher?.connection?.state !== 'connected') {
            setInterval(() => cargarMensajes(), 8000);
        }
    }, 4000);

    cargarMensajes();
}

// Auto-iniciar chat si la pestaña activa es chat al cargar la página
@if($tab === 'chat')
initChat(); chatInitialized = true;
@endif
</script>

@push('realtime-data')
<script>
window._SGE_CLASE_IDS = [{{ $claseVirtual->id }}];
// Presencia: conectar canal cuando Echo esté listo
document.addEventListener('DOMContentLoaded', function () {
    const claseId = {{ $claseVirtual->id }};
    function initPresence() {
        if (!window.Echo) return;
        window.Echo.join('presence-classroom.' + claseId)
            .here(function (members) {
                updatePresence(members.length);
            })
            .joining(function (member) {
                const badge  = document.getElementById('presence-badge');
                const count  = document.getElementById('presence-count');
                if (!badge || !count) return;
                const n = parseInt(count.textContent, 10) + 1;
                count.textContent = n;
                badge.style.display = 'flex';
            })
            .leaving(function (member) {
                const badge  = document.getElementById('presence-badge');
                const count  = document.getElementById('presence-count');
                if (!badge || !count) return;
                const n = Math.max(1, parseInt(count.textContent, 10) - 1);
                count.textContent = n;
                if (n <= 1) badge.style.display = 'none';
            })
            .error(function () {});
    }
    function updatePresence(n) {
        const badge = document.getElementById('presence-badge');
        const count = document.getElementById('presence-count');
        if (!badge || !count) return;
        count.textContent = n;
        badge.style.display = n > 1 ? 'flex' : 'none';
    }
    // Intentar tras 1s (Echo.js puede no estar listo en DOMContentLoaded)
    setTimeout(initPresence, 1000);
});
</script>
<style>@keyframes presencePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }</style>
@endpush
@endsection
