@extends('errors.minimal')

@section('title', __('Unauthorized'))
@section('code', '401')
@section('message', __('You need to be authenticated to access this resource. Please log in and try again.'))
