@extends('qualitycontrol::layouts.master')

@section('content')
    <h1>{{ __('quality_control::navigation.name') }}</h1>

    <p>Module: {!! config('qualitycontrol.name') !!}</p>
@endsection
