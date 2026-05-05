@extends('layouts.admin')

@section('page-title', 'Nueva Especialidad')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-mortarboard me-2"></i>Nueva Especialidad Técnica</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.especialidades.index') }}">Especialidades</a></li>
        <li class="breadcrumb-item active">Nueva</li>
    </ol></nav>
</div>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.especialidades.store') }}">
            @csrf
            @include('admin.especialidades._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Crear Especialidad
                </button>
                <a href="{{ route('admin.especialidades.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
