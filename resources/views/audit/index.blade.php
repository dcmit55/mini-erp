@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <!-- Header -->
            @include('audit.partials.header')
            
            <div class="card-body">
                <!-- Filter Form -->
                @include('audit.partials.filters')
                
                <!-- DataTable -->
                @include('audit.partials.table')
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('audit.partials.modals.changes')
    @include('audit.partials.modals.delete-by-date')
    @include('audit.partials.modals.purge-old')
@endsection

@push('styles')
    @include('audit.styles')
@endpush

@push('scripts')
    @include('audit.scripts')
@endpush