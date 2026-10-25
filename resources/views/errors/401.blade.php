@extends('errors::layout')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('Unauthorized'))
@section('description', __('errors.http.401'))

@section('action')
    <flux:button :href="route('login')" variant="outline" color="purple">{{ __('Log in') }}</flux:button>
@endsection
