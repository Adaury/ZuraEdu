@extends('layouts.portal')
@section('page-title', 'Calendario Escolar')
@section('portal-name', 'Portal Padre')

@section('sidebar')
    @include('portal.padre._sidebar', ['activeKey' => 'calendario'])
@endsection

@section('bottom-nav')
    <a href="{{ route('portal.padre.dashboard') }}" class="prt-nav-item">
        <i class="bi bi-house-fill"></i>Inicio
    </a>
    <a href="{{ route('portal.padre.comunicados') }}" class="prt-nav-item">
        <i class="bi bi-megaphone"></i>Noticias
    </a>
    <a href="{{ route('portal.padre.calendario') }}" class="prt-nav-item active">
        <i class="bi bi-calendar3"></i>Calendario
    </a>
@endsection

@section('content')
@include('portal._calendar_widget')
@endsection
