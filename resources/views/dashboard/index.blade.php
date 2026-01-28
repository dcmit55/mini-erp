@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4 dashboard-container">
        <!-- Header dengan Welcome & Clock -->
        @include('dashboard.partials.header')
        
        <!-- Key Metrics Cards -->
        @include('dashboard.partials.metrics')
        
        <!-- Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Monthly Trends Chart -->
            <div class="col-xl-8">
                @include('dashboard.partials.charts.trends')
            </div>
            
            <!-- Request Status Chart -->
            <div class="col-xl-4">
                @include('dashboard.partials.charts.status')
            </div>
        </div>

        <!-- Recent Activities Section -->
        <div class="row g-4 mb-4">
            <!-- Recent Material Requests -->
            <div class="col-xl-6">
                @include('dashboard.partials.activities.recent-requests')
            </div>
            
            <!-- Low Stock Items -->
            @if(isset($veryLowStockItems) && $veryLowStockItems->count() > 0)
                <div class="col-xl-6">
                    @include('dashboard.partials.activities.low-stock')
                </div>
            @endif
        </div>

        <!-- Overview Section -->
        <div class="row g-4 mb-4">
            <!-- Department Overview -->
            <div class="col-xl-8">
                @include('dashboard.partials.overview.department')
            </div>
            
            <!-- Upcoming Deadlines -->
            <div class="col-xl-4">
                @include('dashboard.partials.overview.deadlines')
            </div>
        </div>

        <!-- Super Admin Actions -->
        @if($user->role === 'super_admin')
            @include('dashboard.partials.admin-actions')
        @endif
    </div>
@endsection

@push('styles')
    @include('dashboard.styles')
@endpush

@push('scripts')
    @include('dashboard.scripts')
@endpush