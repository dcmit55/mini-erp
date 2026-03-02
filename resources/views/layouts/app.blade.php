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
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

        <!-- DataTables CSS -->
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

        <!-- Page Specific Styles -->
        @yield('styles')
        @stack('styles')
    </head>

    <body>
        <div id="app">
            <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                        {{ config('app.name', 'DCM-app') }}
                    </a>
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                        aria-expanded="false" aria-label="Toggle navigation">
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
                                        <i></i>Dashboard
                                    </a>
                                </li>

                                @php
                                    if (!function_exists('isDropdownActive')) {
                                        function isDropdownActive($prefixes)
                                        {
                                            foreach ($prefixes as $prefix) {
                                                if (request()->is($prefix)) {
                                                    return true;
                                                }
                                            }
                                            return false;
                                        }
                                    }

                                    $logisticsPrefixes = [
                                        'inventory*',
                                        'material_requests*',
                                        'goods_out*',
                                        'goods_in*',
                                        'material_usage*',
                                        'goods-movement*',
                                    ];

                                    $procurementPrefixes = [
                                        'suppliers*',
                                        'purchase_requests*',
                                        'pre-shippings*',
                                        'shipping-management*',
                                        'goods-receive*',
                                        'project-purchases*',
                                    ];
                                @endphp

                                <!-- Projects Menu -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_mascot',
                                        'admin_costume',
                                        'admin_logistic',
                                        'admin_finance',
                                        'admin_procurement',
                                        'admin_animatronic',
                                        'admin_hr',
                                        'admin',
                                        'general',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('projects*') || request()->is('internal-projects*') || request()->is('job-order-type-gradings*') ? 'active' : '' }}"
                                            href="#" id="projectsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Projects
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="projectsDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('projects*') && !request()->is('internal-projects*') ? 'active' : '' }}"
                                                    href="{{ route('projects.index') }}">
                                                    <i class="fas fa-building me-2"></i>Client Projects
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('internal-projects*') ? 'active' : '' }}"
                                                    href="{{ route('internal-projects.index') }}">
                                                    <i class="fas fa-cogs me-2"></i>Internal Projects
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('job-order-type-gradings*') ? 'active' : '' }}"
                                                    href="{{ route('job-order-type-gradings.index') }}">
                                                    <i class="fas fa-layer-group me-2"></i>Job Type
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Logistics Dropdown -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_mascot',
                                        'admin_costume',
                                        'admin_logistic',
                                        'admin_finance',
                                        'admin_procurement',
                                        'admin_animatronic',
                                        'admin_hr',
                                        'admin',
                                        'general',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ isDropdownActive($logisticsPrefixes) ? 'active' : '' }}"
                                            href="#" id="logisticsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Logistics
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="logisticsDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('inventory*') ? 'active' : '' }}"
                                                    href="{{ route('inventory.index') }}">
                                                    <i class="fas fa-boxes me-2"></i>Inventory Listing
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                    href="{{ route('material_requests.index') }}">
                                                    <i class="fas fa-clipboard-list me-2"></i>Material Request
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('goods_out*') ? 'active' : '' }}"
                                                    href="{{ route('goods_out.index') }}">
                                                    <i class="fas fa-arrow-right me-2"></i>Goods Out
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('goods_in*') ? 'active' : '' }}"
                                                    href="{{ route('goods_in.index') }}">
                                                    <i class="fas fa-arrow-left me-2"></i>Goods In
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('material_usage*') ? 'active' : '' }}"
                                                    href="{{ route('material_usage.index') }}">
                                                    <i class="fas fa-balance-scale me-2"></i>Material Usage
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('goods-movement*') ? 'active' : '' }}"
                                                    href="{{ route('goods-movement.index') }}">
                                                    <i class="fas fa-exchange-alt me-2"></i>Goods Movement
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Procurement Dropdown -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_procurement',
                                        'admin_hr',
                                        'admin',
                                        'admin_logistic',
                                        'admin_finance',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ isDropdownActive($procurementPrefixes) ? 'active' : '' }}"
                                            href="#" id="procurementDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Procurement
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('project-purchases*') ? 'active' : '' }}"
                                                    href="{{ route('project-purchases.index') }}">
                                                    <i class="fas fa-file-invoice me-2"></i>Indo Purchase
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('suppliers*') ? 'active' : '' }}"
                                                    href="{{ route('suppliers.index') }}">
                                                    <i class="fas fa-truck me-2"></i>Suppliers
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('purchase_requests*') ? 'active' : '' }}"
                                                    href="{{ route('purchase_requests.index') }}">
                                                    <i class="fas fa-clipboard-check me-2"></i>Purchase Request
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('pre-shippings*') ? 'active' : '' }}"
                                                    href="{{ route('pre-shippings.index') }}">
                                                    <i class="fas fa-shipping-fast me-2"></i>Pre Shippings
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('shipping-management*') ? 'active' : '' }}"
                                                    href="{{ route('shipping-management.index') }}">
                                                    <i class="fas fa-ship me-2"></i>Shipping Management
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('goods-receive*') ? 'active' : '' }}"
                                                    href="{{ route('goods-receive.index') }}">
                                                    <i class="fas fa-box-open me-2"></i>Goods Receive
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
                                        'admin_procurement',
                                        'admin_animatronic',
                                        'admin_hr',
                                        'admin',
                                        'general',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('job-orders*') || request()->is('quick-timer*') || request()->is('employees/*/timing*') || request()->is('material-planning*') || request()->is('overtime-requests*') ? 'active' : '' }}"
                                            href="#" id="productionsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Productions
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="productionsDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('job-orders*') ? 'active' : '' }}"
                                                    href="{{ route('job-orders.index') }}">
                                                    <i class="fas fa-tasks me-2"></i>Job Order
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                    href="{{ route('material_requests.index') }}">
                                                    <i class="fas fa-clipboard-list me-2"></i>Material Request
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('material-usage*') ? 'active' : '' }}"
                                                    href="{{ route('material_usage.index') }}">
                                                    <i class="fas fa-balance-scale me-2"></i>Material Usage
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('material-planning*') ? 'active' : '' }}"
                                                    href="{{ route('material_planning.index') }}">
                                                    <i class="fas fa-calendar-alt me-2"></i>Material Planning
                                                </a>
                                            </li>
                                            <!-- Tambahan Overtime Requests -->
                                            <li>
                                                <a class="dropdown-item {{ request()->routeIs('overtime-requests.*') ? 'active' : '' }}"
                                                    href="{{ route('overtime-requests.index') }}">
                                                    <i class="fas fa-hourglass-half me-2"></i>Overtime Requests
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Timing Menu (Dedicated) -->
                                @if (in_array(auth()->user()->role, [
                                        'super_admin',
                                        'admin_mascot',
                                        'admin_costume',
                                        'admin_animatronic',
                                        'admin_hr',
                                        'admin',
                                    ]))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('costume-timing*') || request()->is('animatronics-timing*') || request()->is('mascot-timing*') || request()->is('timing-monitor*') ? 'active' : '' }}"
                                            href="#" id="timingDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Timing
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="timingDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('costume-timing*') ? 'active' : '' }}"
                                                    href="{{ route('costume-timing.index') }}">
                                                    <i class="fas fa-cut me-2"></i>Costume Timing
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('animatronics-timing*') ? 'active' : '' }}"
                                                    href="{{ route('animatronics-timing.index') }}">
                                                    <i class="fas fa-robot me-2"></i>Animatronics Timing
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('mascot-timing*') ? 'active' : '' }}"
                                                    href="{{ route('mascot-timing.index') }}">
                                                    <i class="fas fa-masks-theater me-2"></i>Mascot Timing
                                                </a>
                                            </li>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('timing-monitor*') ? 'active' : '' }}"
                                                    href="{{ route('timing-monitor.index') }}">
                                                    <i class="fas fa-tv me-2"></i>Running Monitor
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- Finances Dropdown -->
                                @if (in_array(auth()->user()->role, ['super_admin', 'admin_finance', 'admin', 'admin_logistic', 'admin_procurement']))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('currencies*') ||
                                        request()->is('costing-report*') ||
                                        request()->is('final_project_summary*') ||
                                        request()->is('dcm-costings*') ||
                                        request()->is('purchase-approvals*') ||
                                        request()->is('purchase-edited*')
                                            ? 'active'
                                            : '' }}"
                                            href="#" id="financesDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Finances
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="financesDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('currencies*') ? 'active' : '' }}"
                                                    href="{{ route('currencies.index') }}">
                                                    <i class="fas fa-money-bill me-2"></i>Currency
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('costing-report*') ? 'active' : '' }}"
                                                    href="{{ route('costing.report') }}">
                                                    <i class="fas fa-chart-line me-2"></i>Costing Report
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('final_project_summary*') ? 'active' : '' }}"
                                                    href="{{ route('final_project_summary.index') }}">
                                                    <i class="fas fa-file-contract me-2"></i>Final Project Summary
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('dcm-costings*') ? 'active' : '' }}"
                                                    href="{{ route('dcm-costings.index') }}">
                                                    <i class="fas fa-file-invoice-dollar me-2"></i>DCM Costing
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('purchase-approvals*') ? 'active' : '' }}"
                                                    href="{{ route('purchase-approvals.index') }}">
                                                    <i class="fas fa-clipboard-check me-2"></i>Purchase Approvals
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('purchase-edited*') ? 'active' : '' }}"
                                                    href="{{ route('purchase-edited.index') }}">
                                                    <i class="fas fa-edit me-2"></i>Purchase Edited
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endif

                                <!-- HR Dropdown (DIPERBARUI) -->
                                @auth
                                    @if (in_array(auth()->user()->role, ['super_admin', 'admin_hr', 'admin']))
                                        @php
                                            $hrOvertimePendingCount = \App\Models\Hr\OvertimeRequest::where('status', 'submitted')
                                                ->where('hr_approval_status', 'pending')
                                                ->count();
                                        @endphp
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle {{ request()->is('employees*') || request()->routeIs('leave_requests.index') || request()->is('attendance*') || request()->routeIs('employee-work-policies.*') || request()->routeIs('timings.*') || request()->routeIs('attendance-logs.*') || request()->routeIs('overtime-requests.*') || request()->routeIs('overtime-pays.*') ? 'active' : '' }}"
                                                href="#" id="hrDropdown" role="button" data-bs-toggle="dropdown"
                                                aria-expanded="false">
                                                <i></i>HR
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="hrDropdown">
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('employees*') ? 'active' : '' }}"
                                                        href="{{ route('employees.index') }}">
                                                        <i class="fas fa-user-tie me-2"></i>Employees
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('leave_requests.index') ? 'active' : '' }}"
                                                        href="{{ route('leave_requests.index') }}">
                                                        <i class="fas fa-calendar-minus me-2"></i>Leave Requests
                                                    </a>
                                                </li>
                                                <!-- Work Policies -->
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('employee-work-policies.*') ? 'active' : '' }}"
                                                        href="{{ route('employee-work-policies.index') }}">
                                                        <i class="fas fa-clock me-2"></i>Work Policies
                                                    </a>
                                                </li>
                                                <!-- Attendance Logs -->
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('attendance-logs.*') ? 'active' : '' }}"
                                                        href="{{ route('attendance-logs.index') }}">
                                                        <i class="fas fa-clock me-2"></i>Attendance Logs
                                                    </a>
                                                </li>
                                                <!-- Timing Data -->
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('timings*') ? 'active' : '' }}"
                                                        href="{{ route('timings.index') }}">
                                                        <i class="fas fa-clock me-2"></i>Timing Data
                                                    </a>
                                                </li>
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <!-- Overtime Requests -->
                                                <li>
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between {{ request()->routeIs('overtime-requests.hr-approvals') ? 'active' : '' }}"
                                                        href="{{ route('overtime-requests.hr-approvals') }}">
                                                        <span><i class="fas fa-user-check me-2"></i>HR Overtime Approvals</span>
                                                        @if($hrOvertimePendingCount > 0)
                                                        <span class="badge bg-danger rounded-pill ms-2" style="font-size:0.65rem;">
                                                            {{ $hrOvertimePendingCount > 99 ? '99+' : $hrOvertimePendingCount }}
                                                        </span>
                                                        @endif
                                                    </a>
                                                </li>
                                                <!-- Overtime Pay -->
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('overtime-pays.*') ? 'active' : '' }}"
                                                        href="{{ route('overtime-pays.index') }}">
                                                        <i class="fas fa-calculator me-2"></i>Overtime Pay
                                                    </a>
                                                </li>
                                            </ul>
                                        </li>
                                    @endif
                                @endauth

                                {{-- Guest Access - Show Leave Request link in navigation for non-authenticated users --}}
                                @guest
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('leave_requests.index') ? 'active' : '' }}"
                                            href="{{ route('leave_requests.index') }}">
                                            <i class="fas fa-calendar-minus me-2"></i>Leave Request
                                        </a>
                                    </li>
                                @endguest

                                <!-- Admin Dropdown -->
                                @if (in_array(auth()->user()->role, ['super_admin', 'admin']))
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('users*') || request()->is('departments*') || request()->routeIs('trash.index') || request()->is('audit*') ? 'active' : '' }}"
                                            href="#" id="adminDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Admin
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                            <li>
                                                <a class="dropdown-item {{ request()->is('users*') ? 'active' : '' }}"
                                                    href="{{ route('users.index') }}">
                                                    <i class="fas fa-user-cog me-2"></i>Users
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('departments*') ? 'active' : '' }}"
                                                    href="{{ route('departments.index') }}">
                                                    <i class="fas fa-sitemap me-2"></i>Departments
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->routeIs('trash.index') ? 'active' : '' }}"
                                                    href="{{ route('trash.index') }}">
                                                    <i class="fas fa-trash me-2"></i>Trash
                                                </a>
                                            </li>
                                            <!-- Audit Log (only for super_admin) -->
                                            @if (Auth::user()->isSuperAdmin())
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('audit*') ? 'active' : '' }}"
                                                        href="{{ route('audit.index') }}">
                                                        <i class="fas fa-clipboard-list me-2"></i>Audit Log
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endif
                            @endif
                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ms-auto">
                            @guest
                            @else
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link btn dropdown-toggle" href="#"
                                        role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                        aria-expanded="false">
                                        <i class="fas fa-user me-2"></i>{{ ucfirst(Auth::user()->username) }}
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt me-2"></i>Logout
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

            <main class="py-4">
                @yield('content')
            </main>
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

        <!-- Page Specific Scripts -->
        @yield('scripts')
        @stack('scripts')

        <footer class="bg-light text-center text-lg-start mt-5">
            <div class="container-fluid">
                <div class="row">
                </div>
            </div>
        </footer>

        @auth
        <!-- ============================================================ -->
        <!-- SYMCORE AI CHATBOT                                            -->
        <!-- ============================================================ -->
        <div id="symcore-chatbot" style="position:fixed;bottom:28px;right:28px;z-index:1055;">

            <!-- Floating Toggle Button -->
            <button id="chatbot-toggle" onclick="chatbotToggle()"
                title="Symcore AI Assistant"
                style="width:56px;height:56px;border-radius:50%;border:none;background:linear-gradient(135deg,#1d4ed8,#3b82f6);
                       color:#fff;font-size:1.3rem;box-shadow:0 4px 18px rgba(29,78,216,.5);
                       cursor:pointer;display:flex;align-items:center;justify-content:center;
                       transition:transform .2s,box-shadow .2s;overflow:hidden;padding:0;"
                onmouseenter="this.style.transform='scale(1.08)';this.style.boxShadow='0 6px 24px rgba(29,78,216,.7)'"
                onmouseleave="this.style.transform='scale(1)';this.style.boxShadow='0 4px 18px rgba(29,78,216,.5)'">
                <img id="chatbot-toggle-icon" src="https://i.ibb.co.com/FjyxLbK/20260302-1522-Image-Generation-simple-compose-01kjpt8j8ffcybrs13c1ac2pk3.webp"
                     alt="AI" style="width:56px;height:56px;object-fit:cover;border-radius:50%;">
            </button>

            <!-- Chat Window -->
            <div id="chatbot-window"
                style="display:none;position:absolute;bottom:68px;right:0;width:370px;height:520px;
                       background:#fff;border-radius:18px;box-shadow:0 8px 40px rgba(0,0,0,.18);
                       display:none;flex-direction:column;overflow:hidden;border:1px solid #e5e7eb;">

                <!-- Header -->
                <div style="background:linear-gradient(135deg,#1d4ed8,#3b82f6);padding:14px 16px;
                            display:flex;align-items:center;justify-content:space-between;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <div style="width:36px;height:36px;border-radius:50%;overflow:hidden;
                                    display:flex;align-items:center;justify-content:center;border:2px solid rgba(255,255,255,.4);">
                            <img src="https://i.ibb.co.com/FjyxLbK/20260302-1522-Image-Generation-simple-compose-01kjpt8j8ffcybrs13c1ac2pk3.webp"
                                 alt="SymBot" style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                        </div>
                        <div>
                            <div style="color:#fff;font-weight:600;font-size:.9rem;line-height:1.2;">SymBot</div>
                            <div style="color:rgba(255,255,255,.75);font-size:.72rem;">
                                <span style="width:7px;height:7px;background:#4ade80;border-radius:50%;display:inline-block;margin-right:4px;"></span>Online
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <!-- Language Selector -->
                        <select id="chatbot-lang" onchange="chatbotSetLang(this.value)"
                            style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3);
                                   border-radius:8px;padding:3px 6px;font-size:.72rem;cursor:pointer;outline:none;">
                            <option value="auto" style="color:#000;">🌐 Auto</option>
                            <option value="id"   style="color:#000;">🇮🇩 ID</option>
                            <option value="en"   style="color:#000;">🇬🇧 EN</option>
                            <option value="zh"   style="color:#000;">🇨🇳 ZH</option>
                        </select>
                        <!-- Clear -->
                        <button onclick="chatbotClear()" title="Clear chat"
                            style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:8px;
                                   width:28px;height:28px;cursor:pointer;font-size:.8rem;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                        <!-- Close -->
                        <button onclick="chatbotToggle()" title="Close"
                            style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:8px;
                                   width:28px;height:28px;cursor:pointer;font-size:.85rem;display:flex;align-items:center;justify-content:center;">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="chatbot-messages"
                    style="flex:1;overflow-y:auto;padding:14px 14px 6px;display:flex;flex-direction:column;gap:10px;
                           background:#f8f9fc;">
                    <!-- Welcome message -->
                    <div class="cb-msg cb-msg-ai">
                        <div class="cb-bubble cb-bubble-ai">
                            👋 Halo! Saya <strong>Symcore AI</strong>.<br>
                            Saya bisa membantu Anda dengan informasi sistem, data real-time, atau pertanyaan umum.<br><br>
                            <em style="font-size:.8rem;opacity:.75;">Ketik dalam Bahasa Indonesia, English, atau 中文 — saya akan menyesuaikan.</em>
                        </div>
                    </div>
                </div>

                <!-- Typing Indicator (hidden by default) -->
                <div id="chatbot-typing" style="padding:0 14px 6px;display:none;">
                    <div class="cb-bubble cb-bubble-ai" style="padding:10px 14px;">
                        <span class="cb-dots"><span></span><span></span><span></span></span>
                    </div>
                </div>

                <!-- Input Area -->
                <div style="padding:10px 12px;border-top:1px solid #e5e7eb;background:#fff;display:flex;gap:8px;align-items:flex-end;">
                    <textarea id="chatbot-input" placeholder="Ketik pesan..." rows="1"
                        onkeydown="chatbotKeydown(event)"
                        oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,96)+'px'"
                        style="flex:1;resize:none;border:1px solid #e5e7eb;border-radius:12px;padding:9px 12px;
                               font-size:.85rem;outline:none;font-family:inherit;line-height:1.4;max-height:96px;
                               transition:border-color .2s;"
                        onfocus="this.style.borderColor='#3b82f6'"
                        onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    <button onclick="chatbotSend()" id="chatbot-send-btn"
                        style="width:38px;height:38px;border-radius:50%;border:none;
                               background:linear-gradient(135deg,#1d4ed8,#3b82f6);
                               color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;
                               flex-shrink:0;transition:opacity .2s;"
                        title="Send">
                        <i class="fas fa-paper-plane" style="font-size:.8rem;"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Chatbot Styles -->
        <style>
            .cb-msg { display:flex; }
            .cb-msg-user { justify-content:flex-end; }
            .cb-msg-ai   { justify-content:flex-start; }
            .cb-bubble {
                max-width:82%;padding:9px 13px;border-radius:16px;font-size:.84rem;
                line-height:1.5;word-break:break-word;
            }
            .cb-bubble-ai {
                background:#fff;color:#1f2937;border:1px solid #e5e7eb;
                border-bottom-left-radius:4px;box-shadow:0 1px 3px rgba(0,0,0,.06);
            }
            .cb-bubble-user {
                background:linear-gradient(135deg,#1d4ed8,#3b82f6);color:#fff;
                border-bottom-right-radius:4px;
            }
            .cb-time {
                font-size:.68rem;color:#9ca3af;margin-top:3px;text-align:right;
            }
            /* Typing dots */
            .cb-dots { display:inline-flex;gap:4px;align-items:center; }
            .cb-dots span {
                width:7px;height:7px;border-radius:50%;background:#9ca3af;
                animation:cb-bounce .9s infinite ease-in-out;
            }
            .cb-dots span:nth-child(2) { animation-delay:.15s; }
            .cb-dots span:nth-child(3) { animation-delay:.3s; }
            @keyframes cb-bounce {
                0%,80%,100% { transform:scale(.7);opacity:.5; }
                40%         { transform:scale(1);opacity:1; }
            }
            /* Scrollbar */
            #chatbot-messages::-webkit-scrollbar { width:4px; }
            #chatbot-messages::-webkit-scrollbar-track { background:transparent; }
            #chatbot-messages::-webkit-scrollbar-thumb { background:#d1d5db;border-radius:4px; }
            /* Slide-in animation */
            @keyframes cb-slide-in { from { opacity:0;transform:translateY(16px); } to { opacity:1;transform:translateY(0); } }
            .cb-slide-in { animation:cb-slide-in .25s ease; }
        </style>

        <!-- Chatbot Script -->
        <script>
        (function() {
            let cbOpen    = false;
            let cbHistory = [];
            let cbLang    = 'auto';
            let cbLoading = false;

            const ROUTE = "{{ route('chatbot.message') }}";
            const CSRF  = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

            window.chatbotToggle = function() {
                cbOpen = !cbOpen;
                const win  = document.getElementById('chatbot-window');
                const btn  = document.getElementById('chatbot-toggle');
                if (cbOpen) {
                    win.style.display = 'flex';
                    btn.innerHTML = '<i class="fas fa-times" style="color:#fff;font-size:1.1rem;"></i>';
                    setTimeout(() => document.getElementById('chatbot-input')?.focus(), 100);
                    chatbotScroll();
                } else {
                    win.style.display = 'none';
                    btn.innerHTML = '<img src="https://i.ibb.co.com/FjyxLbK/20260302-1522-Image-Generation-simple-compose-01kjpt8j8ffcybrs13c1ac2pk3.webp" alt="AI" style="width:56px;height:56px;object-fit:cover;border-radius:50%;">';
                }
            };

            window.chatbotSetLang = function(val) {
                cbLang = val;
            };

            window.chatbotClear = function() {
                cbHistory = [];
                const box = document.getElementById('chatbot-messages');
                box.innerHTML = `
                    <div class="cb-msg cb-msg-ai cb-slide-in">
                        <div class="cb-bubble cb-bubble-ai">
                            👋 Chat cleared. How can I help you?<br>
                            <em style="font-size:.8rem;opacity:.75;">Ketik dalam Bahasa Indonesia, English, atau 中文.</em>
                        </div>
                    </div>`;
            };

            window.chatbotKeydown = function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    chatbotSend();
                }
            };

            window.chatbotSend = async function() {
                if (cbLoading) return;
                const input = document.getElementById('chatbot-input');
                const msg   = (input.value ?? '').trim();
                if (!msg) return;

                input.value = '';
                input.style.height = 'auto';

                // Append user bubble
                chatbotAppend('user', msg);
                cbHistory.push({ role: 'user', content: msg });

                // Show typing
                cbLoading = true;
                document.getElementById('chatbot-send-btn').style.opacity = '.5';
                document.getElementById('chatbot-typing').style.display = 'block';
                chatbotScroll();

                // Build message with optional language override
                let fullMsg = msg;
                if (cbLang !== 'auto') {
                    const langMap = { id: 'Respond in Bahasa Indonesia.', en: 'Respond in English.', zh: 'Respond in Mandarin Chinese (中文).' };
                    fullMsg = '[' + langMap[cbLang] + ']\n' + msg;
                }

                try {
                    const res = await fetch(ROUTE, {
                        method : 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                        body   : JSON.stringify({ message: fullMsg, history: cbHistory.slice(-10) }),
                    });
                    const data = await res.json();
                    const reply = data.reply ?? 'No response received.';

                    document.getElementById('chatbot-typing').style.display = 'none';
                    chatbotAppend('ai', reply);
                    cbHistory.push({ role: 'assistant', content: reply });
                } catch (err) {
                    document.getElementById('chatbot-typing').style.display = 'none';
                    chatbotAppend('ai', '⚠️ Connection error. Please try again.');
                } finally {
                    cbLoading = false;
                    document.getElementById('chatbot-send-btn').style.opacity = '1';
                    chatbotScroll();
                }
            };

            function chatbotAppend(role, text) {
                const box  = document.getElementById('chatbot-messages');
                const isAI = role === 'ai';
                const now  = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

                const div = document.createElement('div');
                div.className = `cb-msg cb-msg-${isAI ? 'ai' : 'user'} cb-slide-in`;

                // Convert markdown-ish: **bold**, newlines
                let html = text
                    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
                    .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
                    .replace(/\*(.*?)\*/g,'<em>$1</em>')
                    .replace(/\n/g,'<br>');

                div.innerHTML = `
                    <div>
                        <div class="cb-bubble cb-bubble-${isAI ? 'ai' : 'user'}">${html}</div>
                        <div class="cb-time">${now}</div>
                    </div>`;
                box.appendChild(div);
                chatbotScroll();
            }

            function chatbotScroll() {
                setTimeout(() => {
                    const box = document.getElementById('chatbot-messages');
                    if (box) box.scrollTop = box.scrollHeight;
                }, 50);
            }
        })();
        </script>
        @endauth
        <!-- ============================================================ -->
        <!-- END SYMCORE AI CHATBOT                                        -->
        <!-- ============================================================ -->
    </body>

</html>
