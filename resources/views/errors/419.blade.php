@extends('errors::layout')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('Page Expired'))
@section('description', __('errors.http.419'))

@section('action')
    <flux:button :href="route('login')" variant="outline" color="purple">{{ __('Log in') }}</flux:button>
@endsection
