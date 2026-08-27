@extends('layouts.admin')

@section('page-title', 'Capacitación')

@push('styles')
<style>
:root {
    --cap-bg:#fff; --cap-border:#e5e7eb; --cap-text:#111827; --cap-desc:#4b5563;
    --cap-step:#374151; --cap-step-sep:#f3f4f6; --cap-muted:#6b7280;
    --cap-tab-bg:#fff; --cap-tab-c:#6b7280; --cap-tab-border:#e5e7eb;
    --cap-track-bg:#f3f4f6;
}
[data-theme="dark"] {
    --cap-bg:#1e293b; --cap-border:#334155; --cap-text:#e2e8f0; --cap-desc:#94a3b8;
    --cap-step:#cbd5e1; --cap-step-sep:#293548; --cap-muted:#94a3b8;
    --cap-tab-bg:#1e293b; --cap-tab-c:#94a3b8; --cap-tab-border:#334155;
    --cap-track-bg:#0f172a;
}

.cap-header {
    background: linear-gradient(135deg, #064e3b 0%, #047857 100%);
    border-radius: 16px; padding: 1.75rem 2rem; margin-bottom: 1.5rem;
    display: flex; align-items: center; gap: 1.25rem; color: #fff;
}
.cap-header .header-icon {
    width:56px; height:56px; background:rgba(255,255,255,.15); border-radius:14px;
    display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0;
}
.cap-header h1 { font-size:1.4rem; font-weight:800; margin-bottom:.25rem; }
.cap-header p  { font-size:.875rem; color:rgba(255,255,255,.8); margin:0; }

.cap-progress-wrap { flex:1; max-width:260px; margin-left:auto; }
.cap-progress-label { font-size:.72rem; color:rgba(255,255,255,.85); margin-bottom:.3rem; display:flex; justify-content:space-between; }
.cap-progress-track { height:8px; border-radius:99px; background:rgba(255,255,255,.2); overflow:hidden; }
.cap-progress-bar { height:100%; background:#fff; border-radius:99px; transition:width .3s; }

.cap-tabs { display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:1.5rem; }
.cap-tab {
    display:inline-flex; align-items:center; gap:.45rem; padding:.5rem 1.1rem; border-radius:10px;
    font-size:.84rem; font-weight:600; border:2px solid var(--cap-tab-border); cursor:pointer;
    background:var(--cap-tab-bg); color:var(--cap-tab-c); transition:background .18s,border-color .18s,color .18s;
}
.cap-tab.active { color:#fff; border-color:transparent; background:var(--tab-color, #047857); }
.cap-tab .count { font-size:.68rem; opacity:.85; }

.cap-grupo { display:none; }
.cap-grupo.active { display:block; }

.cap-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1rem; }

.cap-card {
    background:var(--cap-bg); border:1px solid var(--cap-border); border-radius:14px; padding:1.25rem;
    box-shadow:0 1px 4px rgba(0,0,0,.06); display:flex; flex-direction:column; gap:.75rem;
    transition:border-color .2s, box-shadow .2s;
}
.cap-card:hover { box-shadow:0 4px 16px rgba(0,0,0,.1); }
.cap-card.visto { border-color:#86efac; }
[data-theme="dark"] .cap-card.visto { border-color:#166534; }

.cap-card-head { display:flex; align-items:flex-start; gap:.75rem; }
.cap-card-title { font-size:.95rem; font-weight:700; color:var(--cap-text); margin:0 0 .15rem; }
.cap-card-meta { font-size:.72rem; color:var(--cap-muted); display:flex; align-items:center; gap:.4rem; }

.cap-steps { list-style:none; padding:0; margin:0; }
.cap-steps li {
    font-size:.82rem; color:var(--cap-step); padding:.3rem 0; border-bottom:1px solid var(--cap-step-sep);
    display:flex; align-items:flex-start; gap:.5rem;
}
.cap-steps li:last-child { border-bottom:none; }
.cap-steps li i { flex-shrink:0; margin-top:.15rem; color:var(--cap-muted); font-size:.7rem; }

.cap-card-footer { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-top:auto; padding-top:.25rem; }
.cap-ver-tab { font-size:.78rem; color:#047857; text-decoration:none; font-weight:600; }
.cap-ver-tab:hover { text-decoration:underline; }

.cap-visto-btn {
    display:inline-flex; align-items:center; gap:.4rem; padding:.35rem .8rem; border-radius:8px;
    font-size:.78rem; font-weight:600; border:1.5px solid var(--cap-border); background:transparent;
    color:var(--cap-muted); cursor:pointer; transition:all .15s; white-space:nowrap;
}
.cap-visto-btn:hover { border-color:#047857; color:#047857; }
.cap-visto-btn.visto { background:#dcfce7; border-color:#86efac; color:#166534; }
[data-theme="dark"] .cap-visto-btn.visto { background:#052e16; border-color:#166534; color:#4ade80; }
.cap-visto-btn.loading { opacity:.6; pointer-events:none; }

@media (max-width:767px) {
    .cap-header { flex-wrap:wrap; }
    .cap-progress-wrap { max-width:none; width:100%; margin-left:0; }
    .cap-grid { grid-template-columns:1fr; }
}
</style>
@endpush

@section('content')

@php
    $totalGuias = collect($grupos)->sum(fn ($g) => count($g['guias']));
    $totalVistas = $vistos->count();
@endphp

<div class="cap-header">
    <div class="header-icon"><i class="bi bi-mortarboard-fill"></i></div>
    <div>
        <h1>Capacitación</h1>
        <p>Guías cortas para aprender a usar el sistema, organizadas por perfil</p>
    </div>
    <div class="cap-progress-wrap">
        <div class="cap-progress-label">
            <span>Tu progreso</span>
            <span id="capProgressText">{{ $totalVistas }}/{{ $totalGuias }}</span>
        </div>
        <div class="cap-progress-track">
            <div class="cap-progress-bar" id="capProgressBar" style="width:{{ $totalGuias ? round($totalVistas / $totalGuias * 100) : 0 }}%;"></div>
        </div>
    </div>
</div>

<a href="{{ route('admin.ayuda') }}" class="d-inline-flex align-items-center gap-2 mb-3 text-decoration-none" style="font-size:.82rem;color:#6b7280;">
    <i class="bi bi-arrow-left"></i> Volver al Manual de Ayuda
</a>

<div class="cap-tabs" id="capTabs">
    @foreach($grupos as $key => $grupo)
        <button type="button" class="cap-tab {{ $loop->first ? 'active' : '' }}" style="--tab-color:{{ $grupo['color'] }};" onclick="capSw('{{ $key }}', this)">
            <i class="bi {{ $grupo['icono'] }}"></i> {{ $grupo['nombre'] }}
            <span class="count">({{ count($grupo['guias']) }})</span>
        </button>
    @endforeach
</div>

@foreach($grupos as $key => $grupo)
    <div class="cap-grupo {{ $loop->first ? 'active' : '' }}" id="capg-{{ $key }}">
        <div class="mb-3" style="font-size:.78rem;color:var(--cap-muted);">
            <i class="bi bi-people-fill me-1"></i>Roles: {{ implode(', ', $grupo['roles']) }}
        </div>
        <div class="cap-grid">
            @foreach($grupo['guias'] as $guia)
                @php $visto = $vistos->has($guia['id']); @endphp
                <div class="cap-card {{ $visto ? 'visto' : '' }}" id="capcard-{{ $guia['id'] }}">
                    <div class="cap-card-head">
                        <div class="flex-grow-1">
                            <h4 class="cap-card-title">{{ $guia['titulo'] }}</h4>
                            <div class="cap-card-meta"><i class="bi bi-clock"></i> {{ $guia['duracion'] }}</div>
                        </div>
                    </div>
                    <ul class="cap-steps">
                        @foreach($guia['pasos'] as $paso)
                            <li><i class="bi bi-caret-right-fill"></i>{{ $paso }}</li>
                        @endforeach
                    </ul>
                    <div class="cap-card-footer">
                        @if($guia['ver_tab'])
                            <a href="{{ route('admin.ayuda', ['tab' => $guia['ver_tab']]) }}" class="cap-ver-tab">Ver guía completa <i class="bi bi-arrow-right"></i></a>
                        @else
                            <span></span>
                        @endif
                        <button type="button" class="cap-visto-btn {{ $visto ? 'visto' : '' }}" data-id="{{ $guia['id'] }}" onclick="capToggleVisto(this)">
                            <i class="bi {{ $visto ? 'bi-check-circle-fill' : 'bi-circle' }}"></i>
                            <span>{{ $visto ? 'Visto' : 'Marcar como visto' }}</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endforeach

<script>
(function () {
    'use strict';

    window.capSw = function (key, btn) {
        document.querySelectorAll('.cap-grupo').forEach(g => g.classList.remove('active'));
        document.querySelectorAll('.cap-tab').forEach(b => b.classList.remove('active'));
        var g = document.getElementById('capg-' + key);
        if (g) g.classList.add('active');
        if (btn) btn.classList.add('active');
    };

    var totalGuias = {{ (int) $totalGuias }};

    window.capToggleVisto = function (btn) {
        var id = btn.dataset.id;
        btn.classList.add('loading');
        var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch('{{ url("admin/ayuda/capacitacion") }}/' + id + '/visto', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
        })
        .then(r => r.json())
        .then(function (data) {
            var card = document.getElementById('capcard-' + id);
            var icon = btn.querySelector('i');
            var label = btn.querySelector('span');
            btn.classList.toggle('visto', data.visto);
            if (card) card.classList.toggle('visto', data.visto);
            icon.className = data.visto ? 'bi bi-check-circle-fill' : 'bi bi-circle';
            label.textContent = data.visto ? 'Visto' : 'Marcar como visto';

            var vistos = document.querySelectorAll('.cap-visto-btn.visto').length;
            var pct = totalGuias ? Math.round(vistos / totalGuias * 100) : 0;
            document.getElementById('capProgressText').textContent = vistos + '/' + totalGuias;
            document.getElementById('capProgressBar').style.width = pct + '%';
        })
        .finally(function () { btn.classList.remove('loading'); });
    };
})();
</script>

@endsection
