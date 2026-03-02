@extends('errors.minimal')

@section('title', __('Permanent Redirect'))
@section('code', '308')
@section('message',
    __('The resource has been permanently moved and the request method must be preserved. Please update
    your bookmarks.'))
