@extends('projectmanagement::layouts.master')

@section('content')
    <h1>{{ __('projectmanagement::project.navigation.name') }}</h1>

    <p>Module: {!! config('projectmanagement.name') !!}</p>
@endsection
