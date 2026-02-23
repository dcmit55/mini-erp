@extends('layouts.app')

@section('title', 'Overtime Pay Calculations')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">Overtime Pay Calculations</h5>
                    <p class="text-muted small mb-0">List of calculated overtime payments</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-money-bill-wave text-primary"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Amount</h6>
                                    <h4 class="mb-0 text-primary">Rp {{ number_format($totalAmount, 0, ',', '.') }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-info bg-opacity-10 rounded-2 p-2 me-3">
                                    <i class="fas fa-calculator text-info"></i>
                                </div>
                                <div>
                                    <h6 class="text-muted small mb-1">Total Data</h6>
                                    <h4 class="mb-0 text-info">{{ $payDetails->total() }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('overtime-pays.index') }}" class="row g-2">
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Employee</label>
                            <select name="employee_id" class="form-select border-1 rounded-2 py-2 px-3 form-select-sm">
                                <option value="">All Employees</option>
                                @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                        {{ $emp->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-dark">Start Date</label>
                            <input type="date" name="start_date" class="form-control border-1 rounded-2 py-2 px-3 form-control-sm" value="{{ request('start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-dark">End Date</label>
                            <input type="date" name="end_date" class="form-control border-1 rounded-2 py-2 px-3 form-control-sm" value="{{ request('end_date') }}">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm rounded-2 px-3" title="Filter">
                                    <i class="fas fa-filter"></i>
                                </button>
                                <a href="{{ route('overtime-pays.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3" title="Reset">
                                    <i class="fas fa-sync-alt"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">No</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Employee</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">OT Date</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">OT Code</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Net Hours</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Hourly Rate</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2 text-end">Total Pay</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2">Calculated At</th>
                                    <th class="border-0 small text-dark fw-medium px-3 py-2 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payDetails as $index => $pay)
                                    <tr>
                                        <td class="px-3 py-2 text-center text-muted">{{ $payDetails->firstItem() + $index }}</td>
                                        <td class="px-3 py-2">
                                            <span class="fw-medium">{{ $pay->employee->name ?? '-' }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ $pay->overtimeRequest->start_time->format('d/m/Y') }}</td>
                                        <td class="px-3 py-2">
                                            <span class="badge bg-light text-dark px-2 py-1">{{ $pay->ot_code }}</span>
                                        </td>
                                        <td class="px-3 py-2">{{ $pay->net_hours_formatted }}</td>
                                        <td class="px-3 py-2">Rp {{ number_format($pay->hourly_rate, 0) }}</td>
                                        <td class="px-3 py-2 text-end fw-medium text-primary">Rp {{ number_format($pay->total_pay, 0, ',', '.') }}</td>
                                        <td class="px-3 py-2">{{ $pay->calculated_at->format('d/m/Y H:i') }}</td>
                                        <td class="px-3 py-2 text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a href="{{ route('overtime-pays.show', $pay->id) }}" class="btn btn-sm btn-outline-info border-0 px-2" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <form action="{{ route('overtime-pays.destroy', $pay->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this calculation?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger border-0 px-2" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center py-4 text-muted">
                                            <i class="fas fa-calculator fa-2x mb-2"></i>
                                            <p class="mb-0">No pay calculations found</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($payDetails->hasPages())
                        <div class="card-footer border-0 bg-light px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="text-muted small">
                                    Showing {{ $payDetails->firstItem() }} to {{ $payDetails->lastItem() }} of {{ $payDetails->total() }} entries
                                </div>
                                {{ $payDetails->appends(request()->query())->links() }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .badge { font-weight: 500; }
    .table td { vertical-align: middle; }
</style>
@endsection