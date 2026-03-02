@if($employees->isEmpty())
<div class="text-center py-5">
    <div class="mb-4">
        <i class="bi bi-people display-1 text-muted"></i>
    </div>
    <h4 class="text-muted mb-3">No Employees Found</h4>
    <p class="text-muted mb-4">
        {{ request()->hasAny(['department_id', 'position', 'status', 'search']) 
            ? 'Try adjusting your filters or search criteria.' 
            : 'No employees are assigned to this department.' }}
    </p>
    @if(request()->hasAny(['department_id', 'position', 'status', 'search']))
        <a href="{{ route('attendance.index') }}" class="btn btn-primary">
            <i class="bi bi-arrow-clockwise"></i> Reset Filters
        </a>
    @endif
</div>
@endif