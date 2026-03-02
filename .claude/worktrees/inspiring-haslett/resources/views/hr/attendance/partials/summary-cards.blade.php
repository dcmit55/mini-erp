<div class="row mb-4 g-3">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Total Employees</h6>
                <h3 id="summary-total" class="mb-0 fw-bold text-primary">{{ $summary['total'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Present</h6>
                <h3 id="summary-present" class="mb-0 fw-bold text-success">{{ $summary['present'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Absent</h6>
                <h3 id="summary-absent" class="mb-0 fw-bold text-danger">{{ $summary['absent'] }}</h3>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm rounded-3 h-100 summary-card">
            <div class="card-body text-center">
                <h6 class="text-muted mb-2">Late</h6>
                <h3 id="summary-late" class="mb-0 fw-bold text-warning">{{ $summary['late'] }}</h3>
            </div>
        </div>
    </div>
</div>