@extends('errors.minimal')

@section('title', __('Bad Gateway'))
@section('code', '502')
@section('message', __('The server received an invalid response from the upstream server. Please try again.'))
