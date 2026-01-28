<div class="row mb-4">
    <div class="col-12">
        <div class="alert alert-skill-gap alert-dismissible fade show d-flex align-items-center {{ $skillGapAnalysis['has_critical_impact'] ? 'alert-danger' : 'alert-warning' }}"
            role="alert">
            <div class="me-3">
                <i class="bi {{ $skillGapAnalysis['has_critical_impact'] ? 'bi-exclamation-triangle-fill' : 'bi-exclamation-circle-fill' }}"
                    style="font-size: 2rem;"></i>
            </div>
            <div class="flex-grow-1">
                @if ($skillGapAnalysis['has_critical_impact'])
                    <h5 class="alert-heading mb-1">Critical Skill Gap Detected!</h5>
                @else
                    <h5 class="alert-heading mb-1">Skill Gap Alert</h5>
                @endif
                <p class="mb-2">
                    <strong>{{ $skillGapAnalysis['total_affected_employees'] }} employee(s)</strong>
                    are absent or late today, affecting
                    <strong>{{ count($skillGapAnalysis['missing_skills']) }} skillset(s)</strong>
                    @if ($skillGapAnalysis['has_critical_impact'])
                        (including <strong class="text-danger">{{ count($skillGapAnalysis['critical_skills']) }}
                            critical skills</strong>)
                    @endif
                </p>
                <button type="button"
                    class="btn btn-sm {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-danger' : 'btn-warning' }}"
                    data-bs-toggle="modal" data-bs-target="#skillGapModal">
                    <i class="bi bi-graph-up"></i> View Detailed Analysis
                </button>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
</div>