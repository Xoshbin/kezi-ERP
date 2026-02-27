@extends('qualitycontrol::layouts.master')

@section('content')
    <h1>{{ __('qualitycontrol::navigation.name') }}</h1>

    <p>Module: {!! config('qualitycontrol.name') !!}</p>
@endsection
