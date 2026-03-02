@extends('errors.minimal')

@section('title', __('Multiple Representations'))
@section('code', '310')
@section('message',
    __('The resource requested has multiple representations available. This is an experimental status
    code for content negotiation.'))
