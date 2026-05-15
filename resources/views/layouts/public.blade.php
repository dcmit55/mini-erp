<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">

        <!-- Select2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <link rel="stylesheet" href="{{ asset('css/custom-app.css') }}">

        <style>
            body {
                font-family: 'Inter', sans-serif;
                min-height: 100vh;
                background: var(--bs-body-bg);
            }
            .public-navbar {
                background: var(--bs-body-bg);
                border-bottom: 1px solid var(--bs-border-color);
            }
            .public-footer {
                border-top: 1px solid var(--bs-border-color);
                color: var(--bs-secondary-color);
                font-size: .8rem;
            }
        </style>

        <!-- Apply saved theme immediately to prevent flash -->
        <script>
            (function() {
                var t = localStorage.getItem('preferred-theme') || 'dark';
                document.documentElement.setAttribute('data-bs-theme', t);
            })();
        </script>

        @yield('styles')
        @stack('styles')
    </head>

    <body>
        <!-- Minimal Public Navbar -->
        <nav class="navbar public-navbar shadow-sm sticky-top">
            <div class="container">
                <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="{{ url('/') }}"
                    style="background:linear-gradient(90deg,#e0364d,#2563eb);-webkit-background-clip:text;-webkit-text-fill-color:transparent;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                        fill="none" stroke="url(#logo-grad-pub)" stroke-width="2" stroke-linecap="round"
                        stroke-linejoin="round" style="-webkit-text-fill-color:initial;flex-shrink:0;">
                        <defs>
                            <linearGradient id="logo-grad-pub" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#7c3aed" />
                                <stop offset="100%" stop-color="#2563eb" />
                            </linearGradient>
                        </defs>
                        <rect x="2" y="7" width="20" height="14" rx="2" />
                        <path d="M16 7V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v2" />
                        <line x1="12" y1="12" x2="12" y2="16" />
                        <line x1="10" y1="14" x2="14" y2="14" />
                    </svg>
                    {{ config('app.name', 'DCM') }}
                </a>
                <div class="d-flex align-items-center gap-2">
                    <!-- Theme toggle -->
                    <button id="themeToggle" class="btn btn-sm btn-outline-secondary rounded-circle p-1 lh-1"
                        title="Toggle theme" style="width:30px;height:30px;">
                        <i class="bi bi-moon-stars-fill" id="themeIcon" style="font-size:.85rem;"></i>
                    </button>
                    @guest
                        <a href="{{ route('login') }}" class="btn btn-sm btn-outline-primary rounded-2">
                            <i class="bi bi-box-arrow-in-right me-1"></i>Login
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-sm btn-outline-secondary rounded-2">
                            <i class="bi bi-grid me-1"></i>Dashboard
                        </a>
                    @endguest
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="py-4">
            @yield('content')
        </main>

        <!-- Simple Footer -->
        <footer class="public-footer text-center py-3 mt-auto">
            <div class="container">
                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </div>
        </footer>

        <!-- Scripts -->
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <script>
            // Theme toggle
            const themeToggle = document.getElementById('themeToggle');
            const themeIcon   = document.getElementById('themeIcon');
            function applyTheme(t) {
                document.documentElement.setAttribute('data-bs-theme', t);
                themeIcon.className = t === 'dark' ? 'bi bi-moon-stars-fill' : 'bi bi-sun-fill';
                themeIcon.style.fontSize = '.85rem';
            }
            applyTheme(localStorage.getItem('preferred-theme') || 'dark');
            themeToggle.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-bs-theme');
                const next    = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem('preferred-theme', next);
                applyTheme(next);
            });
        </script>

        @yield('scripts')
        @stack('scripts')
    </body>

</html>
