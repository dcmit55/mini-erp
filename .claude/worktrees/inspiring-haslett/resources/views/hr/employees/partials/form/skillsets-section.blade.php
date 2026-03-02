{{-- resources/views/hr/employees/partials/form/skillsets-section.blade.php --}}
<div class="form-section mb-4">
    <div class="section-header">
        <h5 class="section-title">
            <i class="bi bi-stars me-2"></i>Skillsets & Competencies
        </h5>
        <p class="section-subtitle">Employee skills and proficiency levels</p>
    </div>
    <div class="section-body">
        <div class="row">
            <div class="col-12 mb-3">
                <label class="form-label">Select Skillsets</label>
                <select id="skillsets-select" name="skillsets[]" class="form-select select2-skillsets" multiple>
                    <option value="">Select skillsets...</option>
                    @foreach ($skillsets as $skillset)
                        <option value="{{ $skillset->id }}"
                            data-category="{{ $skillset->category }}"
                            data-proficiency="{{ $skillset->proficiency_required }}"
                            {{ isset($employee) && $employee->skillsets->contains($skillset->id) ? 'selected' : '' }}>
                            {{ $skillset->name }}
                        </option>
                    @endforeach
                </select>
                <small class="text-muted mt-1 d-block">
                    <i class="bi bi-info-circle"></i> Select multiple skills. Can't find a skill?
                    <a href="#" id="btn-add-skillset" class="text-primary">
                        <i class="bi bi-plus-circle"></i> Add New Skillset
                    </a>
                </small>
            </div>
        </div>

        <!-- Dynamic Skillset Details -->
        <div id="skillset-details-container" class="mt-3">
            @if(isset($employee) && $employee->skillsets->count() > 0)
                <div class="row g-3">
                    @foreach($employee->skillsets as $index => $skillset)
                        <div class="col-md-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="mb-0">{{ $skillset->name }}</h6>
                                        @if($skillset->category)
                                            <span class="badge bg-primary">{{ $skillset->category }}</span>
                                        @endif
                                    </div>
                                    
                                    <div class="mb-2">
                                        <label class="form-label small mb-1">Proficiency Level</label>
                                        <select name="skillset_proficiency[{{ $index }}]" class="form-select form-select-sm">
                                            <option value="basic" {{ $skillset->pivot->proficiency_level == 'basic' ? 'selected' : '' }}>Basic</option>
                                            <option value="intermediate" {{ $skillset->pivot->proficiency_level == 'intermediate' ? 'selected' : '' }}>Intermediate</option>
                                            <option value="advanced" {{ $skillset->pivot->proficiency_level == 'advanced' ? 'selected' : '' }}>Advanced</option>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="form-label small mb-1">Date Acquired</label>
                                        <input type="date" name="skillset_acquired_date[{{ $index }}]"
                                               class="form-control form-control-sm"
                                               value="{{ $skillset->pivot->acquired_date ?? date('Y-m-d') }}"
                                               max="{{ date('Y-m-d') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No skillsets selected. Select skills above to add details.
                </div>
            @endif
        </div>
    </div>
</div>

@push('form-scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for skillsets
        if ($('.select2-skillsets').length) {
            $('.select2-skillsets').select2({
                theme: 'bootstrap-5',
                placeholder: 'Select skillsets...',
                allowClear: true,
                width: '100%'
            });
        }
        
        // Update skillset details when selection changes
        $('#skillsets-select').on('change', function() {
            updateSkillsetDetails();
        });
        
        function updateSkillsetDetails() {
            const selectedSkillsets = $('#skillsets-select').select2('data');
            const container = $('#skillset-details-container');
            
            if (selectedSkillsets.length === 0) {
                container.html('<div class="alert alert-info"><i class="bi bi-info-circle"></i> No skillsets selected</div>');
                return;
            }
            
            let html = '<div class="row g-3">';
            
            selectedSkillsets.forEach((skillset, index) => {
                const proficiency = $(skillset.element).data('proficiency');
                const category = $(skillset.element).data('category');
                
                html += `
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">${skillset.text}</h6>
                                    ${category ? `<span class="badge bg-primary">${category}</span>` : ''}
                                </div>
                                
                                <div class="mb-2">
                                    <label class="form-label small mb-1">Proficiency Level</label>
                                    <select name="skillset_proficiency[${index}]" class="form-select form-select-sm">
                                        <option value="basic" ${proficiency === 'basic' ? 'selected' : ''}>Basic</option>
                                        <option value="intermediate" ${proficiency === 'intermediate' ? 'selected' : ''}>Intermediate</option>
                                        <option value="advanced" ${proficiency === 'advanced' ? 'selected' : ''}>Advanced</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="form-label small mb-1">Date Acquired</label>
                                    <input type="date" name="skillset_acquired_date[${index}]"
                                           class="form-control form-control-sm"
                                           value="{{ date('Y-m-d') }}"
                                           max="{{ date('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            container.html(html);
        }
        
        // Initialize skillset details on page load
        updateSkillsetDetails();
    });
</script>
@endpush