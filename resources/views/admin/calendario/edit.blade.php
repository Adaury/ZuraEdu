@extends('layouts.admin')

@section('page-title', 'Editar Evento')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-calendar-event me-2"></i>Editar Evento</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.calendario.index') }}">Calendario</a></li>
        <li class="breadcrumb-item active">Editar</li>
    </ol></nav>
</div>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.calendario.update', $evento) }}">
            @csrf @method('PUT')
            @include('admin.calendario._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Actualizar</button>
                <a href="{{ route('admin.calendario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
                <form method="POST" action="{{ route('admin.calendario.destroy', $evento) }}"
                      onsubmit="return confirm('¿Eliminar este evento?')" class="ms-auto">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-trash me-1"></i>Eliminar</button>
                </form>
            </div>
        </form>
    </div>
</div>
@endsection
