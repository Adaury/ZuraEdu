@extends('layouts.portal')
@section('page-title', 'Calendario Escolar')
@section('portal-name', 'Portal Estudiante')

@section('sidebar')
    @include('portal.estudiante._sidebar', ['activeKey' => 'calendario'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.estudiante.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.estudiante.boletin') }}" class="prt-nav-item">
        <i class="bi bi-file-earmark-text"></i>Boletín
    </a>
    <a href="{{ route('portal.estudiante.asistencia') }}" class="prt-nav-item">
        <i class="bi bi-clipboard-check"></i>Asistencia
    </a>
    <a href="{{ route('portal.estudiante.calendario') }}" class="prt-nav-item active">
        <i class="bi bi-calendar3"></i>Calendario
    </a>
@endsection

@section('content')
@include('portal._calendar_widget')
@endsection
