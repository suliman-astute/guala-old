@extends('adminlte::page')

@section('title', "Gestione Turni")

@section('content_header')
@stop

@section('content')
    @include('gestione_turni.table', ['page' => 'Gestione Turni'])
@endsection