@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow rounded">
                    <div class="card-header text-white" style="background: linear-gradient(45deg, #8F12FE, #4A25AA);">
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Login</h2>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="row mb-3">
                                <label for="username"
                                    class="col-lg-4 col-form-label text-lg-end">{{ __('Username') }}</label>
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
                                <label for="password"
                                    class="col-lg-4 col-form-label text-lg-end">{{ __('Password') }}</label>
                                <div class="col-lg-6">
                                    <div class="input-group">
                                        <input id="password" type="password"
                                            class="form-control @error('password') is-invalid @enderror" name="password"
                                            required autocomplete="current-password">
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
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-6 offset-lg-4">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                            {{ old('remember') ? 'checked' : '' }}>

                                        <label class="form-check-label" for="remember">
                                            {{ __('Remember Me') }}
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-0">
                                <div class="col-lg-8 offset-lg-4">
                                    <button type="submit" class="btn btn-gradient text-white border-0" id="login-btn">
                                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status"
                                            aria-hidden="true"></span>
                                        {{ __('Login') }}
                                    </button>

                                    <a class="btn btn-link btn-link-gradient ms-2"
                                        href="https://wa.me/6287721988393?text=duhh%20tolong%2C%20lupa%20password%20nih"
                                        target="_blank">
                                        Forgot your password?
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
            const togglePasswordButtons = document.querySelectorAll('.toggle-password');
            // Password toggle
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

            // Prevent multiple submit
            const form = document.querySelector('form');
            const loginBtn = document.getElementById('login-btn');
            const spinner = loginBtn.querySelector('.spinner-border');
            form.addEventListener('submit', function() {
                loginBtn.disabled = true;
                spinner.classList.remove('d-none');
                loginBtn.childNodes[2].textContent = ' {{ __('Logging in...') }}';
            });
        });
    </script>
@endpush
