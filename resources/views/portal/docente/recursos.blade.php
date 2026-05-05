@extends('layouts.portal')
@section('page-title', 'Recursos — ' . ($asignacion->asignatura?->nombre ?? ''))
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'recursos'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.asistencia', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-calendar-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.recursos', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-folder-fill"></i>Recursos
    </a>
@endsection

@push('styles')
<style>
.rec-tipo-badge {
    display: inline-flex; align-items: center; gap: .3rem;
    font-size: .68rem; font-weight: 700; border-radius: 99px;
    padding: .18rem .55rem; white-space: nowrap;
}
.rec-card {
    border: 1px solid var(--prt-border);
    border-radius: 12px;
    padding: .85rem 1rem;
    transition: box-shadow .15s, border-color .15s;
    margin-bottom: .6rem;
    background: var(--prt-card);
}
.rec-card:hover { border-color: #c7d7ff; box-shadow: 0 3px 12px rgba(0,0,0,.06); }
[data-theme="dark"] .rec-card { border-color: #334155; }
[data-theme="dark"] .rec-card:hover { border-color: #4f6a9a; }

/* Formulario */
.frm-group { margin-bottom: .85rem; }
.frm-label { display:block; font-size:.75rem; font-weight:700; color:var(--prt-muted); margin-bottom:.3rem; }
.frm-input {
    width: 100%; padding: .5rem .75rem; font-size: .82rem;
    border: 1.5px solid var(--prt-border); border-radius: 8px;
    background: var(--prt-card); color: var(--prt-text);
    transition: border-color .15s;
}
.frm-input:focus { outline: none; border-color: #2563eb; }
[data-theme="dark"] .frm-input { background: #0f172a; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Header --}}
<div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;flex-wrap:wrap;">
    <a href="{{ route('portal.docente.dashboard') }}" class="btn-back"
       style="background:#f1f5f9;color:#374151;border-radius:8px;padding:.4rem .85rem;font-size:.8rem;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
        <i class="bi bi-arrow-left"></i>Volver
    </a>
    <div style="flex:1;">
        <h1 style="font-size:1rem;font-weight:800;margin:0;">
            <i class="bi bi-folder-fill" style="color:#2563eb;"></i>
            Recursos — {{ $asignacion->asignatura?->nombre }}
        </h1>
        <div class="dm-text-muted" style="font-size:.75rem;color:#64748b;">
            {{ $asignacion->grupo?->nombre_completo ?? '—' }}
            @if($schoolYear) · {{ $schoolYear->nombre }} @endif
            · {{ $recursos->count() }} recurso(s)
        </div>
    </div>
    <div style="display:flex;gap:.5rem;">
        <a href="{{ route('portal.docente.recursos.pdf', $asignacion) }}" target="_blank"
           style="background:#dc2626;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-pdf"></i>PDF
        </a>
        <a href="{{ route('portal.docente.recursos.excel', $asignacion) }}"
           style="background:#16a34a;color:#fff;border-radius:8px;padding:.4rem .85rem;font-size:.78rem;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:.4rem;">
            <i class="bi bi-file-earmark-excel"></i>Excel
        </a>
    </div>
</div>

@if(session('success'))
<div style="background:#dcfce7;color:#15803d;border-radius:10px;padding:.65rem 1rem;margin-bottom:1rem;font-size:.8rem;display:flex;align-items:center;gap:.5rem;">
    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
</div>
@endif

{{-- Formulario nuevo recurso --}}
<div class="prt-card" style="margin-bottom:1rem;">
    <div class="prt-card-header">
        <i class="bi bi-plus-circle-fill" style="color:#2563eb;font-size:1rem;"></i>
        <h3>Agregar Recurso</h3>
    </div>
    <div class="prt-card-body" style="padding:1rem;">
        <form method="POST" action="{{ route('portal.docente.recursos.guardar', $asignacion) }}"
              enctype="multipart/form-data" id="frm-recurso">
            @csrf
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <div class="frm-group" style="grid-column:1/-1;">
                    <label class="frm-label">Título <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="titulo" class="frm-input" placeholder="Ej: Guía de estudio P1 — Álgebra" required maxlength="200" value="{{ old('titulo') }}">
                </div>
                <div class="frm-group">
                    <label class="frm-label">Tipo de recurso</label>
                    <select name="tipo" class="frm-input" id="tipo-sel" onchange="tipoChange()">
                        <option value="enlace">🔗 Enlace web</option>
                        <option value="video">▶️ Video (YouTube / Drive)</option>
                        <option value="documento">📄 Documento</option>
                        <option value="imagen">🖼️ Imagen</option>
                        <option value="otro">📎 Archivo</option>
                    </select>
                </div>
                <div class="frm-group">
                    <label class="frm-label">Visibilidad</label>
                    <select name="publicado" class="frm-input">
                        <option value="1">Publicado (visible para estudiantes)</option>
                        <option value="0">Borrador (solo yo lo veo)</option>
                    </select>
                </div>
                {{-- URL --}}
                <div class="frm-group" id="url-group" style="grid-column:1/-1;">
                    <label class="frm-label">URL <span style="color:#ef4444;">*</span></label>
                    <input type="url" name="url" class="frm-input" placeholder="https://..." value="{{ old('url') }}">
                </div>
                {{-- Archivo --}}
                <div class="frm-group" id="file-group" style="grid-column:1/-1;display:none;">
                    <label class="frm-label">Archivo (máx. 20 MB)</label>
                    <input type="file" name="archivo" class="frm-input" style="padding:.4rem .75rem;"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.jpg,.jpeg,.png,.gif,.zip">
                </div>
                <div class="frm-group" style="grid-column:1/-1;">
                    <label class="frm-label">Descripción (opcional)</label>
                    <textarea name="descripcion" class="frm-input" rows="2" maxlength="500"
                              placeholder="Breve descripción o instrucción para los estudiantes...">{{ old('descripcion') }}</textarea>
                </div>
            </div>
            @if($errors->any())
            <div style="background:#fee2e2;color:#dc2626;border-radius:8px;padding:.6rem .9rem;font-size:.78rem;margin-bottom:.75rem;">
                @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
            @endif
            <button type="submit" style="background:#2563eb;color:#fff;border:none;border-radius:9px;padding:.55rem 1.25rem;font-size:.83rem;font-weight:700;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                <i class="bi bi-plus-lg"></i>Agregar recurso
            </button>
        </form>
    </div>
</div>

{{-- Lista de recursos --}}
<div class="prt-card">
    <div class="prt-card-header">
        <i class="bi bi-list-ul" style="color:#2563eb;font-size:1rem;"></i>
        <h3>Recursos publicados</h3>
    </div>
    <div class="prt-card-body" style="padding:.75rem 1rem;">
        @forelse($recursos as $rec)
        @php
            $color = $rec->color;
            $icono = $rec->icono;
        @endphp
        <div class="rec-card">
            <div style="display:flex;align-items:flex-start;gap:.75rem;flex-wrap:wrap;">
                <div style="width:38px;height:38px;border-radius:10px;background:{{ $color }}18;color:{{ $color }};display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;">
                    <i class="bi {{ $icono }}"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:.87rem;font-weight:800;color:var(--prt-text);margin-bottom:.2rem;display:flex;align-items:center;gap:.5rem;flex-wrap:wrap;">
                        {{ $rec->titulo }}
                        <span class="rec-tipo-badge" style="background:{{ $color }}18;color:{{ $color }};">
                            {{ ucfirst($rec->tipo) }}
                        </span>
                        @if(!$rec->publicado)
                        <span class="rec-tipo-badge" style="background:#fef9c3;color:#92400e;">Borrador</span>
                        @endif
                    </div>
                    @if($rec->descripcion)
                    <div style="font-size:.75rem;color:var(--prt-muted);margin-bottom:.3rem;">{{ $rec->descripcion }}</div>
                    @endif
                    @if($rec->url)
                    <a href="{{ $rec->url }}" target="_blank" rel="noopener"
                       style="font-size:.73rem;color:#2563eb;word-break:break-all;display:inline-flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-box-arrow-up-right"></i>
                        {{ Str::limit($rec->url, 60) }}
                    </a>
                    @elseif($rec->archivo_nombre)
                    <span style="font-size:.73rem;color:#64748b;display:inline-flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-paperclip"></i>{{ $rec->archivo_nombre }}
                    </span>
                    @endif
                    <div style="font-size:.65rem;color:#94a3b8;margin-top:.3rem;">
                        Subido {{ $rec->created_at->diffForHumans() }}
                    </div>
                </div>
                {{-- Acciones --}}
                <div style="display:flex;align-items:center;gap:.5rem;flex-shrink:0;">
                    @if($rec->url)
                    <a href="{{ $rec->url }}" target="_blank" rel="noopener"
                       style="background:#f0f9ff;color:#0369a1;border-radius:7px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-eye-fill"></i>Ver
                    </a>
                    @elseif($rec->archivo_path)
                    <a href="{{ Storage::url($rec->archivo_path) }}" target="_blank"
                       style="background:#f0f9ff;color:#0369a1;border-radius:7px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;text-decoration:none;display:flex;align-items:center;gap:.25rem;">
                        <i class="bi bi-download"></i>Descargar
                    </a>
                    @endif
                    <button onclick="toggleRec({{ $rec->id }}, this)"
                            data-pub="{{ $rec->publicado ? '1' : '0' }}"
                            style="background:{{ $rec->publicado ? '#dcfce7' : '#fef9c3' }};color:{{ $rec->publicado ? '#15803d' : '#92400e' }};border:none;border-radius:7px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;cursor:pointer;">
                        <i class="bi {{ $rec->publicado ? 'bi-eye-fill' : 'bi-eye-slash-fill' }}"></i>
                        {{ $rec->publicado ? 'Pub.' : 'Draft' }}
                    </button>
                    <form method="POST" action="{{ route('portal.docente.recursos.eliminar', [$asignacion, $rec]) }}"
                          onsubmit="return confirm('¿Eliminar este recurso?')" style="margin:0;">
                        @csrf @method('DELETE')
                        <button type="submit" style="background:#fee2e2;color:#dc2626;border:none;border-radius:7px;padding:.3rem .65rem;font-size:.72rem;font-weight:700;cursor:pointer;">
                            <i class="bi bi-trash3-fill"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:2.5rem 1rem;color:#9ca3af;">
            <i class="bi bi-folder-x" style="font-size:2.5rem;display:block;margin-bottom:.75rem;"></i>
            <div style="font-weight:700;margin-bottom:.3rem;">Sin recursos aún</div>
            <div style="font-size:.8rem;">Agrega el primer recurso usando el formulario de arriba.</div>
        </div>
        @endforelse
    </div>
</div>

@endsection

@push('scripts')
<script>
function tipoChange() {
    const tipo = document.getElementById('tipo-sel').value;
    const urlGrp  = document.getElementById('url-group');
    const fileGrp = document.getElementById('file-group');
    const urlInp  = urlGrp.querySelector('input');

    // Tipos con archivo local
    if (tipo === 'otro' || tipo === 'documento' || tipo === 'imagen') {
        urlGrp.style.display  = 'none';
        fileGrp.style.display = 'block';
        urlInp.removeAttribute('required');
    } else {
        urlGrp.style.display  = 'block';
        fileGrp.style.display = 'none';
        urlInp.setAttribute('required', '');
    }
}

async function toggleRec(id, btn) {
    const asig = {{ $asignacion->id }};
    const res = await fetch(`/portal/docente/asignacion/${asig}/recursos/${id}/toggle`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    });
    const data = await res.json();
    if (data.ok) {
        btn.dataset.pub = data.publicado ? '1' : '0';
        btn.style.background = data.publicado ? '#dcfce7' : '#fef9c3';
        btn.style.color = data.publicado ? '#15803d' : '#92400e';
        btn.innerHTML = `<i class="bi ${data.publicado ? 'bi-eye-fill' : 'bi-eye-slash-fill'}"></i> ${data.publicado ? 'Pub.' : 'Draft'}`;
    }
}
</script>
@endpush
