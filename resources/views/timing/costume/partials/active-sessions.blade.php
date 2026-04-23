{{-- Active Sessions - Individual Cards with Individual Stop Buttons --}}
@if ($activeSessions->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="bi bi-clock-history" style="font-size: 3rem;"></i>
        <p class="mt-3 mb-0">No active work sessions</p>
        <small>Start a new session to track production time</small>
    </div>
@else
    @foreach ($activeSessions as $session)
        @php
            // Calculate deadline based on job order settings
            $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
            $standardTimePerUnit = $session->jobOrder->standard_time_per_unit ?? 0;
            $deadlineTime = null;
            $deadlineWarning = null;

            // Priority 1: Use total_standard_minutes if available (for progress-based jobs)
            // Priority 2: Skip deadline for qty-based (Costume) since it's dynamic based on actual qty
if ($totalMinutes > 0 && $session->start_time) {
    try {
        $startDateTime = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $session->start_time);
        $deadlineTime = $startDateTime->addMinutes($totalMinutes)->format('H:i');

        // Calculate time remaining
        $now = \Carbon\Carbon::now();
        $deadline = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $deadlineTime);
        $minutesRemaining = $now->diffInMinutes($deadline, false);

        if ($minutesRemaining < 0) {
            $deadlineWarning = 'exceeded';
        } elseif ($minutesRemaining <= 15) {
            $deadlineWarning = 'critical';
        } elseif ($minutesRemaining <= 30) {
            $deadlineWarning = 'warning';
                    }
                } catch (\Exception $e) {
                    $deadlineTime = null;
                }
            }
        @endphp

        @php
            $isFrozen = $session->isFrozen();
            $deptData = $session->department_specific_data ?? [];
            $isAutoBreak = !empty($deptData['auto_break_paused']);
            $sessionType = $session->session_type ?? 'mass_production';
            $isRepair = $sessionType === 'repair';

            // Color scheme: repair = orange border, mass_production = green border
            $cardBorderClass = $isFrozen
                ? 'border-warning border-2'
                : ($isRepair
                    ? 'border-orange border-2'
                    : 'border-success border-2');
            $runningBadgeClass = $isRepair ? 'bg-orange' : 'bg-success';
            $runningBadgeStyle = $isRepair ? 'background-color:#fd7e14!important;' : '';
            $durationColorClass = $isRepair ? '' : 'text-success';
            $durationStyle = $isRepair ? 'color:#fd7e14;' : '';
        @endphp
        <div class="card session-card mb-3 {{ $cardBorderClass }}" id="session-card-{{ $session->id }}"
            data-session-id="{{ $session->id }}">
            <div class="card-body p-3">
                {{-- Employee Info Header --}}
                <div class="d-flex align-items-center mb-2">
                    @if ($session->employee->photo)
                        <img src="{{ asset('storage/' . $session->employee->photo) }}" class="rounded-circle me-2"
                            width="40" height="40" style="object-fit: cover;">
                    @else
                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2"
                            style="width: 40px; height: 40px;">
                            <i class="bi bi-person text-white"></i>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <h6 class="mb-0">
                            @if ($isFrozen)
                                <span class="badge bg-warning text-dark me-1"><i class="bi bi-pause-circle"></i>
                                    PAUSED{{ $isAutoBreak ? ' (BREAK)' : '' }}</span>
                            @elseif ($isRepair)
                                <span class="badge me-1" style="background-color:#fd7e14;"><i class="bi bi-tools"></i>
                                    REPAIR</span>
                            @else
                                <span class="badge bg-success me-1"><i class="bi bi-grid-3x3-gap-fill"></i> MASS
                                    PROD</span>
                            @endif
                            {{ $session->employee->name }}
                        </h6>
                        <small class="text-muted">{{ $session->employee->position ?? 'N/A' }}</small>
                    </div>
                    @if ($isFrozen)
                        <span class="fs-5 fw-bold text-warning">{{ $deptData['frozen_duration'] ?? '00:00:00' }}</span>
                    @else
                        <span class="duration-display fs-5 fw-bold {{ $durationColorClass }}"
                            style="{{ $durationStyle }}" data-start-time="{{ $session->start_time }}"
                            data-session-id="{{ $session->id }}">
                            00:00:00
                        </span>
                    @endif
                </div>

                {{-- Work Details --}}
                <div class="border-top pt-2 mb-2">
                    <div class="row g-2 small">
                        <div class="col-12">
                            <strong>Job Order:</strong> {{ $session->jobOrder->name ?? $session->job_order_id }}<br>
                            <strong>Project:</strong> {{ $session->project->name ?? 'N/A' }}
                        </div>
                        <div class="col-6">
                            <strong>Step:</strong> {{ $session->step }}
                        </div>
                        <div class="col-6">
                            <strong>Part:</strong> {{ $session->parts }}
                        </div>
                        <div class="col-12">
                            <small class="text-muted">
                                <i class="bi bi-clock"></i> Started: {{ $session->start_time }}
                            </small>
                        </div>
                        @if ($deadlineTime)
                            <div class="col-12">
                                <small class="text-muted">
                                    <i class="bi bi-calendar-x"></i> Target Deadline:
                                    <strong
                                        class="{{ $deadlineWarning === 'exceeded' ? 'text-danger' : ($deadlineWarning === 'critical' ? 'text-warning' : '') }}">{{ $deadlineTime }}</strong>
                                    <span class="badge badge-sm bg-info ms-1">{{ $totalMinutes }} min</span>
                                    @if ($deadlineWarning === 'exceeded')
                                        <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i>
                                            OVERDUE</span>
                                    @elseif ($deadlineWarning === 'critical')
                                        <span class="badge bg-warning text-dark ms-1"><i
                                                class="bi bi-clock-history"></i> &lt;15 min</span>
                                    @elseif ($deadlineWarning === 'warning')
                                        <span class="badge bg-warning text-dark ms-1"><i
                                                class="bi bi-hourglass-split"></i> &lt;30 min</span>
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Individual Stop Button --}}
                <div class="d-flex gap-2">
                    <button class="btn btn-danger btn-sm stop-work-btn flex-grow-1"
                        data-timing-id="{{ $session->id }}" data-employee-id="{{ $session->employee_id }}"
                        data-employee-name="{{ $session->employee->name }}"
                        data-job-order="{{ $session->jobOrder->name ?? $session->job_order_id }}">
                        <i class="bi bi-stop-circle me-1"></i>STOP WORK & ENTER QTY
                    </button>
                    <button class="btn btn-outline-info btn-sm flex-shrink-0 detail-modal-btn"
                        data-timing-id="{{ $session->id }}" title="View Detail">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        </div>
    @endforeach
@endif
