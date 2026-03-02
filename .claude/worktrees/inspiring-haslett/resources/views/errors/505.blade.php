@extends('errors.minimal')

@section('title', __('HTTP Version Not Supported'))
@section('code', '505')
@section('message', __('The HTTP version used in the request is not supported by the server.'))
