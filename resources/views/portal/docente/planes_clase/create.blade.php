@extends('layouts.portal')
@section('page-title', 'Nuevo Plan de Clase')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'planes'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.calificaciones', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-journal-check"></i>Notas
    </a>
    <a href="{{ route('portal.docente.planes-clase.index', $asignacion) }}" class="prt-nav-item active">
        <i class="bi bi-journal-text"></i>Planes
    </a>
    <a href="{{ route('portal.docente.instrumentos.index', $asignacion) }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check-fill"></i>Instrum.
    </a>
@endsection

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div class="mb-4">
        <h2 class="h4 mb-1">Nuevo Plan de Clase</h2>
        <p class="text-muted small mb-0">
            {{ $asignacion->asignatura->nombre }} — {{ $asignacion->grupo->nombre_completo ?? '' }}
        </p>
    </div>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <form method="POST" action="{{ route('portal.docente.planes-clase.store', $asignacion) }}" enctype="multipart/form-data">
        @csrf
        @include('portal.docente.planes_clase._form')
    </form>
</div>
@endsection
