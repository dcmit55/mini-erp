@extends('layouts.app')

@section('content')
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-header bg-transparent border-0 py-3">
                <div class="d-flex align-items-center">
                    <i class="bi bi-pencil gradient-icon me-2" style="font-size: 1.5rem;"></i>
                    <h2 class="mb-0" style="font-size:1.3rem;">Edit Feature Announcement</h2>
                </div>
            </div>

            <div class="card-body">
                <form method="POST" action="{{ route('feature-announcements.update', $announcement->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title"
                                    class="form-control @error('title') is-invalid @enderror"
                                    value="{{ old('title', $announcement->title) }}" required>
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                                <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="5" required>{{ old('description', $announcement->description) }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Version</label>
                                <input type="text" name="version"
                                    class="form-control @error('version') is-invalid @enderror"
                                    value="{{ old('version', $announcement->version) }}">
                                @error('version')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Priority <span class="text-danger">*</span></label>
                                <select name="priority" class="form-select @error('priority') is-invalid @enderror"
                                    required>
                                    <option value="info"
                                        {{ old('priority', $announcement->priority) === 'info' ? 'selected' : '' }}>Info
                                    </option>
                                    <option value="important"
                                        {{ old('priority', $announcement->priority) === 'important' ? 'selected' : '' }}>
                                        Important</option>
                                    <option value="critical"
                                        {{ old('priority', $announcement->priority) === 'critical' ? 'selected' : '' }}>
                                        Critical</option>
                                </select>
                                @error('priority')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                        id="is_active" {{ old('is_active', $announcement->is_active) ? 'checked' : '' }}>
                                    <label class="form-check-label fw-bold" for="is_active">
                                        Active
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="bi bi-people me-2"></i>Target Audience</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Roles</label>
                                <select name="target_roles[]"
                                    class="form-select select2 @error('target_roles') is-invalid @enderror" multiple
                                    data-placeholder="Select roles...">
                                    @foreach ($roles as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ in_array($value, old('target_roles', $announcement->target_roles ?? [])) ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Leave empty to target all users</small>
                                @error('target_roles')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Target Specific Users (Optional)</label>
                                <select name="target_user_ids[]"
                                    class="form-select select2 @error('target_user_ids') is-invalid @enderror" multiple
                                    data-placeholder="Select users...">
                                    @foreach ($users as $user)
                                        <option value="{{ $user->id }}"
                                            {{ in_array($user->id, old('target_user_ids', $announcement->target_user_ids ?? [])) ? 'selected' : '' }}>
                                            {{ $user->username }} ({{ ucwords(str_replace('_', ' ', $user->role)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Additional specific users to notify</small>
                                @error('target_user_ids')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h5 class="mb-3"><i class="bi bi-calendar-event me-2"></i>Display Schedule (Optional)</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Show From</label>
                                <input type="datetime-local" name="show_from"
                                    class="form-control @error('show_from') is-invalid @enderror"
                                    value="{{ old('show_from', $announcement->show_from ? $announcement->show_from->format('Y-m-d\TH:i') : '') }}">
                                @error('show_from')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Show Until</label>
                                <input type="datetime-local" name="show_until"
                                    class="form-control @error('show_until') is-invalid @enderror"
                                    value="{{ old('show_until', $announcement->show_until ? $announcement->show_until->format('Y-m-d\TH:i') : '') }}">
                                @error('show_until')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Note:</strong> Read status ({{ $announcement->reads_count }} reads) will be preserved.
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>Update Announcement
                        </button>
                        <a href="{{ route('feature-announcements.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle me-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true
            });
        });
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
