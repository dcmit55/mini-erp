@extends('layouts.app')

@section('title', 'Overtime Pay Detail')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Overtime Pay Detail #{{ $payDetail->id }}</h5>
        <div>
            <a href="{{ route('overtime-pays.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                <i class="fas fa-arrow-left me-1"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2">Request Information</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th style="width: 120px;">Employee</th><td>{{ $payDetail->employee->name ?? '-' }}</td></tr>
                        <tr><th>OT Code</th><td>{{ $payDetail->ot_code }}</td></tr>
                        <tr><th>Net Hours</th><td>{{ $payDetail->net_hours_formatted }}</td></tr>
                        <tr><th>Hourly Rate</th><td>Rp {{ number_format($payDetail->hourly_rate, 0, ',', '.') }}</td></tr>
                        <tr><th>Total Pay</th><td class="fw-bold text-primary">Rp {{ number_format($payDetail->total_pay, 0, ',', '.') }}</td></tr>
                        <tr><th>Calculated At</th><td>{{ $payDetail->calculated_at->format('d M Y H:i') }}</td></tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-2">Calculation Breakdown</div>
                <div class="card-body">
                    @if($payDetail->breakdown)
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Segment</th>
                                    <th class="text-end">Hours</th>
                                    <th class="text-end">Rate</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($payDetail->breakdown as $item)
                                <tr>
                                    <td>{{ $item['segment'] }}</td>
                                    <td class="text-end">{{ number_format($item['hours'], 2) }}</td>
                                    <td class="text-end">{{ $item['rate'] }}x</td>
                                    <td class="text-end">Rp {{ number_format($item['amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                                <tr class="table-active">
                                    <td colspan="3" class="fw-bold">Total</td>
                                    <td class="text-end fw-bold text-primary">Rp {{ number_format($payDetail->total_pay, 0, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted">No breakdown available.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 