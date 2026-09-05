@extends('layouts.admin')

@section('page-title', 'Matriz de Accesos')

@push('styles')
<style>
    :root{
        --ra-ink:#1c2938; --ra-ink-soft:#5b6b7f; --ra-border:#e2e6ec; --ra-border-strong:#c9d0da;
        --ra-accent:#4338ca; --ra-accent-soft:#eef2ff; --ra-grant:#2f8f5b; --ra-bypass:#6b46a3; --ra-bypass-soft:#f4effa;
    }
    [data-theme="dark"] {
        --ra-ink:#e6ebf1; --ra-ink-soft:#93a1b3; --ra-border:#2a3344; --ra-border-strong:#3a4658;
        --ra-accent:#a5b4fc; --ra-accent-soft:#1e2440; --ra-grant:#6fc596; --ra-bypass:#c4a6ec; --ra-bypass-soft:#2a2038;
    }
    .ra-wrap{ color:var(--ra-ink); }
    .ra-controls{ display:flex; gap:.75rem; align-items:center; flex-wrap:wrap; margin-bottom:1rem; }
    .ra-search{ flex:1; min-width:220px; position:relative; }
    .ra-search input{
        width:100%; padding:.55rem .9rem .55rem 2.1rem; border:1px solid var(--ra-border-strong);
        border-radius:8px; font-size:.85rem;
    }
    .ra-search .bi{ position:absolute; left:.75rem; top:50%; transform:translateY(-50%); color:var(--ra-ink-soft); }
    .ra-legend{ display:flex; gap:1rem; font-size:.76rem; color:var(--ra-ink-soft); flex-wrap:wrap; }
    .ra-dot{ width:.55rem; height:.55rem; border-radius:50%; display:inline-block; margin-right:.35rem; }
    .ra-dot.g{ background:var(--ra-grant); }
    .ra-dot.b{ background:var(--ra-bypass); }

    .ra-shell{ border:1px solid var(--ra-border); border-radius:12px; overflow:auto; max-height:74vh; background:var(--surface-color, #fff); }
    [data-theme="dark"] .ra-shell{ background:#1a2130; }
    .ra-shell table{ border-collapse:separate; border-spacing:0; font-size:.8rem; width:max-content; min-width:100%; }
    .ra-shell thead th{ position:sticky; top:0; background:inherit; z-index:3; border-bottom:1px solid var(--ra-border-strong); padding:0; }
    .ra-shell th.ra-rolehead{ width:32px; min-width:32px; padding:.4rem .2rem; vertical-align:bottom; }
    .ra-shell th.ra-rolehead .rot{
        writing-mode:vertical-rl; transform:rotate(180deg); font-family:monospace; font-weight:500;
        font-size:.7rem; white-space:nowrap; height:150px; display:flex; align-items:flex-end;
    }
    .ra-shell th.ra-rolehead.super .rot{ color:var(--ra-bypass); font-weight:700; }
    .ra-corner{
        position:sticky; left:0; top:0; z-index:4; background:inherit;
        border-bottom:1px solid var(--ra-border-strong); border-right:1px solid var(--ra-border-strong);
        min-width:230px; padding:.55rem .85rem; text-align:left;
        font-size:.7rem; text-transform:uppercase; letter-spacing:.05em; color:var(--ra-ink-soft);
    }
    .ra-shell tbody th.ra-permcell{
        position:sticky; left:0; z-index:2; background:inherit; border-right:1px solid var(--ra-border-strong);
        text-align:left; padding:.45rem .85rem; min-width:230px; font-weight:500; font-size:.8rem;
    }
    .ra-shell tbody th.ra-permcell code{ display:block; font-family:monospace; font-size:.66rem; color:var(--ra-ink-soft); margin-top:.05rem; font-weight:400; }
    .ra-shell tbody tr{ border-bottom:1px solid var(--ra-border); }
    .ra-shell tbody td{ text-align:center; padding:.35rem; border-bottom:1px solid var(--ra-border); border-right:1px solid var(--ra-border); }
    .ra-cat-row td, .ra-cat-row th{
        background:var(--ra-accent-soft); color:var(--ra-accent); font-family:monospace; font-size:.66rem;
        text-transform:uppercase; letter-spacing:.06em; font-weight:700; padding:.35rem .85rem; text-align:left; position:sticky; left:0;
    }
    .ra-mark{ width:8px; height:8px; border-radius:50%; display:inline-block; background:var(--ra-grant); }
    .ra-mark.super{ background:var(--ra-bypass); }
    .ra-shell td.ra-super-col{ background:var(--ra-bypass-soft); }
    .ra-shell th.ra-rolehead.super{ background:var(--ra-bypass-soft); }
    .ra-note{
        margin-top:1.1rem; font-size:.82rem; color:var(--ra-ink-soft); line-height:1.6;
        border:1px solid var(--ra-border); border-radius:10px; padding:1rem 1.15rem;
    }
    .ra-note strong{ color:var(--ra-ink); }
    tr.ra-hidden, th.ra-hidden{ display:none; }
</style>
@endpush

@php
    $grupos = [
        'Gestión' => [
            'gestionar-usuarios' => 'Usuarios',
            'gestionar-school-years' => 'Años Escolares',
            'gestionar-grupos' => 'Grupos',
            'gestionar-docentes' => 'Docentes',
            'gestionar-estudiantes' => 'Estudiantes',
            'gestionar-matriculas' => 'Matrículas',
            'gestionar-asignaturas' => 'Asignaturas',
            'gestionar-asignaciones' => 'Asignaciones',
            'gestionar-periodos' => 'Períodos',
            'gestionar-indicadores' => 'Indicadores de Logro',
            'gestionar-configuracion' => 'Configuración del Sistema',
            'gestionar-pagos' => 'Pagos',
            'gestionar-biblioteca' => 'Biblioteca',
        ],
        'Acción' => [
            'ingresar-calificaciones' => 'Ingresar Calificaciones',
            'ingresar-asistencia' => 'Ingresar Asistencia',
            'imprimir-boletines' => 'Imprimir Boletines',
            'supervisar-registros' => 'Supervisar Registros',
        ],
        'Consulta' => [
            'ver-dashboard' => 'Dashboard',
            'ver-calificaciones' => 'Calificaciones',
            'ver-asistencia' => 'Asistencia',
            'ver-boletines' => 'Boletines',
            'ver-estadisticas' => 'Estadísticas',
            'ver-reportes-institucionales' => 'Reportes Institucionales',
            'ver-pagos' => 'Pagos',
            'ver-servicios' => 'Servicios',
            'ver-estudiantes' => 'Estudiantes (solo lectura)',
        ],
    ];

    $rolePerms = $roles->mapWithKeys(fn ($r) => [$r->name => $r->permissions->pluck('name')->all()]);
@endphp

@section('content')
<div class="ra-wrap">
    <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
        <div>
            <h1 class="h4 fw-bold mb-1"><i class="bi bi-shield-lock-fill me-2" style="color:var(--ra-accent);"></i>Matriz de Accesos</h1>
            <p class="mb-0" style="font-size:.86rem;color:var(--ra-ink-soft);">
                {{ $roles->count() }} roles × {{ collect($grupos)->collapse()->count() }} permisos — datos en vivo desde el sistema.
                <strong style="color:var(--ra-bypass);">super_admin</strong> no depende de esta matriz: un
                <code>Gate::before</code> le concede todo, sin importar los permisos asignados a su rol.
            </p>
        </div>
        <a href="{{ route('admin.ayuda') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Volver al manual
        </a>
    </div>

    <div class="ra-controls">
        <div class="ra-search">
            <i class="bi bi-search"></i>
            <input type="text" id="raFilter" placeholder="Filtrar por permiso… (ej: pagos, boletines, matrículas)">
        </div>
        <div class="ra-legend">
            <span><i class="ra-dot g"></i>Permiso asignado</span>
            <span><i class="ra-dot b"></i>super_admin (bypass total)</span>
        </div>
    </div>

    <div class="ra-shell">
        <table>
            <thead>
                <tr>
                    <th class="ra-corner">Permiso</th>
                    @foreach ($roles as $role)
                        <th class="ra-rolehead {{ $role->name === 'super_admin' ? 'super' : '' }}">
                            <div class="rot" title="{{ $role->name }}">{{ $role->name }}</div>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupoNombre => $permisos)
                    <tr class="ra-cat-row">
                        <th>{{ $grupoNombre }}</th>
                        @foreach ($roles as $role)<td></td>@endforeach
                    </tr>
                    @foreach ($permisos as $key => $label)
                        <tr data-search="{{ \Illuminate\Support\Str::lower($label.' '.$key) }}">
                            <th class="ra-permcell">{{ $label }}<code>{{ $key }}</code></th>
                            @foreach ($roles as $role)
                                @php $isSuper = $role->name === 'super_admin'; @endphp
                                <td class="{{ $isSuper ? 'ra-super-col' : '' }}">
                                    @if ($isSuper || in_array($key, $rolePerms[$role->name]))
                                        <span class="ra-mark {{ $isSuper ? 'super' : '' }}"></span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="ra-note">
        <strong>Roles con permisos idénticos (alias funcionales):</strong>
        Coordinador Primer Ciclo = Coordinador Segundo Ciclo · Secretaría = Secretaria Docente ·
        Registrador Académico = Encargado de Registro Académico · Docente = Docente Académico = Docente Técnico
        (Docente Guía agrega <code>ver-estadisticas</code>).
        Existen como roles separados porque cada uno se usa para un dashboard/sidebar contextual distinto,
        aunque el conjunto de permisos coincida.
    </div>
</div>

<script>
document.getElementById('raFilter').addEventListener('input', function (e) {
    var q = e.target.value.trim().toLowerCase();
    document.querySelectorAll('.ra-shell tbody tr:not(.ra-cat-row)').forEach(function (tr) {
        var s = tr.getAttribute('data-search') || '';
        tr.classList.toggle('ra-hidden', q && s.indexOf(q) === -1);
    });
    document.querySelectorAll('.ra-shell tbody tr.ra-cat-row').forEach(function (catRow) {
        var next = catRow.nextElementSibling, anyVisible = false;
        while (next && !next.classList.contains('ra-cat-row')) {
            if (!next.classList.contains('ra-hidden')) anyVisible = true;
            next = next.nextElementSibling;
        }
        catRow.classList.toggle('ra-hidden', !anyVisible);
    });
});
</script>
@endsection
