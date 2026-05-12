@extends('layouts.app')

@section('title', 'Violation Categories')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 fw-bold">Violation Categories</h4>
                    <small class="text-muted">Master data categories for Warning Letters</small>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addCatModal">
                    <i class="bi bi-plus-circle me-1"></i> Add Category
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            @endif

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Code</th>
                                <th>Name</th>
                                <th>Severity</th>
                                <th>Bulk Issue</th>
                                <th>Status</th>
                                <th class="text-end px-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $cat)
                            <tr>
                                <td class="px-3"><span class="badge bg-light text-dark border font-monospace">{{ $cat->code }}</span></td>
                                <td>{{ $cat->name }}</td>
                                <td>
                                    @php $sev = ['low'=>'success','medium'=>'warning','high'=>'orange','critical'=>'danger']; @endphp
                                    <span class="badge bg-{{ $sev[$cat->severity] ?? 'secondary' }} {{ $cat->severity === 'medium' ? 'text-dark' : '' }}">
                                        {{ ucfirst($cat->severity) }}
                                    </span>
                                </td>
                                <td>
                                    @if($cat->can_bulk_issue)
                                        <i class="bi bi-check-circle-fill text-success"></i>
                                    @else
                                        <i class="bi bi-x-circle text-muted"></i>
                                    @endif
                                </td>
                                <td>
                                    @if($cat->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end px-3">
                                    <button class="btn btn-sm btn-outline-secondary"
                                        data-bs-toggle="modal" data-bs-target="#editCatModal"
                                        data-id="{{ $cat->id }}"
                                        data-name="{{ $cat->name }}"
                                        data-severity="{{ $cat->severity }}"
                                        data-bulk="{{ $cat->can_bulk_issue ? 1 : 0 }}"
                                        data-active="{{ $cat->is_active ? 1 : 0 }}">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Add Modal --}}
<div class="modal fade" id="addCatModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('violation-categories.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Code (unique, uppercase)</label>
                        <input type="text" name="code" class="form-control text-uppercase" required maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select name="severity" class="form-select" required>
                            @foreach(['low','medium','high','critical'] as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="can_bulk_issue" value="1" id="bulk_add">
                        <label class="form-check-label" for="bulk_add">Can be used for Bulk Issue</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div class="modal fade" id="editCatModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="" id="editCatForm">
            @csrf @method('PUT')
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Edit Category</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="editName" class="form-control" required maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Severity</label>
                        <select name="severity" id="editSeverity" class="form-select" required>
                            @foreach(['low','medium','high','critical'] as $s)
                                <option value="{{ $s }}">{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="can_bulk_issue" value="1" id="editBulk">
                        <label class="form-check-label" for="editBulk">Can be used for Bulk Issue</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="editActive">
                        <label class="form-check-label" for="editActive">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('editCatModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    const id  = btn.getAttribute('data-id');
    document.getElementById('editCatForm').action = '/violation-categories/' + id;
    document.getElementById('editName').value     = btn.getAttribute('data-name');
    document.getElementById('editSeverity').value = btn.getAttribute('data-severity');
    document.getElementById('editBulk').checked   = btn.getAttribute('data-bulk') === '1';
    document.getElementById('editActive').checked = btn.getAttribute('data-active') === '1';
});
</script>
@endsection
