@extends('errors.minimal')

@section('title', __('Not Modified'))
@section('code', '304')
@section('message', __('The resource has not been modified since your last request. Your cached version is still valid
    and current.'))
