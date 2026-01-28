<form id="filter-form" method="GET" action="{{ route('attendance.index') }}">
    <div class="row g-3">
        <div class="col-6 col-md-2">
            <label class="form-label fw-bold">Department</label>
            <select name="department_id" class="form-select rounded-pill">
                <option value="">All</option>
                @foreach ($departments as $dept)
                    <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>
                        {{ $dept->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-bold">Position</label>
            <select name="position" class="form-select rounded-pill">
                <option value="">All</option>
                @foreach ($positions as $pos)
                    <option value="{{ $pos }}" {{ request('position') == $pos ? 'selected' : '' }}>
                        {{ $pos }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-bold">Status</label>
            <select name="status" class="form-select rounded-pill">
                <option value="">All</option>
                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label fw-bold">Date</label>
            <input type="date" name="date" class="form-control rounded-pill" value="{{ $date }}"
                max="{{ now()->format('Y-m-d') }}" required>
        </div>
        <div class="col-12 col-md-3">
            <label class="form-label fw-bold">Search</label>
            <input type="text" name="search" class="form-control rounded-pill" placeholder="Employee name..."
                value="{{ request('search') }}">
        </div>
        <div class="col-12 col-md-1 d-flex align-items-end">
            <button type="submit" class="btn btn-primary rounded-pill px-3 py-2" style="min-width: 120px;">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </div>
</form>

<!-- Bulk Action Buttons -->
<div class="row mt-3">
    <div class="col-12">
        <div class="row g-2 justify-content-center justify-content-md-start bulk-actions-container">
            <div class="col-4 col-md-auto">
                <button type="button" class="btn btn-info btn-sm rounded-pill px-3 btn-bulk-action"
                    id="btn-bulk-present">
                    <i class="bi bi-check-all"></i> Bulk: Present
                </button>
            </div>
            <div class="col-4 col-md-auto">
                <button type="button" class="btn btn-danger btn-sm rounded-pill px-3 btn-bulk-action"
                    id="btn-bulk-absent">
                    <i class="bi bi-x-circle"></i> Bulk: Absent
                </button>
            </div>
            <div class="col-4 col-md-auto">
                <button type="button" class="btn btn-warning btn-sm rounded-pill px-3 btn-bulk-action"
                    id="btn-bulk-late">
                    <i class="bi bi-clock"></i> Bulk: Late
                </button>
            </div>
        </div>
    </div>
</div>