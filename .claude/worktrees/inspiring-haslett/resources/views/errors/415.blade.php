@extends('errors.minimal')

@section('title', __('Unsupported Media Type'))
@section('code', '415')
@section('message', __('The file format you are trying to upload is not supported. Please use a different format.'))
