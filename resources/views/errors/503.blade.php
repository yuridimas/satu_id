@extends('errors::layout')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message', __('Service Unavailable'))
@section('description', __('errors.http.503'))
