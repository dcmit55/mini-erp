<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="dark">

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

        <style>
            /* ── Nested Dropdown (HR sub-menus) ────────────── */
            @media (min-width: 992px) {
                .dropdown-submenu { position: relative; }
                .dropdown-submenu > .dropdown-menu {
                    top: 0; left: 100%; margin-top: -2px;
                    display: none;
                }
                .dropdown-submenu:hover > .dropdown-menu,
                .dropdown-submenu.open > .dropdown-menu { display: block; }
            }
            @media (max-width: 991.98px) {
                .dropdown-submenu > .dropdown-menu {
                    display: none;
                    padding-left: 1rem;
                }
                .dropdown-submenu.open > .dropdown-menu { display: block; }
                .dropdown-submenu > .dropdown-item { font-weight: 600; }
            }

            /* ── Mobile Sidebar ─────────────────────────────── */
            @media (max-width: 991.98px) {
                #sidebarBackdrop {
                    display: none;
                    position: fixed;
                    inset: 0;
                    background: rgba(0, 0, 0, .45);
                    z-index: 1040;
                    backdrop-filter: blur(2px);
                }

                #navbarSupportedContent {
                    position: fixed !important;
                    top: 0;
                    left: 0;
                    width: min(280px, 85vw);
                    height: 100vh !important;
                    overflow-y: auto;
                    z-index: 1045;
                    padding: 1rem .75rem 0;
                    border-right: 1px solid var(--bs-border-color);
                    box-shadow: 4px 0 24px rgba(0, 0, 0, .18);
                    transform: translateX(-100%);
                    transition: transform .28s ease, visibility .28s !important;
                    display: flex !important;
                    flex-direction: column;
                    visibility: hidden;
                    background: var(--bs-body-bg) !important;
                }

                #navbarSupportedContent.show,
                #navbarSupportedContent.collapsing {
                    transform: translateX(0) !important;
                    visibility: visible !important;
                    height: 100vh !important;
                }

                #navbarSupportedContent .dropdown-menu {
                    position: static !important;
                    box-shadow: none !important;
                    border: none;
                    padding: .25rem 0 .25rem 1rem;
                    background: transparent !important;
                }

                #navbarSupportedContent .navbar-nav .nav-link {
                    padding: .5rem .75rem;
                    border-radius: .375rem;
                }

                #navbarSupportedContent .navbar-nav .nav-link:hover {
                    background: var(--bs-tertiary-bg);
                }

                #navbarSupportedContent .navbar-nav {
                    flex-direction: column !important;
                }

                /* Left nav scrollable, right nav pinned to bottom */
                #navbarSupportedContent .navbar-nav.me-auto {
                    flex: 1;
                    overflow-y: auto;
                }

                #navbarSupportedContent .navbar-nav.ms-auto {
                    flex-direction: row !important;
                    align-items: center;
                    gap: .5rem;
                    padding: .75rem .25rem;
                    margin-top: auto;
                    border-top: 1px solid var(--bs-border-color);
                    flex-shrink: 0;
                }
            }

            @media (max-width: 991.98px) {
                #mainNavbar {
                    z-index: 1050;
                }
            }

            @media (min-width: 992px) {
                #sidebarBackdrop {
                    display: none !important;
                }

                .sidebar-header {
                    display: none !important;
                }
            }
        </style>

        <!-- Apply saved theme immediately to prevent flash -->
        <script>
            (function() {
                var t = localStorage.getItem('preferred-theme') || 'dark';
                document.documentElement.setAttribute('data-bs-theme', t);
            })();
        </script>

        <!-- Page Specific Styles -->
        @yield('styles')
        @stack('styles')
    </head>

    <body>
        <div id="app">
            <div id="sidebarBackdrop"></div>
            <nav id="mainNavbar" class="navbar navbar-expand-lg border-bottom shadow-sm sticky-top"
                style="background: var(--bs-body-bg); transition: background-color .2s, border-color .2s;">
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
                        <!-- Sidebar header — mobile only -->
                        <div
                            class="sidebar-header d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <span class="fw-bold">{{ config('app.name', 'DCM-app') }}</span>
                            <button type="button" id="sidebarCloseBtn" class="btn-close" aria-label="Close"></button>
                        </div>
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
                                        'lark/staging*',
                                    ];

                                    $procurementPrefixes = [
                                        'suppliers*',
                                        'purchase_requests*',
                                        'pre-shippings*',
                                        'shipping-management*',
                                        'goods-receive*',
                                        'indo-purchases*',
                                    ];
                                @endphp

                                <!-- Projects Menu -->
                                @can('production.project.view')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('projects*') || request()->is('internal-projects*') || request()->is('job-order-type-gradings*') ? 'active' : '' }}"
                                            href="#" id="projectsDropdown" role="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
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
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item {{ request()->is('job-order-type-gradings*') ? 'active' : '' }}"
                                                    href="{{ route('job-order-type-gradings.index') }}">
                                                    <i class="fas fa-layer-group me-2"></i>Job Type
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                @endcan

                                <!-- Logistics Dropdown -->
                                @canany(['logistic.inventory.view', 'logistic.inventory-batch.view',
                                    'logistic.stock-adjustment.view', 'logistic.material-request.view',
                                    'logistic.goods-out.view', 'logistic.goods-in.view', 'logistic.material-usage.view',
                                    'lark.staging.view'])
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ isDropdownActive($logisticsPrefixes) ? 'active' : '' }}"
                                            href="#" id="logisticsDropdown" role="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i></i>Logistics
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="logisticsDropdown">
                                            @can('logistic.inventory.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('inventory') || (request()->is('inventory/*') && !request()->is('inventory-batch*')) ? 'active' : '' }}"
                                                        href="{{ route('inventory.index') }}">
                                                        <i class="fas fa-boxes me-2"></i>Inventory Stock
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.inventory-batch.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('inventory-batch*') ? 'active' : '' }}"
                                                        href="{{ route('inventory-batch.index') }}">
                                                        <i class="fas fa-layer-group me-2"></i>Inventory Batches
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.stock-adjustment.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('stock-adjustments*') ? 'active' : '' }}"
                                                        href="{{ route('stock-adjustments.index') }}">
                                                        <i class="bi bi-sliders me-2"></i>Stock Adjustment
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.material-request.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                        href="{{ route('material_requests.index') }}">
                                                        <i class="fas fa-clipboard-list me-2"></i>Material Request
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.goods-out.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('goods_out*') ? 'active' : '' }}"
                                                        href="{{ route('goods_out.index') }}">
                                                        <i class="fas fa-arrow-right me-2"></i>Goods Out
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.goods-in.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('goods_in*') ? 'active' : '' }}"
                                                        href="{{ route('goods_in.index') }}">
                                                        <i class="fas fa-arrow-left me-2"></i>Goods In
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.material-usage.view')
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
                                            @endcan
                                            @can('lark.staging.view')
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li class="dropdown-header">Inventory Incoming</li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('indo-purchases*') ? 'active' : '' }}"
                                                        href="{{ route('indo-purchases.index') }}">
                                                        <i class="fas fa-file-invoice-dollar me-2"></i>Indo Purchase
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/inventory*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.inventory') }}">
                                                        <i class="fas fa-globe me-2"></i>International Purchase
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcanany

                                <!-- Procurement Dropdown -->
                                @canany(['procurement.po.view', 'procurement.supplier.view',
                                    'procurement.shipping.view', 'lark.staging.view'])
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ isDropdownActive($procurementPrefixes) ? 'active' : '' }}"
                                            href="#" id="procurementDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Procurement
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
                                            @can('lark.staging.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('indo-purchases*') ? 'active' : '' }}"
                                                        href="{{ route('indo-purchases.index') }}">
                                                        <i class="fas fa-file-invoice-dollar me-2"></i>Indo Purchase
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/inventory*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.inventory') }}">
                                                        <i class="fas fa-globe me-2"></i>International Purchase
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('procurement.supplier.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('suppliers*') ? 'active' : '' }}"
                                                        href="{{ route('suppliers.index') }}">
                                                        <i class="fas fa-truck me-2"></i>Suppliers
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('procurement.po.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('purchase_requests*') ? 'active' : '' }}"
                                                        href="{{ route('purchase_requests.index') }}">
                                                        <i class="fas fa-clipboard-check me-2"></i>Purchase Request
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('lark.staging.view')
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li class="dropdown-header">Lark Staging</li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/bt-sg-courier*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.bt-sg-courier') }}">
                                                        <i class="fas fa-truck me-2"></i>BT-SG Courier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/sg-bt-courier*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.sg-bt-courier') }}">
                                                        <i class="fas fa-truck-loading me-2"></i>SG-BT Courier
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/bt-sg-items*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.bt-sg-items') }}">
                                                        <i class="fas fa-box me-2"></i>BT-SG Item Tracking
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('lark/staging/sg-bt-items*') ? 'active' : '' }}"
                                                        href="{{ route('lark.staging.sg-bt-items') }}">
                                                        <i class="fas fa-boxes me-2"></i>SG-BT Item Tracking
                                                    </a>
                                                </li>
                                            @endcan
                                            {{-- Hidden: Pre Shippings, Shipping Management, Goods Receive --}}
                                            {{-- @can('procurement.shipping.view')
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
                                            @endcan --}}
                                        </ul>
                                    </li>
                                @endcanany

                                <!-- Productions Dropdown -->
                                @canany(['production.jo.view', 'production.material-planning.view',
                                    'logistic.material-request.view', 'logistic.material-usage.view', 'hr.overtime.view'])
                                    @php
                                        $deptLeavePendingCount = 0;
                                        $deptMap = \App\Models\Hr\LeaveRequest::DEPT_ROLE_MAP ?? [];
                                        $deptRole = auth()->user()->role;
                                        $allProdDepts = \App\Models\Hr\LeaveRequest::getDeptApprovalDepartments() ?? [];

                                        if (isset($deptMap[$deptRole])) {
                                            $mappedDepts = (array) $deptMap[$deptRole];
                                            $deptLeavePendingCount = \App\Models\Hr\LeaveRequest::where(
                                                'approval_dept',
                                                'pending',
                                            )
                                                ->whereHas(
                                                    'employee.department',
                                                    fn($q) => $q->whereIn('name', $mappedDepts),
                                                )
                                                ->count();
                                        } elseif (in_array($deptRole, ['super_admin', 'admin'])) {
                                            $deptLeavePendingCount = \App\Models\Hr\LeaveRequest::where(
                                                'approval_dept',
                                                'pending',
                                            )
                                                ->whereHas(
                                                    'employee.department',
                                                    fn($q) => $q->whereIn('name', $allProdDepts),
                                                )
                                                ->count();
                                        }
                                    @endphp
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('job-orders*') || request()->is('quick-timer*') || request()->is('material_usage*') || request()->is('employees/*/timing*') || request()->is('material-planning*') || request()->is('overtime-requests*') || request()->routeIs('leave_requests.dept-approvals') ? 'active' : '' }}"
                                            href="#" id="productionsDropdown" role="button"
                                            data-bs-toggle="dropdown" aria-expanded="false">
                                            <i></i>Productions
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="productionsDropdown">
                                            @can('production.jo.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('job-orders*') ? 'active' : '' }}"
                                                        href="{{ route('job-orders.index') }}">
                                                        <i class="fas fa-tasks me-2"></i>Job Order
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.material-request.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('material_requests*') ? 'active' : '' }}"
                                                        href="{{ route('material_requests.index') }}">
                                                        <i class="fas fa-clipboard-list me-2"></i>Material Request
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('logistic.material-usage.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('material_usage*') ? 'active' : '' }}"
                                                        href="{{ route('material_usage.index') }}">
                                                        <i class="fas fa-balance-scale me-2"></i>Material Usage
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('production.material-planning.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('material-planning*') ? 'active' : '' }}"
                                                        href="{{ route('material_planning.index') }}">
                                                        <i class="fas fa-calendar-alt me-2"></i>Material Planning
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('hr.overtime.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('overtime-requests.*') ? 'active' : '' }}"
                                                        href="{{ route('overtime-requests.index') }}">
                                                        <i class="fas fa-hourglass-half me-2"></i>Overtime Requests
                                                    </a>
                                                </li>
                                            @endcan
                                            @if (isset($deptMap[$deptRole]) || in_array($deptRole, ['super_admin', 'admin']))
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('leave_requests.dept-approvals') ? 'active' : '' }}"
                                                        href="{{ route('leave_requests.dept-approvals') }}">
                                                        <i class="fas fa-calendar-check me-2"></i>Leave Approvals
                                                        @if ($deptLeavePendingCount > 0)
                                                            <span class="badge bg-danger rounded-pill ms-1"
                                                                style="font-size:0.6rem;">{{ $deptLeavePendingCount > 99 ? '99+' : $deptLeavePendingCount }}</span>
                                                        @endif
                                                    </a>
                                                </li>
                                            @endif
                                        </ul>
                                    </li>
                                @endcanany

                                <!-- Timing Menu (Dedicated) -->
                                @canany(['production.timing.view', 'production.mascot-timing.view',
                                    'production.costume-timing.view', 'production.animatronics-timing.view',
                                    'production.timing-monitor.view'])
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('costume-timing*') || request()->is('animatronics-timing*') || request()->is('mascot-timing*') || request()->is('timing-monitor*') || request()->is('timing/live-workstation*') || request()->is('timing-approval*') || request()->is('timings*') || request()->is('timing-planner*') ? 'active' : '' }}"
                                            href="#" id="timingDropdown" role="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i></i>Timing
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="timingDropdown">
                                            @can('production.costume-timing.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('costume-timing*') ? 'active' : '' }}"
                                                        href="{{ route('costume-timing.index') }}">
                                                        <i class="fas fa-cut me-2"></i>Costume Timing
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('production.animatronics-timing.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('animatronics-timing*') ? 'active' : '' }}"
                                                        href="{{ route('animatronics-timing.index') }}">
                                                        <i class="fas fa-robot me-2"></i>Animatronics Timing
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('production.mascot-timing.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('mascot-timing*') ? 'active' : '' }}"
                                                        href="{{ route('mascot-timing.index') }}">
                                                        <i class="fas fa-masks-theater me-2"></i>Mascot Timing
                                                    </a>
                                                </li>
                                            @endcan
                                            @canany(['production.timing-monitor.view', 'production.timing.view'])
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('timing/live-workstation*') ? 'active' : '' }}"
                                                        href="{{ route('live-workstation.index') }}">
                                                        <i class="fas fa-desktop me-2"></i>Live Workstation
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('timing-monitor*') ? 'active' : '' }}"
                                                        href="{{ route('timing-monitor.index') }}">
                                                        <i class="fas fa-tv me-2"></i>Running Monitor
                                                    </a>
                                                </li>
                                            @endcanany
                                            @can('production.timing.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('timing-approval*') ? 'active' : '' }}"
                                                        href="{{ route('timing-approval.index') }}">
                                                        <i class="fas fa-check me-2"></i>Timing Approval
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcanany

                                <!-- Finances Dropdown -->
                                @canany(['finance.costing.view', 'finance.currency.view', 'procurement.po.approve',
                                    'finance.purchase-edited.view', 'finance.kasbon.view'])
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
                                            @can('finance.currency.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('currencies*') ? 'active' : '' }}"
                                                        href="{{ route('currencies.index') }}">
                                                        <i class="fas fa-money-bill me-2"></i>Currency
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('finance.costing.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('costing-report*') ? 'active' : '' }}"
                                                        href="{{ route('costing.report') }}">
                                                        <i class="fas fa-chart-line me-2"></i>Costing Project
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
                                            @endcan
                                            @can('procurement.po.approve')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('purchase-approvals*') ? 'active' : '' }}"
                                                        href="{{ route('purchase-approvals.index') }}">
                                                        <i class="fas fa-clipboard-check me-2"></i>Purchase Approvals
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('finance.purchase-edited.view')
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('purchase-edited*') ? 'active' : '' }}"
                                                        href="{{ route('purchase-edited.index') }}">
                                                        <i class="fas fa-edit me-2"></i>Purchase Edited
                                                    </a>
                                                </li>
                                            @endcan
                                            @can('finance.kasbon.view')
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('admin/kasbon') ? 'active' : '' }}"
                                                        href="{{ route('kasbon.admin.index') }}">
                                                        <i class="fas fa-hand-holding-usd me-2"></i>Cash Advance
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('admin/kasbon/installments*') ? 'active' : '' }}"
                                                        href="{{ route('kasbon.admin.installments') }}">
                                                        <i class="fas fa-calendar-check me-2"></i>Installment Monitoring
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcanany

                                <!-- HR Dropdown (DIPERBARUI) -->
                                @auth
                                    @can('hr.employees.view')
                                        @php
                                            $hrOvertimePendingCount = \App\Models\Hr\OvertimeRequest::whereIn(
                                                'status',
                                                ['submitted', 'draft'],
                                            )
                                                ->where(function ($q) {
                                                    $q->where('hr_approval_status', 'pending')->orWhereNull(
                                                        'hr_approval_status',
                                                    );
                                                })
                                                ->count();
                                            $directorOvertimePendingCount = \App\Models\Hr\OvertimeRequest::whereIn(
                                                'status',
                                                ['submitted', 'draft'],
                                            )
                                                ->where(function ($q) {
                                                    $q->where('director_approval_status', 'pending')->orWhereNull(
                                                        'director_approval_status',
                                                    );
                                                })
                                                ->count();
                                            $hrLeavePendingCount = \App\Models\Hr\LeaveRequest::where(
                                                'approval_1',
                                                'pending',
                                            )
                                                ->where('approval_dept', 'approved')
                                                ->count();
                                            $directorLeavePendingCount = \App\Models\Hr\LeaveRequest::where(
                                                'approval_2',
                                                'pending',
                                            )
                                                ->where('approval_1', 'approved')
                                                ->count();
                                        @endphp
                                        @php
                                            $hrNavActive = request()->is('employees*')
                                                || request()->is('hr/*')
                                                || request()->routeIs('leave_requests.index')
                                                || request()->routeIs('attendance-logs.*')
                                                || request()->routeIs('overtime-requests.*')
                                                || request()->routeIs('overtime-pays.*')
                                                || request()->routeIs('fingerspot.*')
                                                || request()->routeIs('session-shifts.*')
                                                || request()->routeIs('warning-letters.*')
                                                || request()->routeIs('warning-batches.*')
                                                || request()->routeIs('symcore-export.*');
                                        @endphp
                                        <li class="nav-item dropdown">
                                            <a class="nav-link dropdown-toggle {{ $hrNavActive ? 'active' : '' }}"
                                                href="#" id="hrDropdown" role="button"
                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                <i></i>HR
                                            </a>
                                            <ul class="dropdown-menu" aria-labelledby="hrDropdown">

                                                {{-- HR Dashboard --}}
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('hr.dashboard') ? 'active' : '' }}"
                                                       href="{{ route('hr.dashboard') }}">
                                                        <i class="fas fa-chart-pie me-2"></i>HR Dashboard
                                                    </a>
                                                </li>
                                                <li><hr class="dropdown-divider"></li>

                                                {{-- Record --}}
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('hr.record') || request()->is('employees*') || request()->routeIs('fingerspot.*') || (request()->routeIs('session-shifts.*') && !request()->routeIs('session-shifts.live-monitor')) || request()->routeIs('symcore-export.*') ? 'active' : '' }}"
                                                       href="{{ route('hr.record') }}">
                                                        <i class="fas fa-folder-open me-2"></i>Record
                                                    </a>
                                                </li>

                                                {{-- Management --}}
                                                @can('hr.attendance.view')
                                                <li>
                                                    @php
                                                        $mgmtPending = ($hrLeavePendingCount ?? 0) + ($directorLeavePendingCount ?? 0) + ($hrOvertimePendingCount ?? 0) + ($directorOvertimePendingCount ?? 0);
                                                    @endphp
                                                    <a class="dropdown-item d-flex align-items-center justify-content-between {{ request()->routeIs('hr.management') || request()->routeIs('leave_requests.index') || request()->routeIs('overtime-requests.*') || request()->routeIs('overtime-pays.*') || request()->routeIs('warning-letters.*') || request()->routeIs('warning-batches.*') || request()->routeIs('leave_requests.hr-approvals') || request()->routeIs('leave_requests.director-approvals') ? 'active' : '' }}"
                                                       href="{{ route('hr.management') }}">
                                                        <span><i class="fas fa-tasks me-2"></i>Management</span>
                                                        @if($mgmtPending > 0)
                                                            <span class="badge bg-danger rounded-pill ms-2" style="font-size:0.6rem;">{{ $mgmtPending > 99 ? '99+' : $mgmtPending }}</span>
                                                        @endif
                                                    </a>
                                                </li>

                                                {{-- Attendance --}}
                                                <li>
                                                    <a class="dropdown-item {{ request()->routeIs('hr.attendance-hub') || request()->routeIs('attendance-logs.*') || request()->routeIs('session-shifts.live-monitor') ? 'active' : '' }}"
                                                       href="{{ route('hr.attendance-hub') }}">
                                                        <i class="fas fa-clock me-2"></i>Attendance
                                                    </a>
                                                </li>
                                                @endcan

                                            </ul>
                                        </li>
                                    @endcan
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
                                @can('admin.users.view')
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle {{ request()->is('users*') || request()->is('departments*') || request()->routeIs('trash.index') || request()->is('audit*') ? 'active' : '' }}"
                                            href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
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
                                            @can('admin.audit.view')
                                                <li>
                                                    <hr class="dropdown-divider">
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('audit*') ? 'active' : '' }}"
                                                        href="{{ route('audit.index') }}">
                                                        <i class="fas fa-clipboard-list me-2"></i>Audit Log
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('feature-announcements*') ? 'active' : '' }}"
                                                        href="{{ route('feature-announcements.index') }}">
                                                        <i class="bi bi-megaphone-fill me-2"></i>Announcements
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item {{ request()->is('admin/roles*') ? 'active' : '' }}"
                                                        href="{{ route('admin.roles.index') }}">
                                                        <i class="bi bi-shield-lock-fill me-2"></i>Role & Permissions
                                                    </a>
                                                </li>
                                            @endcan
                                        </ul>
                                    </li>
                                @endcan
                            @endif


                        </ul>

                        <!-- Right Side Of Navbar -->
                        <ul class="navbar-nav ms-auto align-items-center">

                            {{-- ── Theme Switcher Dropdown ── --}}
                            <li class="nav-item me-2 dropdown">
                                <button id="themeToggleBtn"
                                    class="btn btn-sm px-2 py-1 rounded-3 dropdown-toggle border-0"
                                    data-bs-toggle="dropdown" aria-expanded="false" title="Switch Theme"
                                    style="font-size:1rem; line-height:1; transition: background .2s, color .2s;">
                                    <i id="themeToggleIcon" class="bi bi-circle-half"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow"
                                    style="min-width:160px; border-radius:.75rem; padding:.4rem .3rem;">
                                    <li>
                                        <div class="px-3 py-1 text-muted"
                                            style="font-size:.7rem; font-weight:600; text-transform:uppercase; letter-spacing:.06em;">
                                            Appearance
                                        </div>
                                    </li>
                                    <li>
                                        <button
                                            class="dropdown-item d-flex align-items-center gap-2 theme-option rounded-2"
                                            data-theme="light" style="padding:.45rem .75rem;">
                                            <span
                                                class="d-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-10"
                                                style="width:26px;height:26px;flex-shrink:0;">
                                                <i class="bi bi-brightness-high text-warning"
                                                    style="font-size:.85rem;"></i>
                                            </span>
                                            <span class="fw-semibold" style="font-size:.85rem;">Light</span>
                                            <i
                                                class="bi bi-check-lg ms-auto theme-check d-none text-success fw-bold"></i>
                                        </button>
                                    </li>
                                    <li>
                                        <button
                                            class="dropdown-item d-flex align-items-center gap-2 theme-option rounded-2"
                                            data-theme="dark" style="padding:.45rem .75rem;">
                                            <span
                                                class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10"
                                                style="width:26px;height:26px;flex-shrink:0;">
                                                <i class="bi bi-moon-stars-fill text-primary"
                                                    style="font-size:.82rem;"></i>
                                            </span>
                                            <span class="fw-semibold" style="font-size:.85rem;">Dark</span>
                                            <i
                                                class="bi bi-check-lg ms-auto theme-check d-none text-success fw-bold"></i>
                                        </button>
                                    </li>
                                </ul>
                            </li>

                            @guest
                            @else
                                {{-- Notification Toggle Button --}}
                                @auth
                                    <li class="nav-item d-flex align-items-center me-1">
                                        <button id="notifToggleBtn" title="Toggle Notifications"
                                            class="btn btn-sm border-0 px-2 py-1"
                                            style="font-size:1.1rem; background:transparent; transition:color .2s;">
                                            <i id="notifBellIcon" class="bi bi-bell-fill"></i>
                                        </button>
                                    </li>
                                @endauth

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

        <!-- ── Theme Toggle Script ── -->
        <script>
            (function() {
                var html = document.documentElement;

                function applyTheme(theme) {
                    html.setAttribute('data-bs-theme', theme);
                    localStorage.setItem('preferred-theme', theme);

                    // Update toggle button icon and style
                    var icon = document.getElementById('themeToggleIcon');
                    var btn = document.getElementById('themeToggleBtn');
                    if (icon) {
                        icon.className = theme === 'dark' ?
                            'bi bi-moon-stars-fill' :
                            'bi bi-brightness-high';
                    }
                    if (btn) {
                        if (theme === 'dark') {
                            btn.style.color = '#a78bfa'; // soft violet — visible on dark bg
                        } else {
                            btn.style.color = '#f59e0b'; // amber — visible on light bg
                        }
                    }

                    // Update checkmarks in dropdown
                    document.querySelectorAll('.theme-option').forEach(function(option) {
                        var check = option.querySelector('.theme-check');
                        var isActive = option.dataset.theme === theme;
                        if (check) {
                            check.classList.toggle('d-none', !isActive);
                        }
                        // Highlight active item row
                        option.style.background = ''; // reset inline bg
                        option.style.fontWeight = isActive ? '700' : '';
                    });
                }

                // Apply immediately (before DOMContentLoaded) — reads what the anti-flash script set
                applyTheme(html.getAttribute('data-bs-theme') || 'dark');

                document.addEventListener('DOMContentLoaded', function() {
                    // Re-sync icons/checkmarks after DOM is ready
                    applyTheme(html.getAttribute('data-bs-theme') || 'dark');

                    // Wire each dropdown option
                    document.querySelectorAll('.theme-option').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            applyTheme(btn.dataset.theme);
                        });
                    });
                });
            })();
        </script>

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

        <!-- Mobile Sidebar JS -->
        <script>
            (function() {
                var backdrop = document.getElementById('sidebarBackdrop');
                var sidebar = document.getElementById('navbarSupportedContent');
                var closeBtn = document.getElementById('sidebarCloseBtn');

                function closeSidebar() {
                    backdrop.style.display = 'none';
                    var bsCollapse = bootstrap.Collapse.getInstance(sidebar);
                    if (bsCollapse) bsCollapse.hide();
                }

                sidebar.addEventListener('show.bs.collapse', function() {
                    if (window.innerWidth < 992) backdrop.style.display = 'block';
                });

                sidebar.addEventListener('hide.bs.collapse', function() {
                    backdrop.style.display = 'none';
                });

                backdrop.addEventListener('click', closeSidebar);

                if (closeBtn) closeBtn.addEventListener('click', closeSidebar);

                // Close when a non-dropdown link is clicked
                sidebar.addEventListener('click', function(e) {
                    if (window.innerWidth >= 992) return;
                    var link = e.target.closest('a.nav-link, a.dropdown-item');
                    if (link && !link.classList.contains('dropdown-toggle')) {
                        closeSidebar();
                    }
                });
            })();
        </script>

        {{-- ============================================================ --}}
        {{-- FOOTER                                                        --}}
        {{-- ============================================================ --}}
        <footer
            style="
            background: linear-gradient(160deg, #0a0f1e 0%, #111827 50%, #0a0f1e 100%);
            border-top: 1px solid rgba(99,102,241,.3);
            color: #94a3b8;
            font-size: 0.76rem;
            margin-top: 3rem;
            position: relative;
            overflow: hidden;
        ">
            {{-- Top accent glow line --}}
            <div
                style="position:absolute;top:0;left:0;right:0;height:2px;
                background:linear-gradient(90deg,transparent 0%,#4f46e5 20%,#8b5cf6 50%,#4f46e5 80%,transparent 100%);
                opacity:.8;">
            </div>

            <div class="container-fluid px-3 px-md-4 py-3">

                {{-- Row 1: Main info (3 cols on desktop, stack on mobile) --}}
                <div class="row align-items-center g-3 pb-2" style="border-bottom:1px solid rgba(255,255,255,.05);">

                    {{-- LEFT: Brand + copyright --}}
                    <div class="col-12 col-md-4">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <div
                                style="width:26px;height:26px;border-radius:6px;flex-shrink:0;
                                background:linear-gradient(135deg,#4f46e5,#8b5cf6);
                                display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-cpu-fill" style="color:#fff;font-size:.78rem;"></i>
                            </div>
                            <span class="fw-bold" style="color:#e2e8f0;font-size:.9rem;letter-spacing:.4px;">
                                Symcore ERP
                            </span>
                            <span
                                style="
                                background:rgba(99,102,241,.18);border:1px solid rgba(99,102,241,.4);
                                color:#a5b4fc;border-radius:4px;padding:1px 6px;
                                font-size:.65rem;font-weight:700;letter-spacing:.6px;">
                                v{{ config('app.version', '2.0.0') }}
                            </span>
                        </div>
                        <div style="color:#475569;line-height:1.6;">
                            &copy; {{ date('Y') }}
                            <span style="color:#94a3b8;font-weight:500;">
                                {{ config('app.company', 'PT The Costume Magnifique') }}
                            </span>
                            <br class="d-none d-sm-block d-md-none">
                            <span class="d-inline d-sm-none d-md-inline">&nbsp;&mdash;&nbsp;</span>
                            All rights reserved.
                        </div>
                    </div>

                    {{-- CENTER: Dev lineage --}}
                    <div class="col-12 col-md-4 text-md-center">
                        <div class="d-inline-flex flex-column align-items-center gap-1 px-3 py-2 rounded-3 w-100 w-md-auto"
                            style="background:rgba(99,102,241,.07);border:1px solid rgba(99,102,241,.18);
                            max-width:280px;">

                            <div class="d-flex align-items-center gap-1" style="font-size:.68rem;color:#334155;">
                                <i class="bi bi-people" style="font-size:.62rem;"></i>
                                <span>Originally built {{ config('app.build_year', '2025') }} by DCMIT Gen 1</span>
                            </div>
                            <div class="d-flex align-items-center gap-1" style="font-size:.68rem;color:#334155;">
                                <i class="bi bi-arrow-right" style="font-size:.58rem;"></i>
                                <span>Continued by {{ config('app.developer', 'DCMIT Gen 2') }}
                                    ({{ date('Y') }})</span>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: Tech stack --}}
                    <div class="col-12 col-md-4">
                        <div class="d-flex flex-wrap justify-content-md-end align-items-center gap-2">
                            <span class="d-inline-flex align-items-center gap-1"
                                style="background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.2);
                                color:#6ee7b7;border-radius:20px;padding:3px 10px;white-space:nowrap;">
                                <i class="bi bi-shield-check" style="font-size:.7rem;"></i> Symcore
                            </span>
                            <span class="d-inline-flex align-items-center gap-1"
                                style="background:rgba(251,146,60,.08);border:1px solid rgba(251,146,60,.2);
                                color:#fed7aa;border-radius:20px;padding:3px 10px;white-space:nowrap;">
                                <i class="bi bi-box-fill" style="font-size:.7rem;"></i>
                                Laravel {{ Illuminate\Foundation\Application::VERSION }}
                            </span>
                        </div>
                    </div>

                </div>

                {{-- Row 2: Bottom bar — build info + small note --}}
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-1 pt-2"
                    style="color:#334155;">
                    <span>
                        Build&nbsp;
                        <span style="color:#4f4f6b;font-variant-numeric:tabular-nums;">
                            {{ config('app.build_year', '2025') }}.{{ date('m') }}
                        </span>
                        &nbsp;&bull;&nbsp; {{ config('app.name', 'Symcore') }} is a proprietary internal ERP system.
                    </span>
                    <span style="color:#1e293b;">
                        <i class="bi bi-lock-fill me-1" style="font-size:.65rem;"></i>Internal use only
                    </span>
                </div>

            </div>
        </footer>

        @auth
            <!-- ============================================================ -->
            <!-- SYMCORE AI CHATBOT                                            -->
            <!-- ============================================================ -->
            <div id="symcore-chatbot" style="position:fixed;bottom:28px;left:28px;z-index:1055;">

                <!-- Floating Toggle Button -->
                <button id="chatbot-toggle" onclick="chatbotToggle()" title="SymBot — Symcore AI Assistant"
                    style="width:58px;height:58px;border-radius:50%;border:none;
                       background:linear-gradient(135deg,#1d4ed8,#6366f1);
                       cursor:pointer;display:flex;align-items:center;justify-content:center;
                       box-shadow:0 4px 20px rgba(99,102,241,.55);
                       transition:transform .2s,box-shadow .2s;overflow:hidden;padding:0;">
                    <img id="cb-icon-img" src="https://i.ibb.co.com/7t90LZkN/chatbot.webp" alt="AI"
                        style="width:58px;height:58px;object-fit:cover;border-radius:50%;display:block;">
                    <i id="cb-icon-x" class="fas fa-times" style="color:#fff;font-size:1.2rem;display:none;"></i>
                </button>

                <!-- Unread badge -->
                <span id="cb-badge"
                    style="display:none;position:absolute;top:-4px;right:-4px;
                    background:#ef4444;color:#fff;font-size:10px;font-weight:700;
                    width:18px;height:18px;border-radius:50%;text-align:center;line-height:18px;
                    border:2px solid #fff;">1</span>

                <!-- Chat Window -->
                <div id="chatbot-window"
                    style="display:none;position:fixed;top:0;left:0;width:380px;
                       max-height:calc(100vh - 20px);
                       background:#fff;border-radius:20px;
                       box-shadow:0 12px 48px rgba(0,0,0,.18),0 2px 8px rgba(0,0,0,.08);
                       flex-direction:column;overflow:hidden;border:1px solid rgba(99,102,241,.15);">

                    <!-- Header -->
                    <div
                        style="background:linear-gradient(135deg,#1d4ed8 0%,#6366f1 100%);padding:14px 16px;
                            display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="position:relative;flex-shrink:0;">
                                <img src="https://i.ibb.co.com/7t90LZkN/chatbot.webp" alt="SymBot"
                                    style="width:38px;height:38px;object-fit:cover;border-radius:50%;
                                           border:2px solid rgba(255,255,255,.5);">
                                <span
                                    style="position:absolute;bottom:1px;right:1px;width:9px;height:9px;
                                    background:#4ade80;border-radius:50%;border:2px solid #4338ca;"></span>
                            </div>
                            <div>
                                <div style="color:#fff;font-weight:700;font-size:.9rem;letter-spacing:.01em;">SymBot</div>
                                <div style="color:rgba(255,255,255,.7);font-size:.7rem;">AI Assistant · Symcore ERP</div>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <select id="chatbot-lang" onchange="chatbotSetLang(this.value)"
                                style="background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.25);
                                   border-radius:8px;padding:3px 6px;font-size:.7rem;cursor:pointer;outline:none;">
                                <option value="auto" style="color:#000;">🌐 Auto</option>
                                <option value="id" style="color:#000;">🇮🇩 ID</option>
                                <option value="en" style="color:#000;">🇬🇧 EN</option>
                                <option value="zh" style="color:#000;">🇨🇳 ZH</option>
                            </select>
                            <button onclick="chatbotClear()" title="Clear chat"
                                style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:8px;
                                   width:28px;height:28px;cursor:pointer;font-size:.75rem;
                                   display:flex;align-items:center;justify-content:center;
                                   transition:background .15s;"
                                onmouseenter="this.style.background='rgba(255,255,255,.28)'"
                                onmouseleave="this.style.background='rgba(255,255,255,.15)'">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                            <button onclick="chatbotToggle()" title="Close"
                                style="background:rgba(255,255,255,.15);border:none;color:#fff;border-radius:8px;
                                   width:28px;height:28px;cursor:pointer;font-size:.85rem;
                                   display:flex;align-items:center;justify-content:center;
                                   transition:background .15s;"
                                onmouseenter="this.style.background='rgba(255,255,255,.28)'"
                                onmouseleave="this.style.background='rgba(255,255,255,.15)'">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Suggestion chips (shown only when no history) -->
                    <div id="cb-chips"
                        style="padding:10px 12px 4px;background:#f8f9ff;border-bottom:1px solid #eef0f8;
                            display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0;">
                        <span class="cb-chip" onclick="chatbotChip('Stok fur WL-2022 #63 Brown berapa?')">📦 Stok
                            material</span>
                        <span class="cb-chip" onclick="chatbotChip('Berapa karyawan aktif?')">👥 Data karyawan</span>
                        <span class="cb-chip" onclick="chatbotChip('Ada berapa cuti yang pending?')">🏖️ Leave
                            pending</span>
                        <span class="cb-chip" onclick="chatbotChip('Overtime pending bulan ini?')">⏱️ OT pending</span>
                    </div>

                    <!-- Messages Area -->
                    <div id="chatbot-messages"
                        style="flex:1;min-height:120px;max-height:380px;overflow-y:auto;padding:14px 14px 6px;
                               display:flex;flex-direction:column;gap:10px;background:#f8f9ff;">
                        <div class="cb-msg cb-msg-ai">
                            <div class="cb-bubble cb-bubble-ai">
                                👋 Halo! Saya <strong>SymBot</strong>, AI assistant Symcore ERP.<br>
                                Saya bisa menjawab pertanyaan tentang <strong>stok, karyawan, cuti, lembur</strong>, dan
                                data sistem lainnya secara <em>real-time</em>.<br><br>
                                <span style="font-size:.78rem;opacity:.7;">Coba klik salah satu topik di atas, atau ketik
                                    pertanyaan Anda.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div id="chatbot-typing" style="padding:4px 14px 8px;display:none;background:#f8f9ff;flex-shrink:0;">
                        <div class="cb-bubble cb-bubble-ai" style="padding:10px 16px;display:inline-flex;">
                            <span class="cb-dots"><span></span><span></span><span></span></span>
                        </div>
                    </div>

                    <!-- Input Area -->
                    <div
                        style="padding:10px 12px;border-top:1px solid #eef0f8;background:#fff;
                            display:flex;gap:8px;align-items:flex-end;flex-shrink:0;">
                        <textarea id="chatbot-input" placeholder="Tanya sesuatu..." rows="1" onkeydown="chatbotKeydown(event)"
                            oninput="this.style.height='auto';this.style.height=Math.min(this.scrollHeight,96)+'px'"
                            style="flex:1;resize:none;border:1.5px solid #e0e3ef;border-radius:14px;
                               padding:9px 13px;font-size:.84rem;outline:none;font-family:inherit;
                               line-height:1.45;max-height:96px;background:#f8f9ff;color:#1e1e2e;
                               transition:border-color .2s,background .2s;"
                            onfocus="this.style.borderColor='#6366f1';this.style.background='#fff';"
                            onblur="this.style.borderColor='#e0e3ef';this.style.background='#f8f9ff';"></textarea>
                        <button onclick="chatbotSend()" id="chatbot-send-btn" title="Send (Enter)"
                            style="width:40px;height:40px;border-radius:50%;border:none;
                               background:linear-gradient(135deg,#1d4ed8,#6366f1);
                               color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;
                               flex-shrink:0;transition:opacity .2s,transform .15s;box-shadow:0 2px 8px rgba(99,102,241,.4);"
                            onmouseenter="this.style.transform='scale(1.08)'"
                            onmouseleave="this.style.transform='scale(1)'">
                            <i class="fas fa-paper-plane" style="font-size:.8rem;margin-left:2px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Chatbot Styles -->
            <style>
                #chatbot-toggle:hover {
                    transform: scale(1.08) !important;
                    box-shadow: 0 6px 28px rgba(99, 102, 241, .7) !important;
                }

                .cb-msg {
                    display: flex;
                }

                .cb-msg-user {
                    justify-content: flex-end;
                }

                .cb-msg-ai {
                    justify-content: flex-start;
                }

                .cb-bubble {
                    max-width: 82%;
                    padding: 10px 14px;
                    border-radius: 18px;
                    font-size: .84rem;
                    line-height: 1.55;
                    word-break: break-word;
                }

                .cb-bubble-ai {
                    background: #fff;
                    color: #1e1e2e;
                    border: 1px solid #e8eaf6;
                    border-bottom-left-radius: 5px;
                    box-shadow: 0 1px 4px rgba(99, 102, 241, .08);
                }

                .cb-bubble-user {
                    background: linear-gradient(135deg, #1d4ed8, #6366f1);
                    color: #fff;
                    border-bottom-right-radius: 5px;
                    box-shadow: 0 2px 8px rgba(99, 102, 241, .3);
                }

                .cb-time {
                    font-size: .65rem;
                    color: #adb5bd;
                    margin-top: 3px;
                    text-align: right;
                }

                /* Suggestion chips */
                .cb-chip {
                    display: inline-block;
                    padding: 4px 10px;
                    border-radius: 20px;
                    background: #eef0f8;
                    color: #4338ca;
                    font-size: .72rem;
                    font-weight: 600;
                    cursor: pointer;
                    border: 1px solid #c7d2fe;
                    transition: background .15s, transform .1s;
                    white-space: nowrap;
                }

                .cb-chip:hover {
                    background: #c7d2fe;
                    transform: translateY(-1px);
                }

                /* Typing dots */
                .cb-dots {
                    display: inline-flex;
                    gap: 4px;
                    align-items: center;
                }

                .cb-dots span {
                    width: 7px;
                    height: 7px;
                    border-radius: 50%;
                    background: #9ca3af;
                    animation: cb-bounce .9s infinite ease-in-out;
                }

                .cb-dots span:nth-child(2) {
                    animation-delay: .15s;
                }

                .cb-dots span:nth-child(3) {
                    animation-delay: .3s;
                }

                @keyframes cb-bounce {

                    0%,
                    80%,
                    100% {
                        transform: scale(.7);
                        opacity: .5;
                    }

                    40% {
                        transform: scale(1);
                        opacity: 1;
                    }
                }

                /* Scrollbar */
                #chatbot-messages::-webkit-scrollbar {
                    width: 4px;
                }

                #chatbot-messages::-webkit-scrollbar-track {
                    background: transparent;
                }

                #chatbot-messages::-webkit-scrollbar-thumb {
                    background: #d1d5db;
                    border-radius: 4px;
                }

                /* Slide-in animation */
                @keyframes cb-slide-in {
                    from {
                        opacity: 0;
                        transform: translateY(16px);
                    }

                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .cb-slide-in {
                    animation: cb-slide-in .25s ease;
                }

                /* ── Mobile responsive ── */
                @media (max-width: 480px) {
                    #chatbot-window {
                        width: calc(100vw - 16px) !important;
                        border-radius: 16px !important;
                    }

                    #chatbot-messages {
                        max-height: calc(100vh - 230px) !important;
                    }

                    .cb-bubble {
                        max-width: 90%;
                        font-size: .82rem;
                    }

                    .cb-chip {
                        font-size: .70rem;
                        padding: 3px 8px;
                    }
                }
            </style>

            <!-- Chatbot Script -->
            <script>
                (function() {
                    let cbOpen = false;
                    let cbHistory = [];
                    let cbLang = 'auto';
                    let cbLoading = false;

                    const ROUTE = "{{ route('chatbot.message') }}";
                    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                    const LS_KEY = 'symbot_history_{{ auth()->id() }}';
                    const MAX_MSGS = 40; // max bubbles kept in storage

                    // ── Persist helpers ──────────────────────────────────────
                    function cbSave() {
                        try {
                            localStorage.setItem(LS_KEY, JSON.stringify(cbHistory.slice(-MAX_MSGS)));
                        } catch (e) {}
                    }

                    function cbLoad() {
                        try {
                            const raw = localStorage.getItem(LS_KEY);
                            return raw ? JSON.parse(raw) : [];
                        } catch (e) {
                            return [];
                        }
                    }

                    // ── Restore previous messages on page load ───────────────
                    (function restoreHistory() {
                        const saved = cbLoad();
                        if (!saved.length) return;
                        const box = document.getElementById('chatbot-messages');
                        box.innerHTML = ''; // clear welcome message
                        saved.forEach(item => {
                            chatbotAppend(item.role === 'user' ? 'user' : 'ai', item.content, item.time, true);
                        });
                        cbHistory = saved.map(i => ({
                            role: i.role,
                            content: i.content
                        }));
                    })();

                    // ── Toggle ───────────────────────────────────────────────
                    window.chatbotToggle = function() {
                        cbOpen = !cbOpen;
                        const win = document.getElementById('chatbot-window');
                        const img = document.getElementById('cb-icon-img');
                        const xIco = document.getElementById('cb-icon-x');
                        const badge = document.getElementById('cb-badge');
                        if (cbOpen) {
                            win.style.display = 'flex';
                            if (window._cbUpdateWindowSide) window._cbUpdateWindowSide();
                            img.style.display = 'none';
                            xIco.style.display = 'block';
                            if (badge) badge.style.display = 'none';
                            setTimeout(() => document.getElementById('chatbot-input')?.focus(), 100);
                            chatbotScroll();
                        } else {
                            win.style.display = 'none';
                            img.style.display = 'block';
                            xIco.style.display = 'none';
                        }
                    };

                    // ── Chip shortcut ─────────────────────────────────────────
                    window.chatbotChip = function(text) {
                        if (!cbOpen) chatbotToggle();
                        document.getElementById('chatbot-input').value = text;
                        chatbotSend();
                        // hide chips after first use
                        const chips = document.getElementById('cb-chips');
                        if (chips) chips.style.display = 'none';
                    };

                    window.chatbotSetLang = function(val) {
                        cbLang = val;
                    };

                    window.chatbotClear = function() {
                        cbHistory = [];
                        try {
                            localStorage.removeItem(LS_KEY);
                        } catch (e) {}
                        const box = document.getElementById('chatbot-messages');
                        box.innerHTML = `
                    <div class="cb-msg cb-msg-ai cb-slide-in">
                        <div class="cb-bubble cb-bubble-ai">
                            🗑️ Chat cleared.<br>
                            <span style="font-size:.78rem;opacity:.7;">Silakan tanya kembali.</span>
                        </div>
                    </div>`;
                        // show chips again
                        const chips = document.getElementById('cb-chips');
                        if (chips) chips.style.display = 'flex';
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
                        const msg = (input.value ?? '').trim();
                        if (!msg) return;

                        input.value = '';
                        input.style.height = 'auto';

                        const now = new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        chatbotAppend('user', msg, now);
                        cbHistory.push({
                            role: 'user',
                            content: msg,
                            time: now
                        });
                        cbSave();

                        cbLoading = true;
                        document.getElementById('chatbot-send-btn').style.opacity = '.5';
                        document.getElementById('chatbot-typing').style.display = 'block';
                        chatbotScroll();

                        let fullMsg = msg;
                        if (cbLang !== 'auto') {
                            const langMap = {
                                id: 'Respond in Bahasa Indonesia.',
                                en: 'Respond in English.',
                                zh: 'Respond in Mandarin Chinese (中文).'
                            };
                            fullMsg = '[' + langMap[cbLang] + ']\n' + msg;
                        }

                        try {
                            const res = await fetch(ROUTE, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF
                                },
                                body: JSON.stringify({
                                    message: fullMsg,
                                    history: cbHistory.slice(-10)
                                }),
                            });
                            const data = await res.json();
                            const reply = data.reply ?? 'No response received.';
                            const replyTime = new Date().toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit'
                            });

                            document.getElementById('chatbot-typing').style.display = 'none';
                            chatbotAppend('ai', reply, replyTime);
                            cbHistory.push({
                                role: 'assistant',
                                content: reply,
                                time: replyTime
                            });
                            cbSave();
                        } catch (err) {
                            document.getElementById('chatbot-typing').style.display = 'none';
                            chatbotAppend('ai', '⚠️ Connection error. Please try again.');
                        } finally {
                            cbLoading = false;
                            document.getElementById('chatbot-send-btn').style.opacity = '1';
                            chatbotScroll();
                        }
                    };

                    function chatbotAppend(role, text, time, silent) {
                        const box = document.getElementById('chatbot-messages');
                        const isAI = role === 'ai' || role === 'assistant';
                        const ts = time ?? new Date().toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit'
                        });

                        const div = document.createElement('div');
                        div.className = `cb-msg cb-msg-${isAI ? 'ai' : 'user'}` + (silent ? '' : ' cb-slide-in');

                        let html = text
                            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')
                            .replace(/\*(.*?)\*/g, '<em>$1</em>')
                            .replace(/\n/g, '<br>');

                        div.innerHTML = `
                    <div>
                        <div class="cb-bubble cb-bubble-${isAI ? 'ai' : 'user'}">${html}</div>
                        <div class="cb-time">${ts}</div>
                    </div>`;
                        box.appendChild(div);
                        if (!silent) chatbotScroll();
                    }

                    function chatbotScroll() {
                        setTimeout(() => {
                            const box = document.getElementById('chatbot-messages');
                            if (box) box.scrollTop = box.scrollHeight;
                        }, 50);
                    }
                })
                ();

                // ── Draggable AssistiveTouch — snap to left/right edge ───────
                (function() {
                    const el = document.getElementById('symcore-chatbot');
                    const toggle = el.querySelector('#chatbot-toggle');
                    const SNAP = 16; // px from screen edge when snapped
                    const THRESH = 6; // px movement to classify as drag vs click

                    let dragging = false,
                        hasDragged = false;
                    let startX, startY, origLeft, origBottom;
                    let currentSide = 'right';

                    // ── Persist ───────────────────────────────────────────────
                    const POS_KEY = 'symbot_pos_v2';

                    function savePos(side, bottom) {
                        try {
                            localStorage.setItem(POS_KEY, JSON.stringify({
                                side,
                                bottom
                            }));
                        } catch (e) {}
                    }

                    function loadPos() {
                        try {
                            return JSON.parse(localStorage.getItem(POS_KEY));
                        } catch (e) {
                            return null;
                        }
                    }

                    // ── Snap button to a side ─────────────────────────────────
                    function applySnap(side, bottom, animate) {
                        currentSide = side;
                        const maxBottom = window.innerHeight - el.offsetHeight - SNAP;
                        const safeBottom = Math.max(SNAP, Math.min(maxBottom, bottom));
                        el.style.transition = animate ?
                            'left .28s cubic-bezier(.25,.8,.25,1), right .28s cubic-bezier(.25,.8,.25,1), bottom .28s cubic-bezier(.25,.8,.25,1)' :
                            'none';
                        el.style.top = 'auto';
                        el.style.bottom = safeBottom + 'px';
                        if (side === 'right') {
                            el.style.right = SNAP + 'px';
                            el.style.left = 'auto';
                        } else {
                            el.style.left = SNAP + 'px';
                            el.style.right = 'auto';
                        }
                        savePos(side, safeBottom);
                        updateWindowSide();
                    }

                    // ── Chat window direction ─────────────────────────────────
                    function updateWindowSide() {
                        const win = document.getElementById('chatbot-window');
                        if (!win) return;

                        const btnRect = el.getBoundingClientRect();
                        const winW = win.offsetWidth || 380; // actual width (CSS handles mobile sizing)
                        const winH = win.offsetHeight || 520; // actual height (constrained by max-height)
                        const vW = window.innerWidth;
                        const vH = window.innerHeight;
                        const gap = 10;
                        const edge = 8;

                        // Horizontal: center on narrow screens; align to button side on desktop
                        let left;
                        if (vW <= 480) {
                            left = Math.max(edge, Math.round((vW - winW) / 2));
                        } else {
                            left = (currentSide === 'right') ?
                                btnRect.right - winW :
                                btnRect.left;
                            left = Math.max(edge, Math.min(vW - winW - edge, left));
                        }

                        // Vertical: prefer above button; fall back to below; always clamp
                        const spaceAbove = btnRect.top - gap;
                        const spaceBelow = vH - btnRect.bottom - gap;
                        let top;
                        if (spaceAbove >= winH || spaceAbove > spaceBelow) {
                            top = btnRect.top - winH - gap;
                            top = Math.max(edge, top);
                        } else {
                            top = btnRect.bottom + gap;
                            top = Math.min(vH - winH - edge, top);
                            top = Math.max(edge, top);
                        }

                        win.style.left = left + 'px';
                        win.style.right = 'auto';
                        win.style.top = top + 'px';
                        win.style.bottom = 'auto';
                    }

                    // Expose so chatbotToggle can call it
                    window._cbUpdateWindowSide = updateWindowSide;

                    // ── Init position ─────────────────────────────────────────
                    const saved = loadPos();
                    applySnap(saved ? saved.side : 'right', saved ? saved.bottom : 28, false);

                    // ── Drag handlers ─────────────────────────────────────────
                    function onStart(cx, cy) {
                        dragging = true;
                        hasDragged = false;
                        startX = cx;
                        startY = cy;
                        const r = el.getBoundingClientRect();
                        origLeft = r.left;
                        origBottom = window.innerHeight - r.bottom;
                        el.style.transition = 'none';
                        el.style.left = origLeft + 'px';
                        el.style.right = 'auto';
                        document.body.style.userSelect = 'none';
                    }

                    function onMove(cx, cy) {
                        if (!dragging) return;
                        const dx = cx - startX,
                            dy = cy - startY;
                        if (!hasDragged && (Math.abs(dx) > THRESH || Math.abs(dy) > THRESH)) {
                            hasDragged = true;
                        }
                        if (!hasDragged) return;
                        const newLeft = Math.max(0, Math.min(window.innerWidth - el.offsetWidth, origLeft + dx));
                        const newBottom = Math.max(0, Math.min(window.innerHeight - el.offsetHeight, origBottom - dy));
                        el.style.left = newLeft + 'px';
                        el.style.bottom = newBottom + 'px';
                    }

                    function onEnd() {
                        if (!dragging) return;
                        dragging = false;
                        document.body.style.userSelect = '';

                        if (!hasDragged) {
                            // Pure click → fire toggle
                            el.style.transition = '';
                            window.chatbotToggle();
                            return;
                        }

                        // Snap to nearest side
                        const r = el.getBoundingClientRect();
                        const centerX = r.left + el.offsetWidth / 2;
                        const side = centerX > window.innerWidth / 2 ? 'right' : 'left';
                        const bottom = window.innerHeight - r.bottom;
                        applySnap(side, bottom, true);
                    }

                    // Remove inline onclick — handled in onEnd
                    toggle.removeAttribute('onclick');

                    // Mouse
                    toggle.addEventListener('mousedown', e => {
                        e.preventDefault();
                        onStart(e.clientX, e.clientY);
                    });
                    document.addEventListener('mousemove', e => {
                        onMove(e.clientX, e.clientY);
                    });
                    document.addEventListener('mouseup', () => {
                        onEnd();
                    });

                    // Touch
                    toggle.addEventListener('touchstart', e => {
                        const t = e.touches[0];
                        onStart(t.clientX, t.clientY);
                    }, {
                        passive: true
                    });
                    document.addEventListener('touchmove', e => {
                        if (!dragging) return;
                        e.preventDefault();
                        const t = e.touches[0];
                        onMove(t.clientX, t.clientY);
                    }, {
                        passive: false
                    });
                    document.addEventListener('touchend', () => {
                        onEnd();
                    });
                })();
            </script>
        @endauth

        @auth
            <!-- ============================================================ -->
            <!-- PUSHER NOTIFICATIONS + TOGGLE (subscribe/unsubscribe)       -->
            <!-- ============================================================ -->
            <script>
                (function() {
                    // ── Constants ───────────────────────────────────────────────
                    // KEY: 'notif_enabled' — tab-specific via sessionStorage
                    // Default: ON (null → true), persists through refresh, clears on tab close
                    const NOTIF_KEY = 'notif_enabled';
                    const userRole = '{{ auth()->user()->role }}';
                    const userDeptId = '{{ auth()->user()->department_id ?? '' }}';
                    const adminRoles = [
                        'super_admin', 'admin', 'admin_logistic', 'admin_mascot', 'admin_costume',
                        'admin_animatronic', 'admin_finance', 'admin_hr', 'admin_procurement'
                    ];
                    const isAdmin = adminRoles.includes(userRole);

                    // ── Channel name registry ────────────────────────────────────
                    const channels = {
                        deptJobOrder: userDeptId ? `department.${userDeptId}.job-order-alerts` : null,
                        globalJobOrder: isAdmin ? 'job-order-alerts' : null,
                        deptGoodsOut: userDeptId ? `department.${userDeptId}.goods-out-alerts` : null,
                        globalGoodsOut: isAdmin ? 'goods-out-alerts' : null,
                    };

                    // ── Pusher init — exposed on window so other inline scripts can use it
                    window.pusher = new Pusher('{{ env('PUSHER_APP_KEY') }}', {
                        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
                        encrypted: true,
                    });

                    // ── State helpers ────────────────────────────────────────────
                    // Uses sessionStorage: tab-specific, survives refresh, reset on new tab
                    function isNotifEnabled() {
                        return sessionStorage.getItem(NOTIF_KEY) !== 'false';
                    }

                    // ── Sound + popup helpers ────────────────────────────────────
                    function playSound() {
                        const audio = new Audio('{{ asset('sounds/notification.mp3') }}');
                        audio.play().catch(() => {});
                    }

                    // ── Handlers ─────────────────────────────────────────────────
                    function handleJobOrderAlert(data) {
                        if (!isNotifEnabled()) return;
                        playSound();

                        const daysUntil = data.days_until_delivery || 0;
                        const isUrgent = daysUntil <= 1;
                        Swal.fire({
                            icon: isUrgent ? 'error' : 'warning',
                            title: isUrgent ? 'Urgent: Job Order Delivery!' : 'Job Order Delivery Warning',
                            html: `
                            <div class="text-start">
                                <p><strong>Job Order:</strong> ${data.job_order_name}</p>
                                <p><strong>Delivery Date:</strong> ${data.delivery_date}</p>
                                <p><strong>Time Left:</strong>
                                    <span class="badge bg-${isUrgent ? 'danger' : 'warning'}">${data.delivery_display}</span>
                                </p>
                                <p><strong>Project:</strong> ${data.project_name || '-'}</p>
                                <p><strong>Departments:</strong><br>
                                    ${(data.departments||[]).map(d => `<span class="badge bg-secondary me-1">${d}</span>`).join('')}
                                </p>
                                <p class="text-muted mb-0">${data.message}</p>
                            </div>`,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-eye me-1"></i> View Job Order',
                            cancelButtonText: 'Dismiss',
                            confirmButtonColor: isUrgent ? '#dc3545' : '#ffc107',
                            cancelButtonColor: '#6c757d',
                        }).then(r => {
                            if (r.isConfirmed) window.location.href = `/job-orders/${data.job_order_id}`;
                        });

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification(isUrgent ? 'Urgent: Job Order Delivery!' : 'Job Order Delivery Warning', {
                                body: `${data.job_order_name} — Delivery: ${data.delivery_date} (${data.delivery_display})`,
                                icon: '{{ asset('favicon.png') }}',
                                badge: '{{ asset('favicon.png') }}',
                                tag: `job-order-${data.job_order_id}`,
                                requireInteraction: isUrgent,
                            });
                        }
                    }

                    function handleGoodsOutAlert(data) {
                        if (!isNotifEnabled()) return;
                        playSound();

                        Swal.fire({
                            icon: 'info',
                            title: 'Material Issued',
                            html: `
                            <div class="text-start">
                                <p class="mb-2"><strong>Material:</strong> ${data.material_name}</p>
                                <p class="mb-2"><strong>Quantity:</strong> ${parseFloat(data.quantity).toFixed(2)} ${data.unit}</p>
                                <p class="mb-2"><strong>Project:</strong> ${data.project_name}</p>
                                ${data.job_order_name ? `<p class="mb-2"><strong>Job Order:</strong> ${data.job_order_name}</p>` : ''}
                                <p class="mb-2"><strong>Requested By:</strong> ${data.requested_by}</p>
                                <p class="mb-2"><strong>Department:</strong> ${data.department_name}</p>
                                <hr>
                                <p class="text-muted small mb-0">${data.timestamp}</p>
                            </div>`,
                            showCancelButton: true,
                            confirmButtonText: '<i class="fas fa-eye"></i> View Goods Out',
                            cancelButtonText: 'Close',
                            confirmButtonColor: '#0d6efd',
                            cancelButtonColor: '#6c757d',
                        }).then(r => {
                            if (r.isConfirmed && data.url) window.location.href = data.url;
                        });

                        if ('Notification' in window && Notification.permission === 'granted') {
                            new Notification('Material Issued', {
                                body: `${data.material_name} (${parseFloat(data.quantity).toFixed(2)} ${data.unit}) → ${data.project_name}`,
                                icon: '{{ asset('favicon.png') }}',
                                badge: '{{ asset('favicon.png') }}',
                                tag: `goods-out-${data.goods_out_id}`,
                            });
                        }
                    }

                    // ── Subscribe / Unsubscribe ──────────────────────────────────
                    function subscribeAll() {
                        if (channels.deptJobOrder) {
                            window.pusher.subscribe(channels.deptJobOrder)
                                .bind('job-order.delivery-alert', handleJobOrderAlert);
                        }
                        if (channels.globalJobOrder) {
                            window.pusher.subscribe(channels.globalJobOrder)
                                .bind('job-order.delivery-alert', handleJobOrderAlert);
                        }
                        if (channels.deptGoodsOut) {
                            window.pusher.subscribe(channels.deptGoodsOut)
                                .bind('goods-out.processed', handleGoodsOutAlert);
                        }
                        if (channels.globalGoodsOut) {
                            window.pusher.subscribe(channels.globalGoodsOut)
                                .bind('goods-out.processed', handleGoodsOutAlert);
                        }
                        console.log('[Notif] Subscribed to all channels');
                    }

                    function unsubscribeAll() {
                        Object.values(channels).forEach(ch => {
                            if (ch) {
                                try {
                                    window.pusher.unsubscribe(ch);
                                } catch (e) {}
                            }
                        });
                        console.log('[Notif] Unsubscribed from all channels');
                    }

                    // ── Initial subscription based on saved preference ───────────
                    if (isNotifEnabled()) {
                        subscribeAll();
                    }

                    // Request browser notification permission
                    if ('Notification' in window && Notification.permission === 'default') {
                        Notification.requestPermission();
                    }

                    // ── Toggle Button Logic ──────────────────────────────────────
                    function applyToggleUI(enabled) {
                        const icon = document.getElementById('notifBellIcon');
                        const btn = document.getElementById('notifToggleBtn');
                        if (!icon || !btn) return;
                        if (enabled) {
                            icon.className = 'bi bi-bell-fill';
                            btn.style.color = '';
                            btn.title = 'Notifications ON — click to mute';
                        } else {
                            icon.className = 'bi bi-bell-slash-fill';
                            btn.style.color = '#6c757d';
                            btn.title = 'Notifications OFF — click to unmute';
                        }
                    }

                    function showFeedbackToast(enabled) {
                        const el = document.createElement('div');
                        el.textContent = enabled ? '🔔 Notifications enabled' : '🔕 Notifications muted';
                        el.style.cssText = 'position:fixed;top:68px;right:16px;z-index:9999;' +
                            'background:#333;color:#fff;padding:6px 14px;border-radius:8px;' +
                            'font-size:.82rem;opacity:1;transition:opacity .5s;pointer-events:none;';
                        document.body.appendChild(el);
                        setTimeout(() => {
                            el.style.opacity = '0';
                        }, 1500);
                        setTimeout(() => {
                            el.remove();
                        }, 2100);
                    }

                    document.addEventListener('DOMContentLoaded', function() {
                        applyToggleUI(isNotifEnabled());

                        const btn = document.getElementById('notifToggleBtn');
                        if (btn) {
                            btn.addEventListener('click', function() {
                                const newState = !isNotifEnabled();
                                // sessionStorage: tab-specific, does not affect other tabs
                                sessionStorage.setItem(NOTIF_KEY, newState ? 'true' : 'false');

                                if (newState) {
                                    subscribeAll(); // re-subscribe Pusher channels when turning ON
                                } else {
                                    unsubscribeAll(); // unsubscribe Pusher channels when turning OFF
                                }

                                applyToggleUI(newState);
                                showFeedbackToast(newState);
                            });
                        }
                    });

                    // Expose for any other inline scripts that may need it
                    window.isNotifEnabled = isNotifEnabled;
                })
                ();
            </script>
            <!-- ============================================================ -->
            <!-- END PUSHER NOTIFICATIONS + TOGGLE                           -->
            <!-- ============================================================ -->
        @endauth

        <!-- ============================================================ -->
        <!-- END SYMCORE AI CHATBOT                                        -->
        <!-- ============================================================ -->

        <script>
        // HR nested dropdown: klik untuk toggle (mobile & desktop fallback)
        document.querySelectorAll('.hr-submenu-toggle').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var parent = this.closest('.dropdown-submenu');
                var isOpen = parent.classList.contains('open');
                document.querySelectorAll('.dropdown-submenu.open').forEach(function(s) { s.classList.remove('open'); });
                if (!isOpen) parent.classList.add('open');
            });
        });
        document.addEventListener('click', function() {
            document.querySelectorAll('.dropdown-submenu.open').forEach(function(s) { s.classList.remove('open'); });
        });
        </script>
    </body>

</html>
