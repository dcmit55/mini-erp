@extends('errors.minimal')

@section('title', __('Conflict'))
@section('code', '409')
@section('message', __('Your request conflicts with the current state of the resource. Please refresh and try again.'))
