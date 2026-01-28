@extends('errors.minimal')

@section('title', __('Length Required'))
@section('code', '411')
@section('message', __('The server requires a valid Content-Length header to process your request.'))
