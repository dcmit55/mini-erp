@extends('errors.minimal')

@section('title', __('Method Not Allowed'))
@section('code', '405')
@section('message',
    __('The HTTP method used is not allowed for this resource. Please check your request and try
    again.'))
