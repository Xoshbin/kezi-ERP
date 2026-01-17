@extends('projectmanagement::layouts.master')

@section('content')
    <h1>{{ __('project_management::project.navigation.name') }}</h1>

    <p>Module: {!! config('projectmanagement.name') !!}</p>
@endsection
