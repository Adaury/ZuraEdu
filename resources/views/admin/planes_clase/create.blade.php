@extends('layouts.admin')

@section('page-title', 'Nuevo Plan de Clase')

@section('content')
<div class="container-fluid px-4">
    <div class="mb-4">
        <h1 class="h3 mb-0">Nuevo Plan de Clase</h1>
        <nav aria-label="breadcrumb"><ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Inicio</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.planes-clase.index') }}">Planes de Clase</a></li>
            <li class="breadcrumb-item active">Nuevo</li>
        </ol></nav>
    </div>

    <form method="POST" action="{{ route('admin.planes-clase.store') }}" enctype="multipart/form-data">
        @csrf
        @include('admin.planes_clase._form', ['plan' => null])
    </form>
</div>
@endsection
