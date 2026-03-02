@extends('errors.minimal')

@section('title', __('Gateway Timeout'))
@section('code', '504')
@section('message', __('The server took too long to respond. Please try again later.'))
