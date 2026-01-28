@extends('errors.minimal')

@section('title', __('Internal Server Error'))
@section('code', '500')
@section('message', __('Something went wrong on our end. Our team has been notified and is working to fix this issue.'))
