{{--
    Project Select Options Component dengan Data Governance

    CLEAN APPROACH (Updated Jan 2026):
    - Controller SUDAH filter hanya project Lark via scope fromLark()
    - Component ini hanya render options biasa
    - Backend validation (ValidProjectSource) sebagai safety net

    Penggunaan:
    <select name="project_id" class="form-select select2">
        <option value="">Select Project</option>
        @include('components.project-options', [
            'projects' => $projects,  // Already filtered via fromLark() scope
            'selected' => old('project_id', $record->project_id ?? null)
        ])
    </select>

    Parameter:
    - $projects: Collection dari Project model (SUDAH filtered Lark-only di Controller)
    - $selected: ID project yang dipilih (optional)
--}}

@foreach ($projects as $project)
    <option value="{{ $project->id }}" data-department="{{ $project->departments->pluck('name')->implode(', ') }}"
        {{ isset($selected) && $selected == $project->id ? 'selected' : '' }}>
        {{ $project->name }}
    </option>
@endforeach
