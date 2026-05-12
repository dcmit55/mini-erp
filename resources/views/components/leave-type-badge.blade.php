@php
    $color     = \App\Models\Hr\LeaveRequest::getTypeBadgeClass($type);
    $textClass = $color === 'warning' ? 'text-dark' : '';
    $label     = ($labels ?? \App\Models\Hr\LeaveRequest::getTypeLabels())[strtoupper($type)] ?? $type;
@endphp
<span class="badge bg-{{ $color }} {{ $textClass }} fw-normal" style="white-space:normal;">{{ $label }}</span>
