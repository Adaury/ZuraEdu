@extends('layouts.admin')
@section('page-title', 'Comunicados')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0" style="color:var(--primary)">
            <i class="bi bi-megaphone-fill me-2"></i>Comunicados
        </h4>
        <p class="text-muted mb-0 mt-1" style="font-size:.85rem;">Gestión de avisos y comunicados institucionales</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('admin.comunicados.lista-pdf') }}" target="_blank" class="btn btn-danger btn-sm">
            <i class="bi bi-file-earmark-pdf-fill me-1"></i>Lista PDF
        </a>
        <a href="{{ route('admin.comunicados.lista-excel') }}" class="btn btn-success btn-sm">
            <i class="bi bi-file-earmark-excel-fill me-1"></i>Excel
        </a>
        @can('Administrador')
        <a href="{{ route('admin.comunicados.create') }}" class="btn btn-primary" style="border-radius:8px;">
            <i class="bi bi-plus-lg me-1"></i>Nuevo Comunicado
        </a>
        @endcan
    </div>
</div>

@if(session('success'))
<div class="alert alert-success mb-3" style="border-radius:10px;">{{ session('success') }}</div>
@endif

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" style="font-size:.83rem;">
                <thead>
                    <tr style="background:var(--primary);color:#fff;">
                        <th class="ps-3 py-2">Título</th>
                        <th>Destinatarios</th>
                        <th>Autor</th>
                        <th>Publicado</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($comunicados as $c)
                <tr>
                    <td class="ps-3 fw-semibold">{{ $c->titulo }}</td>
                    <td>
                        @php
                            $labels = ['todos'=>'Todos','docentes'=>'Docentes','coordinadores'=>'Coordinadores','grupo'=>'Grupo específico'];
                            $colors = ['todos'=>'#1d4ed8','docentes'=>'#047857','coordinadores'=>'#7c3aed','grupo'=>'#b45309'];
                            $tipo   = $c->tipo_destinatarios;
                        @endphp
                        <span class="badge" style="background:{{ $colors[$tipo] ?? '#6b7280' }};color:#fff;font-size:.7rem;">
                            {{ $labels[$tipo] ?? $tipo }}
                            @if($tipo === 'grupo' && $c->grupo)— {{ $c->grupo->nombre_completo }}@endif
                        </span>
                    </td>
                    <td>{{ $c->autor?->name ?? '—' }}</td>
                    <td>{{ $c->published_at ? $c->published_at->format('d/m/Y H:i') : '—' }}</td>
                    <td>
                        @if($c->es_publicado)
                            <span class="badge bg-success" style="font-size:.7rem;">Publicado</span>
                        @else
                            <span class="badge bg-secondary" style="font-size:.7rem;">Borrador</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('admin.comunicados.pdf', $c) }}" target="_blank" class="btn btn-xs btn-outline-danger me-1" style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;" title="Exportar PDF">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </a>
                        <a href="{{ route('admin.comunicados.edit', $c) }}" class="btn btn-xs btn-outline-primary me-1" style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <form method="POST" action="{{ route('admin.comunicados.destroy', $c) }}" class="d-inline"
                              onsubmit="return confirm('¿Eliminar este comunicado?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger" style="font-size:.73rem;padding:.18rem .5rem;border-radius:5px;">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No hay comunicados creados aún.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $comunicados->links() }}</div>
@endsection
