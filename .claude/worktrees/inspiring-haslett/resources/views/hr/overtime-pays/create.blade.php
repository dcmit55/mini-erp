@extends('layouts.app')

@section('title', 'New Overtime Pay Calculation')

@section('content')
<div class="container-fluid py-3">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="text-dark mb-1 mt-2">New Overtime Pay Calculation</h5>
                    <p class="text-muted small mb-0">Select approved and verified (Pass) overtime requests to calculate payment</p>
                </div>
                <a href="{{ route('overtime-pays.index') }}" class="btn btn-outline-secondary btn-sm rounded-2 px-3">
                    <i class="fas fa-arrow-left me-1"></i> Back
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-4">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form method="POST" action="{{ route('overtime-pays.store') }}">
                        @csrf
                        <div class="mb-4">
                            <label class="form-label fw-medium">Select Overtime Requests</label>
                            @if($pendingRequests->isEmpty())
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i> No approved and verified overtime requests pending calculation.
                                </div>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th><input type="checkbox" id="select-all"></th>
                                                <th>ID</th>
                                                <th>Employee</th>
                                                <th>Date</th>
                                                <th>OT Code</th>
                                                <th>Net Hours</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($pendingRequests as $req)
                                            <tr>
                                                <td>
                                                    <input type="checkbox" name="request_ids[]" value="{{ $req->id }}" class="request-checkbox">
                                                </td>
                                                <td>{{ $req->id }}</td>
                                                <td>{{ $req->employee->name }}</td>
                                                <td>{{ $req->start_time->format('d/m/Y') }}</td>
                                                <td>{{ $req->ot_code }}</td>
                                                <td>{{ number_format($req->net_hours, 2) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary rounded-2 px-4" {{ $pendingRequests->isEmpty() ? 'disabled' : '' }}>
                                <i class="fas fa-calculator me-2"></i>Calculate Selected
                            </button>
                            <a href="{{ route('overtime-pays.index') }}" class="btn btn-outline-secondary rounded-2 px-4">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select all checkbox
    const selectAll = document.getElementById('select-all');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
        });
    }
});
</script>
@endsection