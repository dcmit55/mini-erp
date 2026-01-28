@extends('errors.minimal')

@section('title', __('Forbidden'))
@section('code', '403')
@section('message', __('Access denied! You don\'t have the necessary permissions to view this resource.'))
