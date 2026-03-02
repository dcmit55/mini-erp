@extends('errors.minimal')

@section('title', __('Unprocessable Entity'))
@section('code', '422')
@section('message', __('There are validation errors in your submitted data. Please check your input and try again.'))
