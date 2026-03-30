@if ($activeSessions->isEmpty())
    <div class="text-center text-muted py-5">
        <i class="fas fa-mask" style="font-size: 3rem;"></i>
        <p class="mt-3 mb-0">No active mascot sessions</p>
        <small>Start a new session to track mascot production</small>
    </div>
@else
    {{-- Individual session cards --}}
    @foreach ($activeSessions as $session)
        @php
            $departmentData = $session->department_specific_data ?? [];
            $previousProgress = $departmentData['previous_progress'] ?? 0;
            $isFrozen = $session->isFrozen();
            $isAutoBreak = !empty($departmentData['auto_break_paused']);

            // Calculate deadline based on total_standard_minutes
            $totalMinutes = $session->jobOrder->total_standard_minutes ?? 0;
            $deadlineTime = null;
            $deadlineWarning = null;
            if ($totalMinutes > 0 && $session->start_time) {
                try {
                    $startDateTime = \Carbon\Carbon::parse(date('Y-m-d') . ' ' . $session->start_time);
                    $deadlineTime = $startDateTime->addMinutes($totalMinutes)->format('H:i');

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

        <div class="card session-card mb-3 {{ $isFrozen ? 'border-warning border-2' : '' }}" id="session-card-{{ $session->id }}" data-session-id="{{ $session->id }}">
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
                                <span class="badge bg-warning text-dark me-1"><i class="bi bi-pause-circle"></i> PAUSED{{ $isAutoBreak ? ' (BREAK)' : '' }}</span>
                            @else
                                <span class="badge bg-success me-1">RUNNING</span>
                            @endif
                            {{ $session->employee->name }}
                        </h6>
                        <small class="text-muted">{{ $session->employee->position ?? 'N/A' }}</small>
                    </div>
                    @if ($isFrozen)
                        <span class="fs-5 fw-bold text-warning">
                            {{ $departmentData['frozen_duration'] ?? '00:00:00' }}
                        </span>
                    @else
                        <span class="duration-display fs-5 fw-bold text-success"
                            data-start-time="{{ $session->start_time }}" data-session-id="{{ $session->id }}">
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
                        <div class="col-12">
                            <strong>Task:</strong> {{ $session->step }}
                        </div>
                        <div class="col-12">
                            <strong>Previous Progress:</strong> {{ $previousProgress }}%
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
                                        <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle"></i> OVERDUE</span>
                                    @elseif ($deadlineWarning === 'critical')
                                        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-clock-history"></i> &lt;15 min</span>
                                    @elseif ($deadlineWarning === 'warning')
                                        <span class="badge bg-warning text-dark ms-1"><i class="bi bi-hourglass-split"></i> &lt;30 min</span>
                                    @endif
                                </small>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Action Buttons --}}
                @if ($isFrozen)
                    <div class="d-flex gap-2">
                        <button class="btn btn-success btn-sm unfreeze-work-btn flex-grow-1"
                            data-timing-id="{{ $session->id }}"
                            data-employee-name="{{ $session->employee->name }}">
                            <i class="bi bi-play-circle me-1"></i>Continue
                        </button>
                        <button class="btn btn-outline-info btn-sm flex-shrink-0 detail-modal-btn"
                            data-timing-id="{{ $session->id }}" title="View Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                @else
                    <div class="d-flex gap-2">
                        <button class="btn btn-warning btn-sm freeze-work-btn flex-shrink-0"
                            data-timing-id="{{ $session->id }}"
                            data-employee-name="{{ $session->employee->name }}">
                            <i class="bi bi-pause-circle me-1"></i>Pause
                        </button>
                        <button class="btn btn-danger btn-sm stop-work-btn flex-grow-1"
                            data-timing-id="{{ $session->id }}"
                            data-employee-id="{{ $session->employee_id }}"
                            data-employee-name="{{ $session->employee->name }}"
                            data-job-order="{{ $session->jobOrder->name ?? $session->job_order_id }}"
                            data-job-order-id="{{ $session->job_order_id }}"
                            data-previous-progress="{{ $previousProgress }}">
                            <i class="bi bi-stop-circle me-1"></i>STOP & SELECT STAGE
                        </button>
                        <button class="btn btn-outline-info btn-sm flex-shrink-0 detail-modal-btn"
                            data-timing-id="{{ $session->id }}" title="View Detail">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endif
