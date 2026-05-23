@extends('layouts.portal')
@section('title', $claseVirtual->nombre)

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'classroom'])
@endsection

@section('content')

@php
$color = $claseVirtual->portada_color ?? '#3B82F6';
$asig  = $claseVirtual->asignacion;
$tab   = request('tab', 'muro');
@endphp

{{-- Header portada --}}
<div class="mb-4 p-4" style="background:{{ $color }};border-radius:18px;position:relative;overflow:hidden;">
    <div style="position:absolute;top:-30px;right:-30px;width:150px;height:150px;background:rgba(255,255,255,.07);border-radius:50%;pointer-events:none;"></div>
    <div style="position:relative;z-index:1;">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <a href="{{ route('portal.estudiante.classroom.index') }}" class="btn btn-sm" style="background:rgba(255,255,255,.2);color:#fff;border:none;">
                <i class="bi bi-arrow-left me-1"></i>Mis Aulas
            </a>
            {{-- Indicador de presencia online --}}
            <div id="presence-badge" style="display:none;align-items:center;gap:.4rem;background:rgba(255,255,255,.18);backdrop-filter:blur(4px);border-radius:99px;padding:.25rem .75rem;font-size:.78rem;color:#fff;font-weight:600;">
                <span style="width:8px;height:8px;background:#4ade80;border-radius:50%;display:inline-block;animation:presencePulse 2s infinite;"></span>
                <span id="presence-count">1</span> en línea
            </div>
        </div>
        <h4 class="text-white fw-bold mb-0">{{ $claseVirtual->nombre }}</h4>
        <small class="text-white opacity-75">
            {{ $asig->asignatura?->nombre }} &bull; Prof. {{ $asig->docente?->user?->name }}
        </small>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:12px;">
    <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif
@if(session('error'))
<div class="alert alert-danger alert-dismissible fade show mb-3 border-0 shadow-sm" style="border-radius:12px;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

{{-- Banner clase en vivo --}}
@if($claseVirtual->meetingActiva())
<div class="alert alert-danger d-flex align-items-center gap-3 mb-3 border-0 shadow-sm" style="border-radius:14px;">
    <i class="bi bi-camera-video-fill fs-4" style="animation:pulse 1.5s infinite;"></i>
    <div class="flex-grow-1">
        <strong>¡Clase en vivo!</strong> Tu docente está transmitiendo ahora.
    </div>
    <a href="{{ $claseVirtual->meeting_url }}" target="_blank" class="btn btn-danger btn-sm px-3" style="border-radius:10px;">
        <i class="bi bi-box-arrow-up-right me-1"></i>Unirme
    </a>
</div>
@endif

{{-- Tabs --}}
@php $classTabs = [['muro','bi-layout-text-sidebar-reverse','Muro'],['tareas','bi-list-task','Mis Tareas'],['recursos','bi-folder-fill','Recursos'],['chat','bi-chat-dots-fill','Chat']]; @endphp
<ul class="nav nav-pills mb-4 gap-1 flex-wrap" style="background:#F1F5F9;border-radius:12px;padding:6px;">
    @foreach($classTabs as [$t,$i,$l])
    <li class="nav-item">
        <a class="nav-link classroom-tab-link {{ $tab===$t?'active shadow-sm':'text-muted' }}"
           href="#" data-tab="{{ $t }}"
           style="border-radius:8px;font-size:.875rem;{{ $tab===$t?'background:'.$color.';color:#fff !important;':'' }}">
            <i class="bi {{ $i }} me-1"></i>{{ $l }}
        </a>
    </li>
    @endforeach
</ul>
<style>@keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.5} }</style>

{{-- TAB MURO --}}
<div id="tab-muro" class="classroom-tab-pane{{ $tab !== 'muro' ? ' d-none' : '' }}">
@forelse($materiales as $material)
@php
$clr = ['anuncio'=>'#6366F1','material'=>'#10B981','tarea'=>'#F59E0B','evaluacion'=>'#EF4444'][$material->tipo] ?? '#6B7280';
$icn = ['anuncio'=>'bi-megaphone-fill','material'=>'bi-book-fill','tarea'=>'bi-pencil-fill','evaluacion'=>'bi-clipboard-check-fill'][$material->tipo] ?? 'bi-file-text';
$lbl = ['anuncio'=>'Anuncio','material'=>'Material','tarea'=>'Tarea','evaluacion'=>'Evaluación'][$material->tipo] ?? $material->tipo;
$entrega = $entregasMap[$material->id] ?? null;
$esTarea = $material->esTareaOEvaluacion();
@endphp
<div class="card border-0 shadow-sm mb-3" style="border-radius:14px;border-left:4px solid {{ $clr }} !important;">
    <div class="card-body">
        <div class="d-flex align-items-start gap-3">
            <div style="width:42px;height:42px;background:{{ $clr }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $icn }}" style="color:{{ $clr }};font-size:1.1rem;"></i>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                    <span class="badge rounded-pill" style="background:{{ $clr }}18;color:{{ $clr }};font-size:.7rem;">{{ $lbl }}</span>
                    @if($esTarea)
                        @if($entrega && $entrega->estado === 'calificado')
                            @php $pct = $material->puntos ? ($entrega->calificacion/$material->puntos)*100 : $entrega->calificacion; @endphp
                            <span class="badge bg-success">{{ $entrega->calificacion }}/{{ $material->puntos ?? 100 }} &bull; {{ round($pct) }}%</span>
                        @elseif($entrega && $entrega->estado === 'devuelto')
                            <span class="badge bg-warning text-dark"><i class="bi bi-arrow-return-left me-1"></i>Devuelto para corregir</span>
                        @elseif($entrega && $entrega->estado === 'atrasado')
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i>Entregado tarde</span>
                        @elseif($entrega)
                            <span class="badge bg-info"><i class="bi bi-send-check me-1"></i>Entregado</span>
                        @elseif($material->estaVencido())
                            <span class="badge bg-danger">Vencido</span>
                        @else
                            <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Pendiente</span>
                        @endif
                    @endif
                </div>
                <h6 class="fw-semibold mb-1">{{ $material->titulo }}</h6>
                @if($material->contenido)<p class="text-muted small mb-2">{{ $material->contenido }}</p>@endif

                {{-- Botón de Quiz si tiene quiz asignado --}}
                @if($material->quiz)
                @php
                $quizRelacion = $material->quiz;
                $puedeIniciarQuiz = $quizRelacion->puedeIntentar($matricula->id);
                $intentoActivo    = $quizRelacion->intentoActivo($matricula->id);
                $mejorIntento     = $quizRelacion->intentos()->where('matricula_id',$matricula->id)->where('estado','finalizado')->orderByDesc('puntuacion')->first();
                @endphp
                <div class="mb-2 p-3 rounded-3" style="background:#eef2ff;border:1px solid #c7d2fe;">
                    <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                        <div>
                            <span class="fw-semibold small" style="color:#4f46e5;"><i class="bi bi-clipboard-check me-1"></i>Quiz online</span>
                            <div class="text-muted" style="font-size:.75rem;">
                                {{ $quizRelacion->preguntas()->count() }} preguntas
                                @if($quizRelacion->duracion_minutos) · {{ $quizRelacion->duracion_minutos }} min @endif
                                · máx. {{ $quizRelacion->intentos_max }} intento(s)
                            </div>
                            @if($mejorIntento)
                            <div style="font-size:.75rem;color:#16a34a;font-weight:600;">
                                <i class="bi bi-trophy me-1"></i>Mejor nota: {{ $mejorIntento->puntuacion }}/{{ $mejorIntento->puntuacion_max }} ({{ $mejorIntento->porcentaje }}%)
                            </div>
                            @endif
                        </div>
                        @if($intentoActivo)
                        <a href="{{ route('portal.estudiante.classroom.quiz.tomar', [$claseVirtual, $material, $intentoActivo]) }}"
                           class="btn btn-sm btn-warning fw-semibold" style="border-radius:8px;">
                            <i class="bi bi-play-fill me-1"></i>Continuar
                        </a>
                        @elseif($puedeIniciarQuiz)
                        <a href="{{ route('portal.estudiante.classroom.quiz.iniciar', [$claseVirtual, $material]) }}"
                           class="btn btn-sm fw-semibold" style="background:#4f46e5;color:#fff;border-radius:8px;">
                            <i class="bi bi-play-fill me-1"></i>{{ $mejorIntento ? 'Reintentar' : 'Iniciar quiz' }}
                        </a>
                        @else
                        <span class="badge bg-secondary">Sin intentos restantes</span>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Archivos del material --}}
                @if($material->archivos->isNotEmpty())
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($material->archivos as $arch)
                    <a href="{{ Storage::disk('public')->url($arch->ruta) }}" target="_blank"
                       class="btn btn-sm border" style="font-size:.75rem;border-radius:8px;color:#4B5563;">
                        <i class="bi {{ $arch->esPdf() ? 'bi-file-pdf text-danger' : 'bi-paperclip' }} me-1"></i>
                        {{ Str::limit($arch->nombre_original, 20) }}
                    </a>
                    @endforeach
                </div>
                @endif

                <div class="d-flex flex-wrap gap-3 small text-muted align-items-center">
                    @if($material->fecha_limite)
                    @php $vencido = $material->estaVencido(); @endphp
                    <span class="{{ $vencido && !$entrega ? 'text-danger fw-semibold' : '' }}">
                        <i class="bi bi-calendar-event me-1"></i>{{ $material->fecha_limite->format('d/m/Y H:i') }}
                        {{ ($vencido && !$entrega) ? ' — Vencido' : '' }}
                    </span>
                    @endif
                    @if($material->puntos)<span><i class="bi bi-star me-1"></i>{{ $material->puntos }} pts</span>@endif
                    @if($material->url_externo)<a href="{{ $material->url_externo }}" target="_blank" class="text-primary text-decoration-none"><i class="bi bi-link-45deg me-1"></i>Ver recurso</a>@endif
                </div>

                {{-- FORMULARIO DE ENTREGA --}}
                @if($esTarea && (!$entrega || $material->permite_reentrega || $entrega->estado === 'devuelto'))
                <div class="mt-3 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;">
                    <p class="fw-semibold small mb-2">
                        <i class="bi bi-upload me-1"></i>
                        {{ $entrega ? 'Reenviar tarea' : 'Entregar tarea' }}
                    </p>
                    <form method="POST" action="{{ route('portal.estudiante.classroom.entregar', [$claseVirtual, $material]) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <textarea name="contenido" class="form-control form-control-sm" rows="3"
                                      placeholder="Escribe tu respuesta aquí...">{{ $entrega?->contenido }}</textarea>
                        </div>
                        <div class="mb-2">
                            <input type="url" name="url_entrega" class="form-control form-control-sm"
                                   placeholder="URL de entrega (Google Docs, GitHub, Drive...)"
                                   value="{{ $entrega?->url_entrega }}">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1">Adjuntar archivos</label>
                            <input type="file" name="archivos[]" class="form-control form-control-sm" multiple accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip">
                            <div class="form-text" style="font-size:.75rem;">PDF, Word, imágenes, ZIP — máx. 20 MB c/u</div>
                        </div>
                        {{-- Archivos previos de entrega --}}
                        @if($entrega && $entrega->archivos->isNotEmpty())
                        <div class="mb-2">
                            <div class="small text-muted mb-1">Archivos ya entregados:</div>
                            @foreach($entrega->archivos as $arch)
                            <a href="{{ $arch->url }}" target="_blank" class="btn btn-sm btn-outline-secondary me-1 mb-1" style="font-size:.75rem;border-radius:8px;">
                                <i class="bi bi-paperclip me-1"></i>{{ Str::limit($arch->nombre_original,20) }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm" style="border-radius:8px;">
                            <i class="bi bi-check-lg me-1"></i>{{ $entrega ? 'Reenviar' : 'Entregar' }}
                        </button>
                        @if($material->estaVencido() && !$entrega)
                        <span class="text-danger small ms-2"><i class="bi bi-exclamation-triangle me-1"></i>La entrega será registrada como tardía</span>
                        @endif
                    </form>
                </div>

                {{-- Retroalimentación del docente --}}
                @elseif($entrega?->comentario_docente || $entrega?->retroalimentacion)
                <div class="mt-2 p-3 rounded-3" style="background:#ECFDF5;border:1px solid #A7F3D0;">
                    <div class="fw-semibold small text-success mb-1"><i class="bi bi-chat-left-text me-1"></i>Retroalimentación del docente</div>
                    @if($entrega->comentario_docente)<p class="small mb-1">{{ $entrega->comentario_docente }}</p>@endif
                    @if($entrega->retroalimentacion)<p class="small mb-0 text-muted">{{ $entrega->retroalimentacion }}</p>@endif
                </div>
                @elseif($entrega && $entrega->estado !== 'calificado')
                <div class="mt-2 p-2 rounded-3" style="background:#EFF6FF;border:1px solid #BFDBFE;">
                    <small class="text-primary"><i class="bi bi-clock me-1"></i>Entregado. Esperando calificación del docente.</small>
                </div>
                @endif

                {{-- Calificación con rúbrica --}}
                @if($entrega?->rubricCalificaciones->isNotEmpty())
                <div class="mt-3 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E5E7EB;">
                    <div class="fw-semibold small mb-2"><i class="bi bi-grid-3x3-gap me-1 text-indigo"></i>Detalle por rúbrica</div>
                    @foreach($entrega->rubricCalificaciones as $rc)
                    <div class="d-flex justify-content-between small py-1 border-bottom">
                        <span>{{ $rc->criterio?->nombre }}</span>
                        <span class="fw-semibold">{{ $rc->puntaje }} / {{ $rc->criterio?->puntaje_max }}</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@empty
<div class="text-center py-5 text-muted">
    <i class="bi bi-inbox" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="mb-0">No hay publicaciones aún</p>
</div>
@endforelse
</div>

{{-- TAB MIS TAREAS --}}
<div id="tab-tareas" class="classroom-tab-pane{{ $tab !== 'tareas' ? ' d-none' : '' }}">
@php
$tareas = $materiales->filter(fn($m) => $m->esTareaOEvaluacion());
$pendientes = $tareas->filter(fn($m) => !isset($entregasMap[$m->id]) || in_array($entregasMap[$m->id]?->estado??'pendiente',['pendiente','devuelto']));
$entregadas = $tareas->filter(fn($m) => isset($entregasMap[$m->id]) && in_array($entregasMap[$m->id]->estado,['entregado','atrasado','calificado']));
@endphp

@if($pendientes->isNotEmpty())
<div class="mb-4">
    <h6 class="fw-bold mb-3 text-warning"><i class="bi bi-clock me-1"></i>Por entregar ({{ $pendientes->count() }})</h6>
    @foreach($pendientes->sortBy('fecha_limite') as $material)
    @php $entrega = $entregasMap[$material->id] ?? null; $clr = $material->estaVencido() ? '#EF4444' : '#F59E0B'; @endphp
    <div class="card border-0 shadow-sm mb-2" style="border-radius:12px;border-left:3px solid {{ $clr }} !important;">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <div class="fw-semibold" style="font-size:.9rem;">{{ $material->titulo }}</div>
                    <div class="small text-muted">
                        @if($material->fecha_limite)
                            @if($material->estaVencido())
                                <span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>Vencido {{ $material->fecha_limite->diffForHumans() }}</span>
                            @else
                                <i class="bi bi-calendar me-1"></i>Vence {{ $material->fecha_limite->diffForHumans() }}
                            @endif
                        @else
                            Sin fecha límite
                        @endif
                        @if($material->puntos) &bull; {{ $material->puntos }} pts @endif
                    </div>
                    @if($entrega?->estado === 'devuelto')
                    <span class="badge bg-warning text-dark mt-1" style="font-size:.7rem;"><i class="bi bi-arrow-return-left me-1"></i>Devuelta para corregir</span>
                    @endif
                </div>
                <div class="ms-auto">
                    <a href="?tab=muro#material-{{ $material->id }}" class="btn btn-sm btn-outline-primary" style="border-radius:8px;font-size:.8rem;">Entregar</a>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@if($entregadas->isNotEmpty())
<div>
    <h6 class="fw-bold mb-3 text-success"><i class="bi bi-check-circle me-1"></i>Entregadas ({{ $entregadas->count() }})</h6>
    @foreach($entregadas->sortByDesc('fecha_limite') as $material)
    @php $entrega = $entregasMap[$material->id]; @endphp
    <div class="card border-0 shadow-sm mb-2" style="border-radius:12px;border-left:3px solid #10B981 !important;">
        <div class="card-body py-3">
            <div class="d-flex align-items-center gap-3">
                <div class="flex-grow-1">
                    <div class="fw-semibold" style="font-size:.9rem;">{{ $material->titulo }}</div>
                    <div class="small text-muted">
                        Entregado {{ $entrega->fecha_entrega?->format('d/m/Y H:i') }}
                        @if($material->puntos) &bull; {{ $material->puntos }} pts @endif
                    </div>
                </div>
                <div class="text-end">
                    @if($entrega->estado === 'calificado')
                        @php $pct = $material->puntos ? ($entrega->calificacion/$material->puntos)*100 : $entrega->calificacion; @endphp
                        <div class="fw-bold" style="font-size:1.1rem;color:{{ $pct>=90?'#16A34A':($pct>=70?'#CA8A04':'#DC2626') }};">
                            {{ $entrega->calificacion }}/{{ $material->puntos ?? 100 }}
                        </div>
                        <small class="text-muted">{{ round($pct) }}%</small>
                    @else
                        <span class="badge bg-info" style="font-size:.75rem;">Esperando revisión</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

@if($tareas->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-check-all" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="fw-semibold mb-0">No hay tareas asignadas</p>
</div>
@endif
</div>

{{-- TAB RECURSOS --}}
<div id="tab-recursos" class="classroom-tab-pane{{ $tab !== 'recursos' ? ' d-none' : '' }}">
@if($recursos->isEmpty())
<div class="text-center py-5 text-muted">
    <i class="bi bi-folder2-open" style="font-size:3rem;color:#CBD5E1;display:block;margin-bottom:.75rem;"></i>
    <p class="mb-0">No hay recursos disponibles</p>
</div>
@else
<div class="row g-3">
@foreach($recursos as $recurso)
@php $ti = $recurso->tipo_info; @endphp
<div class="col-md-6">
    <div class="card border-0 shadow-sm h-100" style="border-radius:14px;">
        <div class="card-body d-flex align-items-center gap-3">
            <div style="width:44px;height:44px;background:{{ $ti['color'] }}18;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="bi {{ $ti['icon'] }}" style="color:{{ $ti['color'] }};font-size:1.2rem;"></i>
            </div>
            <div class="flex-grow-1 min-w-0">
                <div class="fw-semibold" style="font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $recurso->titulo }}</div>
                @if($recurso->descripcion)<small class="text-muted">{{ Str::limit($recurso->descripcion,60) }}</small>@endif
            </div>
            @if($recurso->enlace !== '#')
            <a href="{{ $recurso->enlace }}" target="_blank" class="btn btn-sm btn-outline-primary flex-shrink-0" style="border-radius:8px;">
                <i class="bi bi-download"></i>
            </a>
            @endif
        </div>
    </div>
</div>
@endforeach
</div>
@endif
</div>

{{-- TAB CHAT --}}
<div id="tab-chat" class="classroom-tab-pane{{ $tab !== 'chat' ? ' d-none' : '' }}">
<div class="card border-0 shadow-sm" style="border-radius:16px;overflow:hidden;">
    <div class="card-header border-0 d-flex align-items-center justify-content-between" style="background:#f8fafc;padding:1rem 1.25rem;">
        <h6 class="fw-bold mb-0"><i class="bi bi-chat-dots-fill me-2" style="color:{{ $color }};"></i>Chat del aula</h6>
        <span class="small text-muted">{{ $asig->asignatura?->nombre }}</span>
    </div>
    <div id="chat-box" style="height:400px;overflow-y:auto;padding:1rem;background:#fafafa;display:flex;flex-direction:column;gap:.5rem;">
        <div class="text-center text-muted small py-4" id="chat-loading">
            <div class="spinner-border spinner-border-sm me-2"></div>Cargando mensajes...
        </div>
    </div>
    @if($claseVirtual->permite_comentarios)
    <div class="card-footer border-0 bg-white p-3">
        <form id="chat-form" class="d-flex gap-2">
            @csrf
            <input type="text" id="chat-input" class="form-control" placeholder="Escribe tu mensaje..." maxlength="1000" autocomplete="off" style="border-radius:24px;">
            <button type="submit" class="btn btn-primary px-3" style="border-radius:24px;background:{{ $color }};border-color:{{ $color }};">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
    @else
    <div class="card-footer border-0 bg-white p-3 text-center text-muted small">
        <i class="bi bi-lock me-1"></i>El chat está desactivado en este aula
    </div>
    @endif
</div>

</div>

<style>
.chat-bubble { max-width:75%;padding:.5rem .85rem;border-radius:18px;font-size:.875rem;line-height:1.4;word-break:break-word; }
.chat-bubble.own { background:{{ $color }};color:#fff;border-bottom-right-radius:4px;align-self:flex-end; }
.chat-bubble.other { background:#fff;border:1px solid #e5e7eb;border-bottom-left-radius:4px;align-self:flex-start; }
.chat-wrapper { display:flex;flex-direction:column;gap:.1rem; }
.chat-meta { font-size:.7rem;color:#94a3b8;margin-bottom:.2rem; }
</style>

<script>
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
const CHAT_URL  = '{{ route('portal.estudiante.classroom.chat.index', $claseVirtual) }}';
const CHAT_POST = '{{ route('portal.estudiante.classroom.chat.store', $claseVirtual) }}';
const CSRF      = '{{ csrf_token() }}';
const ME_ID     = {{ auth()->id() }};
let   chatBox, chatForm, chatInput;

function escHtml(t) { const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }

function renderBubble(m) {
    const propio = m.user_id === ME_ID || m.es_propio;
    const wrap = document.createElement('div');
    wrap.className = 'chat-wrapper ' + (propio ? 'align-items-end' : 'align-items-start');
    wrap.innerHTML = `
        <div class="chat-meta">${propio ? 'Tú' : '<strong>' + escHtml(m.user_name) + '</strong>'} · ${m.created_at}</div>
        <div class="chat-bubble ${propio ? 'own' : 'other'}">${escHtml(m.mensaje)}</div>`;
    return wrap;
}

function cargarMensajes() {
    fetch(CHAT_URL, { headers: {'Accept':'application/json','X-CSRF-TOKEN':CSRF} })
        .then(r => r.json())
        .then(data => {
            document.getElementById('chat-loading')?.remove();
            chatBox.innerHTML = '';
            const msgs = data.mensajes?.data ?? [];
            msgs.reverse().forEach(m => chatBox.appendChild(renderBubble(m)));
            chatBox.scrollTop = chatBox.scrollHeight;
        });
}

function initChat() {
    chatBox   = document.getElementById('chat-box');
    chatForm  = document.getElementById('chat-form');
    chatInput = document.getElementById('chat-input');

    if (chatForm) {
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
    }

    // Mensajes y eventos vía Echo (DOM events despachados por echo.js)
    window.addEventListener('classroom:new-message', function(e) {
        if (e.detail.user_id !== ME_ID) {
            chatBox.appendChild(renderBubble(e.detail));
            chatBox.scrollTop = chatBox.scrollHeight;
        }
    });
    window.addEventListener('classroom:meeting-updated', function(e) {
        const data = e.detail;
        if (data.status === 'active' && data.meeting_url) {
            const banner = document.querySelector('.alert-danger');
            if (!banner) {
                const el = document.createElement('div');
                el.className = 'alert alert-danger d-flex align-items-center gap-3 mb-3 border-0 shadow-sm';
                el.style.borderRadius = '14px';
                el.innerHTML = `<i class="bi bi-camera-video-fill fs-4"></i><div class="flex-grow-1"><strong>¡Clase en vivo!</strong> Tu docente está transmitiendo.</div><a href="${data.meeting_url}" target="_blank" class="btn btn-danger btn-sm">Unirme</a>`;
                document.querySelector('.container-fluid, main')?.prepend(el);
            }
        }
    });

    // Polling de respaldo si Reverb no conecta
    setTimeout(function() {
        if (window.Echo?.connector?.pusher?.connection?.state !== 'connected') {
            setInterval(() => cargarMensajes(), 8000);
        }
    }, 4000);

    cargarMensajes();
}

@if($tab === 'chat')
initChat(); chatInitialized = true;
@endif
</script>

@push('realtime-data')
<script>
window._SGE_CLASE_IDS = [{{ $claseVirtual->id }}];
document.addEventListener('DOMContentLoaded', function () {
    const claseId = {{ $claseVirtual->id }};
    function initPresence() {
        if (!window.Echo) return;
        window.Echo.join('presence-classroom.' + claseId)
            .here(function (members) { updatePresence(members.length); })
            .joining(function () {
                const c = document.getElementById('presence-count');
                if (c) { const n = parseInt(c.textContent, 10) + 1; c.textContent = n; updatePresence(n); }
            })
            .leaving(function () {
                const c = document.getElementById('presence-count');
                if (c) { const n = Math.max(1, parseInt(c.textContent, 10) - 1); c.textContent = n; updatePresence(n); }
            })
            .error(function () {});
    }
    function updatePresence(n) {
        const badge = document.getElementById('presence-badge');
        if (!badge) return;
        badge.style.display = n > 1 ? 'flex' : 'none';
        const c = document.getElementById('presence-count');
        if (c) c.textContent = n;
    }
    setTimeout(initPresence, 1000);
});
</script>
<style>@keyframes presencePulse { 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.6;transform:scale(1.3)} }</style>
@endpush
@endsection
