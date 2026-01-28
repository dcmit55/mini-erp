<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <h5 class="card-title mb-0 fw-bold">Department Overview</h5>
    </div>
    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
        <div class="row g-3">
            @foreach($departmentStats as $dept)
                <div class="col-md-6 col-lg-4">
                    <div class="dept-card bg-light rounded-3 p-3 text-center"
                         data-department-id="{{ $dept->id }}"
                         data-department-name="{{ $dept->name }}">
                        <div class="dept-icon mb-2">
                            <i class="fas fa-building text-brand-icon fs-3"></i>
                        </div>
                        <h6 class="fw-bold mb-1">{{ ucfirst($dept->name) }}</h6>
                        <div class="small text-muted">
                            {{ $dept->projects_count }} Projects • {{ $dept->users_count }} Users
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>