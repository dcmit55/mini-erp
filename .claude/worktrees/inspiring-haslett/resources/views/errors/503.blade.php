@extends('errors.minimal')

@section('title', __('Service Unavailable'))
@section('code', '503')
@section('message',
    __('The service is temporarily unavailable due to maintenance or overload. Please try again
    later.'))
