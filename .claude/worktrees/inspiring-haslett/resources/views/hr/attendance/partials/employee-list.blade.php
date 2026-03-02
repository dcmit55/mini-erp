<!-- Selection Checkbox -->
<div class="form-check mb-3">
    <input class="form-check-input" type="checkbox" id="select-all">
    <label class="form-check-label fw-bold" for="select-all">
        Select All (<span id="selected-count">0</span> selected)
    </label>
</div>

<!-- Employee Cards List -->
<div id="employee-list">
    @forelse($employees as $employee)
        <div class="card mb-3 employee-card border-0 shadow-sm rounded-3 fade-in"
            data-employee-id="{{ $employee->id }}">
            <div class="card-body py-3 px-2">
                <div class="row align-items-center">
                    <!-- Checkbox -->
                    <div class="col-auto">
                        <input type="checkbox" class="form-check-input employee-checkbox"
                            value="{{ $employee->id }}">
                    </div>

                    <!-- Employee Info -->
                    <div class="col-12 col-md-4 d-flex align-items-center" style="gap: 16px;">
                        @if($employee->avatar_url)
                            <img src="{{ $employee->avatar_url }}" alt="{{ $employee->name }}" 
                                 class="rounded-circle" width="40" height="40">
                        @else
                            <div class="avatar-circle">
                                {{ substr($employee->name, 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h6 class="mb-0 fw-bold">{{ $employee->name }}</h6>
                            <div class="d-flex flex-wrap gap-2 mt-1">
                                <small class="text-muted">
                                    <i class="bi bi-briefcase"></i> {{ $employee->position ?? 'N/A' }}
                                </small>
                                <small class="text-muted">
                                    <i class="bi bi-building"></i> {{ $employee->department->name ?? 'N/A' }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Status Buttons -->
                    <div class="col-12 col-md-4 mt-2 mt-md-0">
                        <div class="d-flex flex-wrap gap-2 status-btn-group">
                            <button type="button"
                                class="btn status-btn {{ $employee->attendance_status == 'present' ? 'btn-success active' : 'btn-outline-success' }} rounded-pill"
                                data-status="present" data-employee-id="{{ $employee->id }}">
                                <i class="bi bi-check-circle"></i> Present
                            </button>
                            <button type="button"
                                class="btn status-btn {{ $employee->attendance_status == 'absent' ? 'btn-danger active' : 'btn-outline-danger' }} rounded-pill"
                                data-status="absent" data-employee-id="{{ $employee->id }}">
                                <i class="bi bi-x-circle"></i> Absent
                            </button>
                            <button type="button"
                                class="btn status-btn {{ $employee->attendance_status == 'late' ? 'btn-warning active' : 'btn-outline-warning' }} rounded-pill"
                                data-status="late" data-employee-id="{{ $employee->id }}"
                                onclick="openLateModal({{ $employee->id }})">
                                <i class="bi bi-clock"></i> Late
                            </button>
                        </div>
                    </div>

                    <!-- Recorded Time -->
                    <div class="col-12 col-md-2 text-md-end mt-2 mt-md-0">
                        @if ($employee->recorded_time)
                            <div class="recorded-time">
                                <small class="text-muted">
                                    <i class="bi bi-clock-history"></i>
                                    Recorded at:
                                    {{ \Carbon\Carbon::parse($employee->recorded_time)->format('h:i A') }}
                                </small>
                            </div>
                        @else
                            <div class="recorded-time">
                                <small class="text-muted">
                                    <i class="bi bi-dash-circle"></i>
                                    Not recorded
                                </small>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="alert alert-info rounded-3 shadow-sm">
            <i class="bi bi-info-circle"></i> No employees found. Try adjusting your filters.
        </div>
    @endforelse
</div>