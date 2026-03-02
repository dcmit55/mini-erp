@extends('errors.minimal')

@section('title', __('Payload Too Large'))
@section('code', '413')
@section('message', __('The file or data you are trying to upload is too large. Please reduce the size and try again.'))
