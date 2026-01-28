<div class="modal fade" id="skillGapModal" tabindex="-1" aria-labelledby="skillGapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header {{ $skillGapAnalysis['has_critical_impact'] ? 'bg-danger text-white' : 'bg-warning' }}">
                <h5 class="modal-title" id="skillGapModalLabel">
                    <i class="bi {{ $skillGapAnalysis['has_critical_impact'] ? 'bi-exclamation-triangle-fill' : 'bi-exclamation-circle-fill' }}"></i>
                    @if ($skillGapAnalysis['has_critical_impact'])
                        Critical Skill Gap Analysis
                    @else
                        Skill Gap Analysis
                    @endif
                </h5>
                <button type="button" class="btn-close {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-close-white' : '' }}"
                    data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Skill Gap Content (from existing view) -->
                <div class="row g-3">
                    <!-- Summary Card -->
                    <div class="col-md-12">
                        <div class="card {{ $skillGapAnalysis['has_critical_impact'] ? 'border-danger' : 'border-warning' }}">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 text-center">
                                        <h1 class="display-4 {{ $skillGapAnalysis['has_critical_impact'] ? 'text-danger' : 'text-warning' }}">
                                            {{ $skillGapAnalysis['total_affected_employees'] ?? 0 }}
                                        </h1>
                                        <p class="text-muted">Affected Employees</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h1 class="display-4 text-info">
                                            {{ $skillGapAnalysis['missing_skills_count'] ?? count($skillGapAnalysis['missing_skills'] ?? []) }}
                                        </h1>
                                        <p class="text-muted">Missing Skills</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h1 class="display-4 text-danger">
                                            {{ $skillGapAnalysis['critical_skills_count'] ?? count($skillGapAnalysis['critical_skills'] ?? []) }}
                                        </h1>
                                        <p class="text-muted">Critical Skills</p>
                                    </div>
                                    <div class="col-md-3 text-center">
                                        <h1 class="display-4 text-success">
                                            {{ $skillGapAnalysis['available_skills_count'] ?? 0 }}
                                        </h1>
                                        <p class="text-muted">Available Skills</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Missing Skills Section -->
                    @if(($skillGapAnalysis['missing_skills_count'] ?? count($skillGapAnalysis['missing_skills'] ?? [])) > 0)
                        <div class="col-md-12">
                            <div class="card border-danger">
                                <div class="card-header bg-danger text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-exclamation-triangle"></i>
                                        Missing Skills ({{ $skillGapAnalysis['missing_skills_count'] ?? count($skillGapAnalysis['missing_skills'] ?? []) }})
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        @php
                                            $missingSkills = $skillGapAnalysis['missing_skills'] ?? [];
                                            $criticalSkills = $skillGapAnalysis['critical_skills'] ?? [];
                                        @endphp
                                        
                                        @foreach($missingSkills as $skillName => $skillData)
                                            @php
                                                // Cek apakah skill critical
                                                $isCritical = false;
                                                if (isset($criticalSkills[$skillName])) {
                                                    $isCritical = true;
                                                } elseif (is_array($skillData) && isset($skillData['count']) && $skillData['count'] >= 2) {
                                                    $isCritical = true;
                                                } elseif (is_array($criticalSkills) && in_array($skillName, $criticalSkills)) {
                                                    $isCritical = true;
                                                }
                                            @endphp
                                            
                                            <div class="col-md-4">
                                                <div class="card border-{{ $isCritical ? 'danger' : 'warning' }}">
                                                    <div class="card-header bg-{{ $isCritical ? 'danger' : 'warning' }} text-white py-2">
                                                        <h6 class="mb-0">{{ $skillName }}</h6>
                                                    </div>
                                                    <div class="card-body p-2">
                                                        @if(is_array($skillData) && isset($skillData['employees']))
                                                            <small class="text-muted">Affected Employees ({{ $skillData['count'] ?? count($skillData['employees']) }}):</small>
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach($skillData['employees'] as $employee)
                                                                    <li class="small">
                                                                        <i class="bi bi-person"></i> 
                                                                        {{ $employee['name'] ?? $employee }} 
                                                                        <span class="badge bg-{{ ($employee['status'] ?? 'absent') === 'absent' ? 'danger' : 'warning' }}">
                                                                            {{ ucfirst($employee['status'] ?? 'absent') }}
                                                                        </span>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <small class="text-muted">Affected Employees:</small>
                                                            <ul class="list-unstyled mb-0">
                                                                @foreach((array)$skillData as $employee)
                                                                    <li class="small">
                                                                        <i class="bi bi-person"></i> {{ $employee }}
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Available Employees with Same Skills -->
                    @if(isset($skillGapAnalysis['available_employees']) && count($skillGapAnalysis['available_employees']) > 0)
                        <div class="col-md-12">
                            <div class="card border-success">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0">
                                        <i class="bi bi-check-circle"></i>
                                        Available Employees with Similar Skills
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Position</th>
                                                    <th>Department</th>
                                                    <th>Matching Skills</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($skillGapAnalysis['available_employees'] as $employee)
                                                    <tr>
                                                        <td>{{ $employee['name'] ?? 'Unknown' }}</td>
                                                        <td>{{ $employee['position'] ?? 'N/A' }}</td>
                                                        <td>{{ $employee['department'] ?? 'N/A' }}</td>
                                                        <td>
                                                            @foreach(($employee['matching_skills'] ?? []) as $skill)
                                                                <span class="badge bg-info">{{ $skill }}</span>
                                                            @endforeach
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-success">Present</span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bi bi-x-circle"></i> Close
                </button>
                <button type="button" class="btn {{ $skillGapAnalysis['has_critical_impact'] ? 'btn-danger' : 'btn-warning' }}"
                    onclick="window.print();">
                    <i class="bi bi-printer"></i> Print Analysis
                </button>
            </div>
        </div>
    </div>
</div>