<!-- filepath: c:\laragon\www\inventory-system-v2\resources\views\hr\attendance\skill-gap-modal.blade.php -->

<!-- Summary Info -->
<div class="alert {{ $skillGapAnalysis['has_critical_impact'] ? 'alert-danger' : 'alert-warning' }} mb-4">
    <h6 class="alert-heading">Summary</h6>
    <ul class="mb-0">
        <li><strong>{{ $skillGapAnalysis['total_affected_employees'] }} employees</strong> are absent or late today</li>
        <li><strong>{{ count($skillGapAnalysis['missing_skills']) }} skillsets</strong> are affected</li>
        @if ($skillGapAnalysis['has_critical_impact'])
            <li class="text-danger">
                <strong>{{ count($skillGapAnalysis['critical_skills']) }} critical skills</strong>
                (2+ employees with same skill unavailable)
            </li>
        @endif
    </ul>
</div>

<!-- Skill Cards -->
<div class="row g-3">
    @foreach ($skillGapAnalysis['missing_skills'] as $skill)
        <div class="col-md-6">
            <div class="card {{ $skill['count'] >= 2 ? 'border-danger' : 'border-warning' }} h-100">
                <div class="card-header {{ $skill['count'] >= 2 ? 'bg-danger text-white' : 'bg-warning' }}">
                    <div class="d-flex justify-content-between align-items-start">
                        <h6 class="mb-0">
                            <i class="bi bi-stars"></i> {{ $skill['name'] }}
                            @if ($skill['count'] >= 2)
                                <span class="badge bg-white text-danger ms-2">Critical</span>
                            @endif
                        </h6>
                        @if ($skill['category'])
                            <span class="badge {{ $skill['count'] >= 2 ? 'bg-white text-danger' : 'bg-dark' }}">
                                {{ $skill['category'] }}
                            </span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <p class="mb-3">
                        <i class="bi bi-people-fill"></i>
                        <strong>{{ $skill['count'] }} employee(s)</strong> with this skill are unavailable
                    </p>

                    <h6 class="text-muted mb-2">Affected Employees:</h6>
                    <div class="list-group list-group-flush">
                        @foreach ($skill['employees'] as $emp)
                            <div class="list-group-item px-0 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <strong>{{ $emp['name'] }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Proficiency:
                                            <span
                                                class="badge {{ $emp['proficiency'] === 'advanced' ? 'bg-success' : ($emp['proficiency'] === 'intermediate' ? 'bg-warning' : 'bg-light text-dark') }}">
                                                {{ ucfirst($emp['proficiency']) }}
                                            </span>
                                        </small>
                                    </div>
                                    <span class="badge {{ $emp['status'] === 'absent' ? 'bg-danger' : 'bg-warning' }}">
                                        {{ ucfirst($emp['status']) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Action Recommendations -->
@if ($skillGapAnalysis['has_critical_impact'])
    <div class="alert alert-danger mt-4 mb-0">
        <h6 class="alert-heading">
            <i class="bi bi-shield-exclamation"></i> Action Required
        </h6>
        <ul class="mb-0">
            <li>Consider reassigning tasks to available employees with similar skills</li>
            <li>Contact backup personnel who have the required critical skills</li>
            <li>Reschedule non-urgent tasks that depend on missing skills</li>
            <li>Document the impact for management review</li>
        </ul>
    </div>
@endif
