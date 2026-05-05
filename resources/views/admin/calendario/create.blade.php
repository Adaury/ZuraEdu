@extends('layouts.admin')

@section('page-title', 'Nuevo Evento')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-calendar-plus me-2"></i>Nuevo Evento</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.calendario.index') }}">Calendario</a></li>
        <li class="breadcrumb-item active">Nuevo Evento</li>
    </ol></nav>
</div>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.calendario.store') }}">
            @csrf
            @include('admin.calendario._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i>Guardar Evento</button>
                <a href="{{ route('admin.calendario.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
