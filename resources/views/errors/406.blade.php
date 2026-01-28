@extends('errors.minimal')

@section('title', __('Not Acceptable'))
@section('code', '406')
@section('message',
    __('The server cannot produce a response that matches your request criteria. Please modify your
    request.'))
