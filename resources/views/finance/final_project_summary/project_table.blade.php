@forelse($projects as $project)
    <tr>
        <td class="fw-semibold">{{ $project->name }}</td>
        <td>{{ ucfirst($project->department->name) }}</td>
        <td>
            <a href="{{ route('final_project_summary.show', $project) }}" class="btn btn-success btn-sm">
                <i class="bi bi-eye"></i> View Final Summary
            </a>
        </td>
    </tr>
@empty
    <tr class="no-data-row">
        <td colspan="3" class="text-center text-muted py-4">
            <i class="bi bi-search"></i> No projects found.
        </td>
    </tr>
@endforelse
