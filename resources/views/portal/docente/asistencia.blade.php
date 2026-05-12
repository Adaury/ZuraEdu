@extends('layouts.portal')
@section('page-title', 'Pasar Asistencia')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'asistencia'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.boletines', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletines
    </a>
@endsection

@section('content')
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">Asistencia — {{ $asignacion->asignatura?->nombre }}</h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">{{ $asignacion->grupo?->nombre_completo ?? '—' }}</div>
    </div>
    <a href="{{ route('portal.docente.asistencia.qr.panel', $asignacion) }}"
       style="background:linear-gradient(135deg,#1e3a8a,#2563eb);color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-qr-code"></i>QR
    </a>
    <a href="{{ route('portal.docente.asistencia.pdf', $asignacion) }}" target="_blank"
       style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-pdf"></i>PDF
    </a>
    <a href="{{ route('portal.docente.asistencia.excel', $asignacion) }}"
       style="background:#166534;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.4rem;white-space:nowrap;flex-shrink:0;">
        <i class="bi bi-file-earmark-excel"></i>Exportar Excel
    </a>
</div>

<div class="prt-card">
    <div class="prt-card-header" style="gap:1rem;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-calendar-check" style="color:#10b981;font-size:1rem;"></i>
            <h3>Lista de estudiantes</h3>
        </div>
        <div style="margin-left:auto;">
            <input type="date" id="fechaInput" value="{{ $fecha }}"
                   onchange="window.location.href='?fecha='+this.value"
                   style="border:1px solid #e2e8f0;border-radius:8px;padding:.35rem .7rem;font-size:.8rem;color:#374151;">
        </div>
    </div>
    <form method="POST" action="{{ route('portal.docente.asistencia.guardar', $asignacion) }}">
        @csrf
        <input type="hidden" name="fecha" value="{{ $fecha }}">

        {{-- Botones rápidos --}}
        <div class="dm-toolbar" style="padding:.75rem 1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;display:flex;gap:.5rem;flex-wrap:wrap;align-items:center;">
            <span class="dm-text-muted" style="font-size:.75rem;font-weight:600;color:#64748b;">Marcar todos:</span>
            <button type="button" onclick="marcarTodos('presente')"
                    style="background:#dcfce7;color:#15803d;border:none;border-radius:7px;padding:.3rem .7rem;font-size:.73rem;font-weight:600;cursor:pointer;">
                ✔ Todos presentes
            </button>
            <button type="button" onclick="marcarTodos('ausente')"
                    style="background:#fee2e2;color:#991b1b;border:none;border-radius:7px;padding:.3rem .7rem;font-size:.73rem;font-weight:600;cursor:pointer;">
                ✖ Todos ausentes
            </button>
        </div>

        <div style="padding:0;">
            @forelse($matriculas as $m)
            @php $estadoActual = $registradas[$m->id]?->estado ?? 'presente'; @endphp
            <div class="dm-list-item est-row" style="padding:.75rem 1rem;border-bottom:1px solid #f1f5f9;">
                <div class="est-row-inner">
                <div class="dm-avatar est-row-avatar" style="width:36px;height:36px;border-radius:50%;background:#eff6ff;color:#1d4ed8;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:.85rem;flex-shrink:0;">
                    {{ strtoupper(substr($m->estudiante?->nombres ?? 'E', 0, 1)) }}
                </div>
                <div class="dm-text-primary" style="flex:1;min-width:0;font-size:.85rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $m->estudiante?->nombre_completo ?? '—' }}</div>
                <div class="est-row-buttons" data-matricula="{{ $m->id }}">
                    @foreach(['presente' => ['#15803d','#dcfce7','✔ Presente'], 'tardanza' => ['#92400e','#fef9c3','⏰ Tardanza'], 'ausente' => ['#991b1b','#fee2e2','✖ Ausente']] as $val => $info)
                    <label style="cursor:pointer;">
                        <input type="radio" name="estados[{{ $m->id }}]" value="{{ $val }}"
                               {{ $estadoActual === $val ? 'checked' : '' }}
                               onchange="highlightRow(this)"
                               style="display:none;">
                        <span class="est-btn {{ $estadoActual !== $val ? 'est-btn-default' : '' }}"
                              style="display:block;padding:.3rem .55rem;border-radius:7px;font-size:.7rem;font-weight:700;border:1.5px solid transparent;transition:all .15s;
                              {{ $estadoActual === $val ? "background:{$info[1]};color:{$info[0]};border-color:{$info[0]};" : 'background:#f1f5f9;color:#64748b;' }}">
                            {{ $info[2] }}
                        </span>
                    </label>
                    @endforeach
                </div>
                </div>{{-- est-row-inner --}}
            </div>
            @empty
            <div style="padding:2rem;text-align:center;color:#9ca3af;">No hay estudiantes matriculados en este grupo.</div>
            @endforelse
        </div>

        <div class="dm-toolbar" style="padding:1rem;background:#f8fafc;border-top:1px solid #e2e8f0;display:flex;justify-content:flex-end;gap:.75rem;align-items:center;">
            <span class="dm-text-muted" style="font-size:.78rem;color:#64748b;">{{ $matriculas->count() }} estudiante(s) · {{ $fecha }}</span>
            <button type="submit"
                    style="background:linear-gradient(135deg,#15803d,#16a34a);color:#fff;border:none;border-radius:9px;padding:.55rem 1.5rem;font-size:.85rem;font-weight:700;cursor:pointer;">
                <i class="bi bi-floppy me-1"></i>Guardar asistencia
            </button>
        </div>
    </form>
</div>
{{-- ── Panel Offline: Descargar plantilla / Importar ──────────────────── --}}
<div class="prt-card" style="margin-top:1rem;">
    <div class="prt-card-header" style="cursor:pointer;user-select:none;" onclick="toggleOffline()">
        <div style="display:flex;align-items:center;gap:.6rem;">
            <i class="bi bi-cloud-slash" style="color:#f59e0b;font-size:1rem;"></i>
            <h3 style="color:#92400e;">Modo sin internet — Plantilla CSV</h3>
        </div>
        <span id="offline-chevron" style="font-size:.8rem;color:#92400e;transition:transform .2s;">▼</span>
    </div>
    <div id="offline-panel" style="display:none;padding:1rem;border-top:1px solid #fde68a;background:#fffbeb;">

        <div style="background:#fff;border:1px solid #fde68a;border-radius:8px;padding:.75rem 1rem;margin-bottom:1rem;font-size:.8rem;color:#78350f;">
            <strong><i class="bi bi-info-circle me-1"></i>¿Cómo usar la plantilla?</strong>
            <ol style="margin:.4rem 0 0 1.1rem;padding:0;line-height:2;">
                <li>Descarga la plantilla CSV. Ya incluye todos los estudiantes del grupo y la fecha de hoy.</li>
                <li>Abre el archivo en Excel o cualquier hoja de cálculo.</li>
                <li>Columna <code>estado</code>: escribe <code>presente</code>, <code>ausente</code>, <code>tardanza</code> o <code>justificado</code>.</li>
                <li>Columna <code>fecha</code>: formato <code>AAAA-MM-DD</code> (ej: <code>{{ now()->format('Y-m-d') }}</code>).</li>
                <li>Puedes registrar varios días en el mismo archivo (una fila por estudiante por día).</li>
                <li>Guarda como CSV y sube el archivo aquí.</li>
            </ol>
        </div>

        <div style="display:flex;gap:.75rem;align-items:center;flex-wrap:wrap;margin-bottom:1rem;">
            <a href="{{ route('portal.docente.asistencia.plantilla', $asignacion) }}?fecha={{ $fecha }}"
               style="display:inline-flex;align-items:center;gap:.5rem;background:#16a34a;color:#fff;text-decoration:none;border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:700;">
                <i class="bi bi-file-earmark-arrow-down-fill"></i>Descargar plantilla CSV
            </a>
            <span style="font-size:.75rem;color:#92400e;">
                {{ $matriculas->count() }} estudiante(s) · fecha: {{ $fecha }}
            </span>
        </div>

        <form method="POST"
              action="{{ route('portal.docente.asistencia.importar', $asignacion) }}"
              enctype="multipart/form-data"
              style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
            @csrf
            <div style="flex:1;min-width:200px;">
                <label style="font-size:.75rem;font-weight:600;color:#92400e;display:block;margin-bottom:.3rem;">
                    Subir plantilla completada (.csv, .xlsx):
                </label>
                <input type="file" name="archivo" accept=".csv,.xlsx,.xls"
                       required
                       style="width:100%;font-size:.8rem;border:1.5px solid #fde68a;border-radius:8px;padding:.35rem .6rem;background:#fff;color:#374151;">
            </div>
            <button type="submit"
                    style="background:#d97706;color:#fff;border:none;border-radius:8px;padding:.45rem 1rem;font-size:.82rem;font-weight:700;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-upload"></i>Importar asistencia
            </button>
        </form>

        @if(session('errores_import') && count(session('errores_import')))
        <div style="margin-top:.75rem;background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;padding:.65rem .9rem;font-size:.77rem;color:#991b1b;">
            <strong><i class="bi bi-exclamation-triangle me-1"></i>Filas con errores (omitidas):</strong>
            <ul style="margin:.3rem 0 0 1rem;padding:0;">
                @foreach(session('errores_import') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function highlightRow(radio) {
    const colores = { presente: '#dcfce7', tardanza: '#fef9c3', ausente: '#fee2e2' };
    const textColors = { presente: '#15803d', tardanza: '#92400e', ausente: '#991b1b' };
    const row = radio.closest('.est-row');
    row.style.background = colores[radio.value] + '55';

    // Reset all labels in this group
    row.querySelectorAll('.est-btn').forEach(btn => {
        btn.style.background = '#f1f5f9';
        btn.style.color = '#64748b';
        btn.style.borderColor = 'transparent';
    });
    // Highlight selected
    const selSpan = radio.nextElementSibling;
    selSpan.style.background = colores[radio.value];
    selSpan.style.color = textColors[radio.value];
    selSpan.style.borderColor = textColors[radio.value];
}

function marcarTodos(estado) {
    document.querySelectorAll(`input[type="radio"][value="${estado}"]`).forEach(r => {
        r.checked = true;
        highlightRow(r);
    });
}
</script>
@endpush
