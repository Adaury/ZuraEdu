@extends('layouts.admin')
@section('page-title', 'Respaldo de Base de Datos')

@push('styles')
<style>
    /* ── Page header ─────────────────────────────── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: .75rem;
    }
    .page-header h1 {
        font-size: 1.45rem;
        font-weight: 800;
        color: var(--primary);
        margin: 0;
    }

    /* ── Cards ───────────────────────────────────── */
    .card-panel {
        background: #fff;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* ── Section title ───────────────────────────── */
    .section-title {
        font-size: .72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .07em;
        color: var(--primary);
        border-bottom: 2px solid var(--primary);
        padding-bottom: .4rem;
        margin-bottom: 1.1rem;
    }

    /* ── Table ───────────────────────────────────── */
    .table-backups thead th {
        font-size: .75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
        color: #6b7280;
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    .table-backups tbody td {
        vertical-align: middle;
        font-size: .875rem;
    }
    .table-hover tbody tr:hover { background: #f8faff; }

    /* ── Filename cell ───────────────────────────── */
    .filename-cell {
        font-family: 'Courier New', monospace;
        font-size: .8rem;
        color: #374151;
        word-break: break-all;
    }

    /* ── Action buttons ──────────────────────────── */
    .btn-action {
        padding: .28rem .6rem;
        font-size: .78rem;
        border-radius: 6px;
        line-height: 1.4;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        text-decoration: none;
    }
    .btn-download {
        background: #d1fae5;
        color: #065f46;
    }
    .btn-download:hover { background: #a7f3d0; color: #065f46; }
    .btn-delete {
        background: #fee2e2;
        color: #991b1b;
    }
    .btn-delete:hover { background: #fecaca; color: #991b1b; }

    /* ── Primary button ──────────────────────────── */
    .btn-primary-custom {
        background: var(--primary);
        color: #fff;
        border-radius: 8px;
        border: none;
        padding: .65rem 1.25rem;
        font-size: .9rem;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        width: 100%;
        justify-content: center;
        transition: background .15s;
    }
    .btn-primary-custom:hover { background: var(--primary-dark); color: #fff; }

    /* ── Info list ───────────────────────────────── */
    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .info-list li {
        font-size: .83rem;
        color: #374151;
        padding: .35rem 0;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: flex-start;
        gap: .5rem;
    }
    .info-list li:last-child { border-bottom: none; }
    .info-list li i { color: var(--primary); flex-shrink: 0; margin-top: .1rem; }

    /* ── Count badge ─────────────────────────────── */
    .count-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary);
        color: #fff;
        font-size: .7rem;
        font-weight: 700;
        border-radius: 20px;
        padding: .1rem .55rem;
        margin-left: .4rem;
        vertical-align: middle;
    }

    /* ── Empty state ─────────────────────────────── */
    .empty-state {
        text-align: center;
        padding: 3.5rem 2rem;
        color: #9ca3af;
    }
    .empty-state i { font-size: 3rem; display: block; margin-bottom: .75rem; opacity: .4; }
    .empty-state p { font-size: .875rem; margin: 0; }

    [data-theme="dark"] .card-panel { background: #1e293b; border-color: #334155; }
</style>
@endpush

@section('content')

{{-- Flash messages --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-check-circle-fill"></i>
        {{ session('success') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="bi bi-exclamation-circle-fill"></i>
        {{ session('error') }}
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
@endif

{{-- Page header --}}
<div class="page-header">
    <div>
        <h1>
            <i class="bi bi-database-fill me-2" style="color:var(--secondary);"></i>
            Respaldo de Base de Datos
        </h1>
        <p class="text-muted mb-0" style="font-size:.85rem;">
            Gestiona los respaldos de la base de datos del sistema
        </p>
    </div>
</div>

{{-- Two-column layout --}}
<div class="row g-4">

    {{-- LEFT: Table of existing backups --}}
    <div class="col-lg-8">
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-archive me-1"></i>
                Backups Existentes
                <span class="count-badge">{{ count($backups) }}</span>
            </div>

            @if(count($backups) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-backups mb-0">
                        <thead>
                            <tr>
                                <th>Archivo</th>
                                <th>Tamaño</th>
                                <th>Fecha</th>
                                <th class="text-end">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backups as $backup)
                                <tr>
                                    <td>
                                        <span class="filename-cell">
                                            <i class="bi bi-file-earmark-zip me-1 text-muted"></i>
                                            {{ $backup['name'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="text-muted" style="font-size:.82rem;">
                                            {{ $backup['size'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span style="font-size:.84rem;">{{ $backup['date'] }}</span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex justify-content-end align-items-center gap-2">
                                            {{-- Download --}}
                                            <a href="{{ route('admin.sistema.backup.descargar', ['file' => $backup['name']]) }}"
                                               class="btn-action btn-download"
                                               title="Descargar backup">
                                                <i class="bi bi-download"></i>
                                                Descargar
                                            </a>

                                            {{-- Delete --}}
                                            <form action="{{ route('admin.sistema.backup.eliminar') }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('¿Eliminar el backup {{ addslashes($backup['name']) }}? Esta acción no se puede deshacer.')">
                                                @csrf
                                                <input type="hidden" name="file" value="{{ $backup['name'] }}">
                                                <button type="submit" class="btn-action btn-delete" title="Eliminar backup">
                                                    <i class="bi bi-trash3"></i>
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
            @else
                <div class="empty-state">
                    <i class="bi bi-archive"></i>
                    <p>No hay backups disponibles.</p>
                    <p class="mt-1" style="font-size:.8rem;">Genera el primer respaldo usando el panel de la derecha.</p>
                </div>
            @endif
        </div>
    </div>

    {{-- RIGHT: Create backup + Info --}}
    <div class="col-lg-4">

        {{-- Crear Nuevo Backup --}}
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-plus-circle me-1"></i>
                Crear Nuevo Backup
            </div>

            <div class="alert alert-warning d-flex align-items-start gap-2 mb-3" style="font-size:.84rem; border-radius:8px;">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                <span>
                    El backup descarga la base de datos completa. El proceso puede tomar unos segundos.
                </span>
            </div>

            <form action="{{ route('admin.sistema.backup.crear') }}" method="POST">
                @csrf
                <button type="submit" class="btn-primary-custom">
                    <i class="bi bi-database-down"></i>
                    Generar Backup Ahora
                </button>
            </form>
        </div>

        {{-- Información --}}
        <div class="card-panel">
            <div class="section-title">
                <i class="bi bi-info-circle me-1"></i>
                Información
            </div>

            <ul class="info-list">
                <li>
                    <i class="bi bi-hdd-fill"></i>
                    <span>Los backups se guardan en el servidor.</span>
                </li>
                <li>
                    <i class="bi bi-file-earmark-arrow-down"></i>
                    <span>Descarga el archivo <strong>.sql</strong> para guardarlo localmente.</span>
                </li>
                <li>
                    <i class="bi bi-shield-check"></i>
                    <span>Se recomienda hacer backup antes de cambios importantes.</span>
                </li>
                <li>
                    <i class="bi bi-trash3"></i>
                    <span>Elimina backups antiguos para liberar espacio en el servidor.</span>
                </li>
            </ul>
        </div>

    </div>
</div>

@endsection
