@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Edit User</h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('users.update', $user->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row mb-2">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="username"
                                value="{{ old('username', $user->username) }}" required>
                            @error('username')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label mb-0">New Password <span
                                    class="text-danger">*</span></label>
                            <small class="text-muted mb-2">(Leave blank to keep current password)</small>
                            <div class="input-group">
                                <input type="password" id="password" name="password" class="form-control">
                                <button type="button" class="btn btn-secondary toggle-password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="super_admin"
                                    {{ old('role', $user->role) == 'super_admin' ? 'selected' : '' }}>
                                    Super Admin</option>
                                <option value="admin_logistic"
                                    {{ old('role', $user->role) == 'admin_logistic' ? 'selected' : '' }}>Admin Logistic
                                </option>
                                <option value="admin_mascot"
                                    {{ old('role', $user->role) == 'admin_mascot' ? 'selected' : '' }}>
                                    Admin Mascot</option>
                                <option value="admin_costume"
                                    {{ old('role', $user->role) == 'admin_costume' ? 'selected' : '' }}>Admin Costume
                                </option>
                                <option value="admin_finance"
                                    {{ old('role', $user->role) == 'admin_finance' ? 'selected' : '' }}>Admin Finance
                                </option>
                                <option value="admin_animatronic"
                                    {{ old('role', $user->role) == 'admin_animatronic' ? 'selected' : '' }}>
                                    Admin Animatronic</option>
                                <option value="admin_procurement"
                                    {{ old('role', $user->role) == 'admin_procurement' ? 'selected' : '' }}>
                                    Admin Procurement</option>
                                <option value="admin" {{ old('role', $user->role) == 'admin' ? 'selected' : '' }}>Admin
                                </option>
                                <option value="general" {{ old('role', $user->role) == 'general' ? 'selected' : '' }}>
                                    General</option>
                            </select>
                            @error('role')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label>Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-control" required>
                                <option value="">Select Department</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="user-update-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Update
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password (existing code)
            const togglePasswordButtons = document.querySelectorAll('.toggle-password');
            togglePasswordButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const passwordInput = this.previousElementSibling;
                    const icon = this.querySelector('i');
                    if (passwordInput.type === 'password') {
                        passwordInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });

            // Prevent multiple submit & show spinner
            const form = document.querySelector('form[action="{{ route('users.update', $user->id) }}"]');
            const submitBtn = document.getElementById('user-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Updating...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Update';
        });
    </script>
@endpush
