@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow rounded">
                    <div class="card-header text-white" style="background: linear-gradient(45deg, #8F12FE, #4A25AA);">
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">
                            <i class="bi bi-person-plus"></i> Register New User
                        </h2>
                    </div>

                    <div class="card-body">

                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="row mb-3">
                                <label for="username" class="col-lg-4 col-form-label text-lg-end">
                                    {{ __('Username') }} <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-6">
                                    <input id="username" type="text"
                                        class="form-control @error('username') is-invalid @enderror" name="username"
                                        value="{{ old('username') }}" required autocomplete="username" autofocus>
                                    @error('username')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>
                                                <i class="fas fa-user-times text-danger"></i>
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password" class="col-lg-4 col-form-label text-lg-end">
                                    {{ __('Password') }} <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-6">
                                    <div class="input-group">
                                        <input id="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror" name="password"
                                            required autocomplete="new-password">
                                        <span class="input-group-text toggle-password" style="cursor: pointer;">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                    @error('password')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>
                                                <i class="fas fa-key text-warning"></i>
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                    <small class="form-text text-muted">Minimum 8 characters</small>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="password_confirmation" class="col-lg-4 col-form-label text-lg-end">
                                    {{ __('Confirm Password') }} <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-6">
                                    <div class="input-group">
                                        <input id="password_confirmation" type="password" class="form-control"
                                            name="password_confirmation" required autocomplete="new-password">
                                        <span class="input-group-text toggle-password-confirm" style="cursor: pointer;">
                                            <i class="fas fa-eye"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="role" class="col-lg-4 col-form-label text-lg-end">
                                    {{ __('Role') }} <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-6">
                                    <select id="role" name="role"
                                        class="form-select @error('role') is-invalid @enderror" required>
                                        <option value="">Select Role</option>
                                        <option value="admin_mascot" {{ old('role') == 'admin_mascot' ? 'selected' : '' }}>
                                            Admin Mascot
                                        </option>
                                        <option value="admin_costume"
                                            {{ old('role') == 'admin_costume' ? 'selected' : '' }}>
                                            Admin Costume
                                        </option>
                                        <option value="admin_animatronic"
                                            {{ old('role') == 'admin_animatronic' ? 'selected' : '' }}>
                                            Admin Animatronic
                                        </option>
                                        <option value="general" {{ old('role') == 'general' ? 'selected' : '' }}>
                                            General
                                        </option>
                                    </select>
                                    @error('role')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>
                                                <i class="fas fa-user-tag text-danger"></i>
                                                {{ $message }}
                                            </strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="department_id" class="col-lg-4 col-form-label text-lg-end">
                                    Department <span class="text-danger">*</span>
                                </label>
                                <div class="col-lg-6">
                                    <select id="department_id" name="department_id" class="form-select" required>
                                        <option value="">Select Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}"
                                                {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                                {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('department_id')
                                        <span class="invalid-feedback d-block" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-lg-8 offset-lg-4">
                                    <button type="submit" class="btn btn-gradient text-white border-0" id="register-btn">
                                        <span class="spinner-border spinner-border-sm d-none" role="status"
                                            aria-hidden="true"></span>
                                        <i class="bi bi-person-plus"></i> {{ __('Register') }}
                                    </button>

                                    <a class="btn btn-link btn-link-gradient ms-2" href="{{ route('login') }}">
                                        <i class="bi bi-arrow-left"></i> Back to Login
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .btn-gradient {
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            color: #fff !important;
            border: none;
            transition: opacity 0.2s;
        }

        .btn-gradient:hover,
        .btn-gradient:focus {
            opacity: 0.85;
            color: #fff !important;
        }

        .btn-link-gradient {
            background: linear-gradient(45deg, #8F12FE, #4A25AA);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: underline;
            border: none;
            padding: 0;
            border-radius: 0;
            transition: opacity 0.2s;
            display: inline;
            font-weight: 500;
        }

        .btn-link-gradient:hover,
        .btn-link-gradient:focus {
            opacity: 0.8;
            text-decoration: underline;
        }

        .toggle-password,
        .toggle-password-confirm {
            cursor: pointer;
            transition: color 0.2s;
        }

        .toggle-password:hover,
        .toggle-password-confirm:hover {
            color: #8F12FE;
        }

        .invalid-feedback i {
            margin-right: 6px;
            font-size: 1.1em;
            vertical-align: middle;
        }

        .invalid-feedback.d-block {
            display: block !important;
            animation: shake 0.3s;
        }

        @keyframes shake {
            0% {
                transform: translateX(0);
            }

            25% {
                transform: translateX(-5px);
            }

            50% {
                transform: translateX(5px);
            }

            75% {
                transform: translateX(-5px);
            }

            100% {
                transform: translateX(0);
            }
        }

        .form-select:focus {
            border-color: #8F12FE;
            box-shadow: 0 0 0 0.2rem rgba(143, 18, 254, 0.25);
        }

        .form-control:focus {
            border-color: #8F12FE;
            box-shadow: 0 0 0 0.2rem rgba(143, 18, 254, 0.25);
        }
    </style>
@endpush

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility for main password field
            const togglePasswordButton = document.querySelector('.toggle-password');
            const passwordInput = document.getElementById('password');

            if (togglePasswordButton && passwordInput) {
                togglePasswordButton.addEventListener('click', function() {
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
            }

            // Toggle password visibility for confirm password field
            const togglePasswordConfirmButton = document.querySelector('.toggle-password-confirm');
            const passwordConfirmInput = document.getElementById('password_confirmation');

            if (togglePasswordConfirmButton && passwordConfirmInput) {
                togglePasswordConfirmButton.addEventListener('click', function() {
                    const icon = this.querySelector('i');

                    if (passwordConfirmInput.type === 'password') {
                        passwordConfirmInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        passwordConfirmInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            }

            // Real-time password confirmation validation
            if (passwordInput && passwordConfirmInput) {
                passwordConfirmInput.addEventListener('input', function() {
                    if (this.value !== passwordInput.value) {
                        this.setCustomValidity('Passwords do not match');
                        this.classList.add('is-invalid');
                    } else {
                        this.setCustomValidity('');
                        this.classList.remove('is-invalid');
                    }
                });

                passwordInput.addEventListener('input', function() {
                    if (passwordConfirmInput.value && passwordConfirmInput.value !== this.value) {
                        passwordConfirmInput.setCustomValidity('Passwords do not match');
                        passwordConfirmInput.classList.add('is-invalid');
                    } else if (passwordConfirmInput.value) {
                        passwordConfirmInput.setCustomValidity('');
                        passwordConfirmInput.classList.remove('is-invalid');
                    }
                });
            }

            const registerForm = document.querySelector('form[action="{{ route('register') }}"]');
            const registerBtn = document.getElementById('register-btn');
            if (registerForm && registerBtn) {
                registerForm.addEventListener('submit', function() {
                    registerBtn.disabled = true;
                    registerBtn.querySelector('.spinner-border').classList.remove('d-none');
                });
            }
        });
    </script>
@endpush
