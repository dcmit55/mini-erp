@extends('errors.minimal')

@section('title', __('Loop Detected'))
@section('code', '508')
@section('message', __('The server detected an infinite loop while processing the request.'))
