@extends('adminlte::page')

@section('title', 'WELCOME')

@section('content')
    <div class="card shadow mb-4 mt-2">
        <div class="card-body">
            <div class="row">
                <div class="col text-center p-5">
                    <h1>Benvenuto in GualaApp</h1>
                    <img src="{{ asset('images/logo.jpg') }}" class="img-fluid my-5">
                </div>
            </div>


            @if (!Auth::user()->admin)

                <div class="row">

                    @php
                        $pivot = "";
                    @endphp

                    @foreach (Auth::user()->active_apps()->orderby("site_id")->orderby("name_en")->get() as $active_app)
                        @if ($pivot != $active_app->site->name)
                            @php $pivot = $active_app->site->name; @endphp

                            <div class="col-12 my-3">
                                <h5>
                                    {{ $active_app->site->name }}
                                </h5>
                            </div>
                        @endif

                        <div class="col-4 my-3">
                            <div class="info-box" onclick="location.href='{{ $active_app->code }}'">
                                <span class="info-box-icon bg-info"><img src="{{$active_app->icon_link}}" alt="Icon" class="w-100 img-fluid img-thumbnail"></span>
                                <div class="info-box-content">
                                    <h4 class="info-box-text">{{ $active_app->name }}</h4>
                                   <!--  <span class="info-box-number">Alert: 0</span> -->
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@stop
