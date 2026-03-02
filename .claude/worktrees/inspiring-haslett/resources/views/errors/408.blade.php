@extends('errors.minimal')

@section('title', __('Request Timeout'))
@section('code', '408')
@section('message', __('Your request took too long to process. Please try again or check your connection.'))
