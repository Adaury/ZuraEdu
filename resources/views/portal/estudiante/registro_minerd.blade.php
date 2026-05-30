@extends('layouts.portal')
@section('page-title', 'Mi Registro MINERD')
@section('portal-name', 'Portal Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'registro-minerd'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.boletin') }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text-fill"></i>Boletín
    </a>
    <a href="{{ route('portal.estudiante.registro-minerd') }}" class="prt-nav-item active">
        <i class="bi bi-table"></i>MINERD
    </a>
    <a href="{{ route('portal.estudiante.asistencia') }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check"></i>Asistencia
    </a>
@endsection

@push('styles')
@include('portal._partials.registro_minerd_styles')
@endpush

@section('content')

<div class="d-flex align-items-center gap-2 mb-3">
    <div style="width:38px;height:38px;background:linear-gradient(135deg,#1e3a6e,#4f46e5);
                border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
        <i class="bi bi-table" style="color:#fff;font-size:1rem;"></i>
    </div>
    <div>
        <h1 style="font-size:1.2rem;font-weight:800;color:var(--primary);margin:0;">
            Mi Registro Académico MINERD
        </h1>
        <p class="text-muted mb-0" style="font-size:.78rem;">
            Competencias Específicas e Indicadores de Logro — Solo lectura
        </p>
    </div>
</div>

@php $nombreAlumno = $estudiante->nombre_completo ?? ($estudiante->apellidos.', '.$estudiante->nombres); @endphp

@include('portal._partials.registro_minerd_cuerpo')

@endsection

@push('scripts')
@include('portal._partials.registro_minerd_scripts')
@endpush
