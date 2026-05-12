@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between">
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="bi bi-megaphone gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0" style="font-size:1.3rem;">Feature Announcements</h2>
                    </div>
                    @can('feature.announcement.manage')
                    <div>
                        <a href="{{ route('feature-announcements.create') }}" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i> Create Announcement
                        </a>
                    </div>
                    @endcan
                </div>
            </div>

            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($announcements->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Title</th>
                                    <th>Version</th>
                                    <th>Priority</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Reads</th>
                                    <th>Created</th>
                                    <th style="width: 180px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($announcements as $announcement)
                                    <tr>
                                        <td>
                                            <strong>{{ $announcement->title }}</strong>
                                            <br>
                                            <small
                                                class="text-muted">{{ Str::limit($announcement->description, 60) }}</small>
                                        </td>
                                        <td>
                                            @if ($announcement->version)
                                                <span class="badge bg-info">v{{ $announcement->version }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($announcement->priority === 'critical')
                                                <span class="badge bg-danger">Critical</span>
                                            @elseif($announcement->priority === 'important')
                                                <span class="badge bg-warning">Important</span>
                                            @else
                                                <span class="badge bg-primary">Info</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($announcement->target_roles)
                                                <small>{{ count($announcement->target_roles) }} roles</small>
                                            @elseif($announcement->target_user_ids)
                                                <small>{{ count($announcement->target_user_ids) }} users</small>
                                            @else
                                                <small class="text-muted">All users</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($announcement->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-info">{{ $announcement->reads_count }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $announcement->created_at->format('d M Y H:i') }}
                                            </small>
                                        </td>
                                        <td>
                                            @can('feature.announcement.manage')
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-success"
                                                    onclick="reBroadcast({{ $announcement->id }})" title="Re-broadcast">
                                                    <i class="bi bi-broadcast"></i>
                                                </button>
                                                <a href="{{ route('feature-announcements.edit', $announcement->id) }}"
                                                    class="btn btn-outline-primary" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <form
                                                    action="{{ route('feature-announcements.destroy', $announcement->id) }}"
                                                    method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-outline-danger" title="Delete">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $announcements->links() }}
                    </div>
                @else
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-3">No feature announcements yet.</p>
                        @can('feature.announcement.manage')
                        <a href="{{ route('feature-announcements.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Create First Announcement
                        </a>
                        @endcan
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function reBroadcast(id) {
            if (confirm('Re-broadcast this announcement to all targeted users?')) {
                $.post(`/feature-announcements/${id}/re-broadcast`, function(response) {
                    if (response.success) {
                        Swal.fire('Success', response.message, 'success');
                    }
                }).fail(function() {
                    Swal.fire('Error', 'Failed to re-broadcast announcement', 'error');
                });
            }
        }
    </script>
@endpush

@push('styles')
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
@endpush
