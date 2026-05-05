@extends('layouts.admin')
@section('page-title', 'Editar Comunicado')
@section('content')

<div class="d-flex align-items-center gap-3 mb-4">
    <a href="{{ route('admin.comunicados.index') }}" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i>Volver
    </a>
    <h4 class="fw-bold mb-0" style="color:var(--primary)"><i class="bi bi-pencil-square me-2"></i>Editar Comunicado</h4>
</div>

@if($errors->any())
<div class="alert alert-danger mb-3" style="border-radius:10px;">
    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form method="POST" action="{{ route('admin.comunicados.update', $comunicado) }}">
    @csrf @method('PUT')
    @include('admin.comunicados._form')
</form>
@endsection
