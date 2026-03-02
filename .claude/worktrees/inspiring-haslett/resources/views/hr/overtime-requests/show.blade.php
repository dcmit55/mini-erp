@extends('layouts.app')

@section('title', 'Overtime Request Detail')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <a href="{{ route('overtime-requests.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i>Back
            </a>
        </div>
        <div>
            @if($overtimeRequest->status == 'draft')
                <form action="{{ route('overtime-requests.submit', $overtimeRequest) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-success">Submit for Approval</button>
                </form>
                <a href="{{ route('overtime-requests.edit', $overtimeRequest) }}" class="btn btn-sm btn-warning">Edit</a>
                <form action="{{ route('overtime-requests.destroy', $overtimeRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this request?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                </form>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2">Request Details</div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th style="width: 120px;">Employee</th><td>{{ $overtimeRequest->employee->name ?? '-' }}</td></tr>
                        <tr><th>Department</th><td>{{ $overtimeRequest->department->name ?? '-' }}</td></tr>
                        <tr><th>Project</th><td>{{ $overtimeRequest->jobOrder->name ?? '-' }}</td></tr>
                        <tr><th>OT Code</th><td>{{ $overtimeRequest->ot_code }}</td></tr>
                        <tr><th>Reason</th><td>{{ $overtimeRequest->reason }}</td></tr>
                        <tr><th>Start Time</th><td>{{ $overtimeRequest->start_time->format('d M Y H:i') }}</td></tr>
                        <tr><th>End Time</th><td>{{ $overtimeRequest->end_time->format('d M Y H:i') }}</td></tr>
                        <tr><th>Total Hours</th><td>{{ number_format($overtimeRequest->total_hours, 2) }}</td></tr>
                        <tr><th>Break Deduction</th><td>{{ number_format($overtimeRequest->break_deduction, 2) }}</td></tr>
                        <tr><th>Net Hours</th><td>{{ $overtimeRequest->net_hours_formatted }}</td></tr>
                        <tr><th>Overall Status</th>
                            <td>
                                @php
                                    $statusColors = [
                                        'approved' => 'success',
                                        'rejected' => 'danger',
                                        'submitted' => 'info',
                                        'draft' => 'secondary',
                                    ];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$overtimeRequest->status] ?? 'secondary' }}">
                                    {{ ucfirst($overtimeRequest->status) }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <!-- Approval Status -->
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header bg-white py-2">Approval Status</div>
                <div class="card-body">
                    <h6 class="small fw-bold">HR Approval</h6>
                    <p>
                        Status: 
                        @if($overtimeRequest->hr_approval_status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($overtimeRequest->hr_approval_status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @else
                            <span class="badge bg-secondary">Pending</span>
                        @endif
                    </p>
                    @if($overtimeRequest->hr_approved_by)
                        <p class="small">By: {{ $overtimeRequest->hrApprover->name ?? '-' }} at {{ $overtimeRequest->hr_approved_at?->format('d M Y H:i') }}</p>
                    @endif

                    @if(auth()->user()->role == 'hr' && $overtimeRequest->hr_approval_status == 'pending' && $overtimeRequest->status == 'submitted')
                        <div class="d-flex gap-1">
                            <form action="{{ route('overtime-requests.approve-hr', $overtimeRequest) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="{{ route('overtime-requests.approve-hr', $overtimeRequest) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </div>
                    @endif

                    <hr class="my-2">

                    <h6 class="small fw-bold">Director Approval</h6>
                    <p>
                        Status: 
                        @if($overtimeRequest->director_approval_status == 'approved')
                            <span class="badge bg-success">Approved</span>
                        @elseif($overtimeRequest->director_approval_status == 'rejected')
                            <span class="badge bg-danger">Rejected</span>
                        @else
                            <span class="badge bg-secondary">Pending</span>
                        @endif
                    </p>
                    @if($overtimeRequest->director_approved_by)
                        <p class="small">By: {{ $overtimeRequest->directorApprover->name ?? '-' }} at {{ $overtimeRequest->director_approved_at?->format('d M Y H:i') }}</p>
                    @endif

                    @if(auth()->user()->role == 'director' && $overtimeRequest->director_approval_status == 'pending' && $overtimeRequest->hr_approval_status == 'approved' && $overtimeRequest->status == 'submitted')
                        <div class="d-flex gap-1">
                            <form action="{{ route('overtime-requests.approve-director', $overtimeRequest) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="approve">
                                <button type="submit" class="btn btn-sm btn-success">Approve</button>
                            </form>
                            <form action="{{ route('overtime-requests.approve-director', $overtimeRequest) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="action" value="reject">
                                <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function calculatePay() {
    fetch('{{ route('overtime-requests.calculate-pay', $overtimeRequest) }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('payResult').innerHTML = 'Total Pay: Rp ' + data.total_pay.toLocaleString('id-ID');
        })
        .catch(err => {
            document.getElementById('payResult').innerHTML = 'Error calculating pay.';
        });
}
</script>
@endsection