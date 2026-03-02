@extends('errors.minimal')

@section('title', __('Bad Request'))
@section('code', '400')
@section('message',
    __('Oops! The server couldn\'t understand your request due to invalid syntax. Please check your
    input and try again.'))
