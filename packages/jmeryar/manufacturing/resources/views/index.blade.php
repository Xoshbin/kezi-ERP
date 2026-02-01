@extends('manufacturing::layouts.master')

@section('content')
    <h1>{{ __('manufacturing::navigation.name') }}</h1>

    <p>Module: {!! config('manufacturing.name') !!}</p>
@endsection
