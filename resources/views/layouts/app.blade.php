<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="dns-prefetch" href="//fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap"
            rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- DataTables CSS -->
        {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/css/bootstrap.min.css"> --}}
        <link rel="stylesheet" href="https://cdn.datatables.net/2.3.1/css/dataTables.bootstrap5.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.3.2/css/fixedHeader.dataTables.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/select/3.0.1/css/select.bootstrap5.css">
        <!-- DataTables Responsive CSS -->
        <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.4/css/responsive.bootstrap5.css">

        <!-- SweetAlert2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css">

        <!-- Select2 CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" />
        <link rel="stylesheet"
            href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

        <!-- Bootstrap Icons -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

        <!-- Fancybox CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />

        <!-- Flatpickr CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

        <link rel="stylesheet" href="{{ asset('css/custom-app.css') }}">
        @stack('styles')
    </head>

    <body>
        <div id="app">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm rounded-4 rounded-top-0">
                <div class="container-fluid">
                    <a class="navbar-brand" style="font-weight: bold;" href="{{ url('/') }}">
                        {{ config('app.name', 'DCM-app') }}
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarSupportedContent">
                        <!-- Left Side Of Navbar -->
                        <ul class="navbar-nav me-auto">
                            @if (auth()->check())
                                <!-- Dashboard -->
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->is('dashboard*') ? 'active' : '' }}"
                                        href="{{ route('dashboard') }}">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>

                                <!-- Logistics Dropdown -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_mascot',
                                        'admin_costume',
                                        'admin_logistic',
                                        'admin_finance',
                                        'admin_animatronic',
                                        'general',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('inventory*') || request()->is('material_requests*') || request()->is('goods_out*') || request()->is('goods_in*') || request()->is('material-usage*') ? 'active' : '' }}"
                                            href="#" id="logisticsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-boxes"></i> Logistics
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="logisticsDropdown">
                                            <li><a class="dropdown-item {{ request()->is('inventory*') ? 'active' : '' }}"
                                                    href="{{ route('inventory.index') }}">
                                                    <i class="fas fa-warehouse"></i> Inventory Listing
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                    href="{{ route('material_requests.index') }}">
                                                    <i class="fas fa-clipboard-list"></i> Material Request
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('goods_out*') ? 'active' : '' }}"
                                                    href="{{ route('goods_out.index') }}">
                                                    <i class="fas fa-shipping-fast"></i> Goods Out
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('goods_in*') ? 'active' : '' }}"
                                                    href="{{ route('goods_in.index') }}">
                                                    <i class="fas fa-dolly"></i> Goods In
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('material-usage*') ? 'active' : '' }}"
                                                    href="{{ route('material_usage.index') }}">
                                                    <i class="fas fa-chart-line"></i> Material Usage
                                                </a></li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Procurement Dropdown -->
                                @if (in_array(auth()->user()->role, ['super_admin', 'admin_procurement']))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('external_requests*') ? 'active' : '' }}"
                                            href="#" id="procurementDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-shopping-cart"></i> Procurement
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('external_requests*') ? 'active' : '' }}"
                                                    href="{{ route('external_requests.index') }}">
                                                    <i class="fas fa-external-link-alt"></i> External Request
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('pre-shippings*') ? 'active' : '' }}"
                                                    href="{{ route('pre-shippings.index') }}">
                                                    <i class="fas fa-truck"></i> Pre Shippings
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('shipping-management*') ? 'active' : '' }}"
                                                    href="{{ route('shipping-management.index') }}">
                                                    <i class="fas fa-truck"></i> Shipping Management
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('goods-receive*') ? 'active' : '' }}"
                                                    href="{{ route('goods-receive.index') }}">
                                                    <i class="fas fa-box-open"></i> Goods Receive
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Productions Dropdown -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_mascot',
                                        'admin_costume',
                                        'admin_logistic',
                                        'admin_finance',
                                        'admin_animatronic',
                                        'general',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('projects*') || request()->is('timings*') || request()->is('employees/*/timing*') ? 'active' : '' }}"
                                            href="#" id="productionsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-cogs"></i> Productions
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="productionsDropdown">
                                            <li><a class="dropdown-item {{ request()->is('projects*') ? 'active' : '' }}"
                                                    href="{{ route('projects.index') }}">
                                                    <i class="fas fa-project-diagram"></i> Project
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                    href="{{ route('material_requests.index') }}">
                                                    <i class="fas fa-clipboard-list"></i> Material Request
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('material-usage*') ? 'active' : '' }}"
                                                    href="{{ route('material_usage.index') }}">
                                                    <i class="fas fa-chart-line"></i> Material Usage
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('timings*') ? 'active' : '' }}"
                                                    href="{{ route('timings.index') }}">
                                                    <i class="fas fa-clock"></i> Timing
                                                </a></li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Finances Dropdown -->
                                @if (in_array(auth()->user()->role, ['super_admin', 'admin_finance']))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('currencies*') || request()->is('costing-report*') || request()->is('final_project_summary*') ? 'active' : '' }}"
                                            href="#" id="financesDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-calculator"></i> Finances
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="financesDropdown">
                                            <li><a class="dropdown-item {{ request()->is('currencies*') ? 'active' : '' }}"
                                                    href="{{ route('currencies.index') }}">
                                                    <i class="fas fa-coins"></i> Currency
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('costing-report*') ? 'active' : '' }}"
                                                    href="{{ route('costing.report') }}">
                                                    <i class="fas fa-file-invoice-dollar"></i> Costing Report
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('final_project_summary*') ? 'active' : '' }}"
                                                    href="{{ route('final_project_summary.index') }}">
                                                    <i class="fas fa-chart-pie"></i> Final Project Summary
                                                </a></li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Admin Dropdown -->
                                @if (auth()->user()->role === 'super_admin')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('employees*') || request()->is('users*') || request()->routeIs('trash.index') ? 'active' : '' }}"
                                            href="#" id="adminDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="fas fa-user-shield"></i> Admin
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                            <li><a class="dropdown-item {{ request()->is('employees*') ? 'active' : '' }}"
                                                    href="{{ route('employees.index') }}">
                                                    <i class="fas fa-users"></i> Employees
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->is('users*') ? 'active' : '' }}"
                                                    href="{{ route('users.index') }}">
                                                    <i class="fas fa-user"></i> Users
                                                </a></li>
                                            <li><a class="dropdown-item {{ request()->routeIs('trash.index') ? 'active' : '' }}"
                                                    href="{{ route('trash.index') }}">
                                                    <i class="fas fa-trash"></i> Trash
                                                </a></li>
                                        </ul>
                                    </li>
                                @endif
                            @endif
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ms-auto">
                            <!-- Authentication Links -->
                            @guest
                                @if (Route::has('login'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                    </li>
                                @endif

                                @if (Route::has('register'))
                                    <li class="nav-item">
                                        <a class="nav-link" href="{{ route('register') }}">{{ __('Register') }}</a>
                                    </li>
                                @endif
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link btn dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        {{ ucfirst(Auth::user()->username) }}
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-1"></i> {{ __('Logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                            class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endguest
                        </ul>
                    </div>
                </div>
            </nav>

            <main>
                @yield('content')
            </main>
        </div>

        </div>

        <!-- Toast Container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" id="toast-container"
            style="max-height: 50vh; overflow-y: auto;">
            <!-- Template toast kosong -->
            <div class="toast d-none" id="toast-template" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="toast-header">
                    <span class="rounded-circle bg-primary d-inline-block" style="width: 10px; height: 10px;"></span>
                    <strong class="me-auto ms-2">Material Request</strong>
                    <small class="toast-time">Just now</small>
                    <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
                <div class="toast-body">
                    <!-- Konten toast akan diisi melalui JavaScript -->
                </div>
            </div>
        </div>

        <div id="notification-sound-container">
            <audio id="notification-sound" src="{{ asset('sounds/notification.mp3') }}" preload="auto"></audio>
        </div>

        <!-- Scripts -->

        <script src="https://code.jquery.com/jquery-3.7.1.js"></script>

        <!-- Setup CSRF Token for AJAX -->
        <script>
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
        </script>

        <!-- Bootstrap JS Bundle with Popper -->
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <script src="{{ mix('js/app.js') }}"></script>

        <!-- Select2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

        <!-- DataTables JS -->
        {{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.3.3/js/bootstrap.bundle.min.js"></script> --}}
        <script src="https://cdn.datatables.net/2.3.1/js/dataTables.js"></script>
        <script src="https://cdn.datatables.net/2.3.1/js/dataTables.bootstrap5.js"></script>
        <script src="https://cdn.datatables.net/fixedheader/3.3.2/js/dataTables.fixedHeader.min.js"></script>
        <script src="https://cdn.datatables.net/select/3.0.1/js/dataTables.select.min.js"></script>
        <script src="https://cdn.datatables.net/select/3.0.1/js/select.bootstrap5.js"></script>
        <!-- DataTables Responsive JS -->
        <script src="https://cdn.datatables.net/responsive/3.0.4/js/dataTables.responsive.js"></script>
        <script src="https://cdn.datatables.net/responsive/3.0.4/js/responsive.bootstrap5.js"></script>

        <!-- SweetAlert2 JS -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>

        <!-- Fancybox JS -->
        <script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>

        <!-- Flatpickr JS -->
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            const authUserRole = "{{ auth()->check() ? auth()->user()->role : '' }}";
        </script>

        <script src="{{ asset('js/custom-app.js') }}"></script>
        @stack('scripts')

        <footer class="bg-light text-center text-lg-start mt-5">
            <div class="container-fluid">
                <div class="row">
                    <!-- About Section -->
                    <div class="bg-white col-lg-12 col-lg-12 mb-2 mb-lg-0">
                        <h5 class="text-uppercase mt-2">About:</h5>
                        <p class="mb-3">
                            This is an inventory management system designed to streamline your operations and improve
                            efficiency.
                        </p>
                    </div>
                </div>
            </div>

            <div class="text-center p-1 bg-dark text-secondary">
                © {{ date('Y') }} {{ config('app.name', 'DCM-app') }}. Created with <i
                    class="fas fa-heart text-danger"></i> by IT Team (Gen 1)
            </div>
        </footer>
    </body>

</html>
