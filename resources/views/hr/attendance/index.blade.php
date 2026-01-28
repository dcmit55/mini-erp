@extends('layouts.app')

@section('title', 'Attendance Management')

@section('content')
    <div class="container-fluid py-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <!-- Header -->
                @include('hr.attendance.partials.header')
                
                <!-- Summary Cards -->
                @include('hr.attendance.partials.summary-cards')
                
                <!-- Skill Gap Alert -->
                @if ($skillGapAnalysis['total_affected_employees'] > 0)
                    @include('hr.attendance.partials.skill-gap-alert')
                @endif
                
                <!-- Filters -->
                @include('hr.attendance.partials.filters')
            </div>
        </div>

        <!-- Employee List -->
        <div class="card shadow-sm rounded-3">
            <div class="card-body">
                @include('hr.attendance.partials.employee-list')
            </div>
        </div>
    </div>

    <!-- Modals -->
    @include('hr.attendance.partials.modals.late')
    @include('hr.attendance.partials.modals.bulk-late')
    
    @if ($skillGapAnalysis['total_affected_employees'] > 0)
        @include('hr.attendance.partials.modals.skill-gap')
    @endif
    
    <!-- Toast Notification -->
    @include('hr.attendance.partials.toast')
@endsection

@push('styles')
    @include('hr.attendance.styles')
@endpush

@push('scripts')
    @include('hr.attendance.scripts')
@endpush