<!doctype html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="icon" type="image/png" href="<?php echo e(asset('favicon.png')); ?>">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Laravel')); ?></title>

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

    <link rel="stylesheet" href="<?php echo e(asset('css/custom-app.css')); ?>">
    <?php echo $__env->yieldPushContent('styles'); ?>
</head>

<body>
    <div id="app">
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="<?php echo e(url('/')); ?>">
                    <?php echo e(config('app.name', 'DCM-app')); ?>

                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        <?php if(auth()->check()): ?>
                            <!-- Dashboard -->
                            <li class="nav-item">
                                <a class="nav-link <?php echo e(request()->is('dashboard*') ? 'active' : ''); ?>"
                                    href="<?php echo e(route('dashboard')); ?>">
                                    <i></i>Dashboard
                                </a>
                            </li>

                            <?php
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
                            ?>

                            <!-- Projects Menu -->
                            <?php if(in_array(auth()->user()->role, [
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
                                ])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(request()->is('projects*') || request()->is('internal-projects*') ? 'active' : ''); ?>"
                                        href="#" id="projectsDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Projects
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="projectsDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('projects*') && !request()->is('internal-projects*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('projects.index')); ?>">
                                                <i class="fas fa-building me-2"></i>Client Projects
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('internal-projects*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('internal-projects.index')); ?>">
                                                <i class="fas fa-cogs me-2"></i>Internal Projects
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <!-- Logistics Dropdown -->
                            <?php if(in_array(auth()->user()->role, [
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
                                ])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(isDropdownActive($logisticsPrefixes) ? 'active' : ''); ?>"
                                        href="#" id="logisticsDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Logistics
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="logisticsDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('inventory*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('inventory.index')); ?>">
                                                <i class="fas fa-boxes me-2"></i>Inventory Listing
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('suppliers*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('suppliers.index')); ?>">
                                                <i class="fas fa-truck me-2"></i>Suppliers
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('material_requests*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('material_requests.index')); ?>">
                                                <i class="fas fa-clipboard-list me-2"></i>Material Request
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('goods_out*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('goods_out.index')); ?>">
                                                <i class="fas fa-arrow-right me-2"></i>Goods Out
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('goods_in*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('goods_in.index')); ?>">
                                                <i class="fas fa-arrow-left me-2"></i>Goods In
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('material_usage*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('material_usage.index')); ?>">
                                                <i class="fas fa-balance-scale me-2"></i>Material Usage
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('goods-movement*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('goods-movement.index')); ?>">
                                                <i class="fas fa-exchange-alt me-2"></i>Goods Movement
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <!-- Procurement Dropdown -->
                            <?php if(in_array(auth()->user()->role, [
                                    'super_admin',
                                    'admin_procurement',
                                    'admin_hr',
                                    'admin',
                                    'admin_logistic',
                                    'admin_finance',
                                ])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(isDropdownActive($procurementPrefixes) ? 'active' : ''); ?>"
                                        href="#" id="procurementDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Procurement
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="procurementDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('project-purchases*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('project-purchases.index')); ?>">
                                                <i class="fas fa-file-invoice me-2"></i>Indo Purchase
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('suppliers*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('suppliers.index')); ?>">
                                                <i class="fas fa-truck me-2"></i>Suppliers
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('purchase_requests*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('purchase_requests.index')); ?>">
                                                <i class="fas fa-clipboard-check me-2"></i>Purchase Request
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('pre-shippings*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('pre-shippings.index')); ?>">
                                                <i class="fas fa-shipping-fast me-2"></i>Pre Shippings
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('shipping-management*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('shipping-management.index')); ?>">
                                                <i class="fas fa-ship me-2"></i>Shipping Management
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('goods-receive*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('goods-receive.index')); ?>">
                                                <i class="fas fa-box-open me-2"></i>Goods Receive
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <!-- Productions Dropdown -->
                            <?php if(in_array(auth()->user()->role, [
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
                                ])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(request()->is('job-orders*') || request()->is('timings*') || request()->is('employees/*/timing*') || request()->is('material-planning*') ? 'active' : ''); ?>"
                                        href="#" id="productionsDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Productions
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="productionsDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('job-orders*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('job-orders.index')); ?>">
                                                <i class="fas fa-tasks me-2"></i>Job Order
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('material_requests*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('material_requests.index')); ?>">
                                                <i class="fas fa-clipboard-list me-2"></i>Material Request
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('material-usage*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('material_usage.index')); ?>">
                                                <i class="fas fa-balance-scale me-2"></i>Material Usage
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('material-planning*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('material_planning.index')); ?>">
                                                <i class="fas fa-calendar-alt me-2"></i>Material Planning
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('timings*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('timings.index')); ?>">
                                                <i class="fas fa-clock me-2"></i>Timing
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <!-- Finances Dropdown -->
                            <?php if(in_array(auth()->user()->role, [
                                    'super_admin',
                                    'admin_finance',
                                    'admin',
                                    'admin_logistic',
                                    'admin_procurement',
                                ])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(request()->is('currencies*') || 
                                        request()->is('costing-report*') || 
                                        request()->is('final_project_summary*') ||
                                        request()->is('dcm-costings*') ||
                                        request()->is('purchase-approvals*')
                                        ? 'active' : ''); ?>" 
                                        href="#" id="financesDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Finances
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="financesDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('currencies*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('currencies.index')); ?>">
                                                <i class="fas fa-money-bill me-2"></i>Currency
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('costing-report*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('costing.report')); ?>">
                                                <i class="fas fa-chart-line me-2"></i>Costing Report
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('final_project_summary*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('final_project_summary.index')); ?>">
                                                <i class="fas fa-file-contract me-2"></i>Final Project Summary
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('dcm-costings*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('dcm-costings.index')); ?>">
                                                <i class="fas fa-file-invoice-dollar me-2"></i>DCM Costing
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('purchase-approvals*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('purchase-approvals.index')); ?>">
                                                <i class="fas fa-clipboard-check me-2"></i>Purchase Approvals
                                            </a>
                                        </li>
                                    </ul>
                                </li>
                            <?php endif; ?>

                            <!-- HR Dropdown -->
                            <?php if(auth()->guard()->check()): ?>
                                <?php if(in_array(auth()->user()->role, ['super_admin', 'admin_hr', 'admin'])): ?>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle <?php echo e(request()->is('employees*') || request()->routeIs('leave_requests.index') || request()->is('attendance*') ? 'active' : ''); ?>"
                                            href="#" id="hrDropdown" role="button" data-bs-toggle="dropdown"
                                            aria-expanded="false">
                                            <i></i>HR
                                        </a>
                                        <ul class="dropdown-menu" aria-labelledby="hrDropdown">
                                            <li>
                                                <a class="dropdown-item <?php echo e(request()->is('employees*') ? 'active' : ''); ?>"
                                                    href="<?php echo e(route('employees.index')); ?>">
                                                    <i class="fas fa-user-tie me-2"></i>Employees
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo e(request()->routeIs('leave_requests.index') ? 'active' : ''); ?>"
                                                    href="<?php echo e(route('leave_requests.index')); ?>">
                                                    <i class="fas fa-calendar-minus me-2"></i>Leave Requests
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo e(request()->is('attendance*') ? 'active' : ''); ?>"
                                                    href="<?php echo e(route('attendance.index')); ?>">
                                                    <i class="fas fa-calendar-day me-2"></i>Daily Attendance
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo e(request()->is('attendance/list*') ? 'active' : ''); ?>"
                                                    href="<?php echo e(route('attendance.list')); ?>">
                                                    <i class="fas fa-history me-2"></i>Attendance History
                                                </a>
                                            </li>
                                        </ul>
                                    </li>
                                <?php endif; ?>
                            <?php endif; ?>

                            
                            <?php if(auth()->guard()->guest()): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo e(request()->routeIs('leave_requests.index') ? 'active' : ''); ?>"
                                        href="<?php echo e(route('leave_requests.index')); ?>">
                                        <i class="fas fa-calendar-minus me-2"></i>Leave Request
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Admin Dropdown -->
                            <?php if(in_array(auth()->user()->role, ['super_admin', 'admin'])): ?>
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle <?php echo e(request()->is('users*') || request()->routeIs('trash.index') || request()->is('audit*') ? 'active' : ''); ?>"
                                        href="#" id="adminDropdown" role="button"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i></i>Admin
                                    </a>
                                    <ul class="dropdown-menu" aria-labelledby="adminDropdown">
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->is('users*') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('users.index')); ?>">
                                                <i class="fas fa-user-cog me-2"></i>Users
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item <?php echo e(request()->routeIs('trash.index') ? 'active' : ''); ?>"
                                                href="<?php echo e(route('trash.index')); ?>">
                                                <i class="fas fa-trash me-2"></i>Trash
                                            </a>
                                        </li>
                                        <!-- Audit Log (only for super_admin) -->
                                        <?php if(Auth::user()->isSuperAdmin()): ?>
                                            <li>
                                                <hr class="dropdown-divider">
                                            </li>
                                            <li>
                                                <a class="dropdown-item <?php echo e(request()->is('audit*') ? 'active' : ''); ?>"
                                                    href="<?php echo e(route('audit.index')); ?>">
                                                    <i class="fas fa-clipboard-list me-2"></i>Audit Log
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </li>
                            <?php endif; ?>
                        <?php endif; ?>
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        <?php if(auth()->guard()->guest()): ?>
                        <?php else: ?>
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link btn dropdown-toggle" href="#"
                                    role="button" data-bs-toggle="dropdown" aria-haspopup="true"
                                    aria-expanded="false">
                                    <i class="fas fa-user me-2"></i><?php echo e(ucfirst(Auth::user()->username)); ?>

                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="<?php echo e(route('logout')); ?>"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                                    </a>
                                    <form id="logout-form" action="<?php echo e(route('logout')); ?>" method="POST"
                                        class="d-none">
                                        <?php echo csrf_field(); ?>
                                    </form>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <?php echo $__env->yieldContent('content'); ?>
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
        <audio id="notification-sound" src="<?php echo e(asset('sounds/notification.mp3')); ?>" preload="auto"></audio>
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

    <script src="<?php echo e(mix('js/app.js')); ?>"></script>

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
        const authUserRole = "<?php echo e(auth()->check() ? auth()->user()->role : ''); ?>";
    </script>

    <script src="<?php echo e(asset('js/custom-app.js')); ?>"></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>

    <footer class="bg-light text-center text-lg-start mt-5">
        <div class="container-fluid">
            <div class="row">
                <!-- About Section -->
                <div class="bg-white col-lg-12 mb-2 mb-lg-0">
                    <h5 class="text-uppercase mt-2">About:</h5>
                    <p class="mb-3">
                        This is an inventory management system designed to streamline your operations and improve
                        efficiency.
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center p-1 bg-dark text-secondary">
            © <?php echo e(date('Y')); ?> <?php echo e(config('app.name', 'DCM-app')); ?>. Created with <i
                class="fas fa-heart text-danger"></i> by IT Team (Gen 1)
        </div>
    </footer>
</body>

</html><?php /**PATH D:\27JAN\resources\views/layouts/app.blade.php ENDPATH**/ ?>