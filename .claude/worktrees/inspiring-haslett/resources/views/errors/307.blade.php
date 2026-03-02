@extends('errors.minimal')

@section('title', __('Temporary Redirect'))
@section('code', '307')
@section('message', __('The resource has been temporarily moved and the request method must be preserved. Please try
    again later.'))
