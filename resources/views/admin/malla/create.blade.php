@extends('layouts.admin')

@section('page-title', 'Agregar a Malla')

@section('content')
<div class="mb-4">
    <h4 class="fw-bold mb-0" style="color:#1e3a6e;"><i class="bi bi-grid-3x3 me-2"></i>Agregar Asignatura a la Malla</h4>
    <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0" style="font-size:.8rem;">
        <li class="breadcrumb-item"><a href="{{ route('admin.malla.index') }}">Malla Curricular</a></li>
        <li class="breadcrumb-item active">Agregar</li>
    </ol></nav>
</div>

<div class="card border-0 shadow-sm" style="max-width:680px;">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('admin.malla.store') }}">
            @csrf
            @include('admin.malla._form')
            <div class="d-flex gap-2 mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-1"></i>Agregar a la Malla
                </button>
                <a href="{{ route('admin.malla.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
