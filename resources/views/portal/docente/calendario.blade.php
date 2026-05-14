@extends('layouts.portal')
@section('page-title', 'Calendario Escolar')
@section('portal-name', 'Portal Docente')

@section('sidebar')
    @include('portal.docente._sidebar_clase', ['activeKey' => 'calendario'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.docente.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.docente.mis-planificaciones') }}" class="prt-nav-item">
        <i class="bi bi-journal-bookmark"></i>Planes
    </a>
    <a href="{{ route('portal.docente.calendario') }}" class="prt-nav-item active">
        <i class="bi bi-calendar3"></i>Calendario
    </a>
@endsection

@section('content')
@include('portal._calendar_widget')
@endsection
