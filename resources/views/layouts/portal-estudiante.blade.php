@extends('layouts.portal')

@section('portal-name', 'Portal del Estudiante')

@section('page-title')@yield('title', 'Portal Estudiante') — SGE@endsection

@section('sidebar')
    @include('portal.estudiante._sidebar', [
        'activeKey' => $__env->yieldContent('activeKey', 'dashboard'),
    ])
@endsection

@section('bottom-nav')
@php $__ak = $__env->yieldContent('activeKey', 'dashboard'); @endphp
<a href="{{ route('portal.estudiante.dashboard') }}"
   class="prt-nav-item {{ $__ak === 'dashboard' ? 'active' : '' }}">
    <i class="bi bi-house-fill"></i>Inicio
</a>
<a href="{{ route('portal.estudiante.boletin') }}"
   class="prt-nav-item {{ $__ak === 'boletin' ? 'active' : '' }}">
    <i class="bi bi-file-earmark-text"></i>Boletín
</a>
<a href="{{ route('portal.estudiante.solicitudes.index') }}"
   class="prt-nav-item {{ $__ak === 'solicitudes' ? 'active' : '' }}">
    <i class="bi bi-send-fill"></i>Solicitudes
</a>
<a href="{{ route('portal.estudiante.historial-academico') }}"
   class="prt-nav-item {{ $__ak === 'historial' ? 'active' : '' }}">
    <i class="bi bi-clock-history"></i>Historial
</a>
@endsection
