@extends('adminlte::page')

@section('title', "Gestione Turni Presse")

@section('content_header')
@stop

@section('content')
    @include('gestione_turni_presse.table', ['page' => 'Gestione Turni Presse'])
@endsection