@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4">
    <div id="qc-app"
        data-auth-user="{{ json_encode($authUser) }}"
        data-csrf="{{ csrf_token() }}"
        data-context="mascot">
    </div>
</div>
@endsection

@push('styles')
    @vite(['resources/css/qc.css'])
@endpush

@push('scripts')
    @viteReactRefresh
    @vite(['resources/js/qc/main.jsx'])
@endpush
