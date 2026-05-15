@extends('layouts.portal-estudiante')
@section('title', 'Calendario Escolar')

@section('activeKey', 'calendario')

@section('content')
@include('portal._calendar_widget')
@endsection
