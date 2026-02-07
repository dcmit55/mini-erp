

<?php $__env->startSection('content'); ?>
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-sm-0">
                        <i class="fas fa-briefcase gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Job Orders</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-sm-auto d-flex flex-wrap gap-2">
                        <a href="<?php echo e(route('job-orders.create')); ?>" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Job Order
                        </a>
                        <?php if(in_array(auth()->user()->role, ['super_admin', 'admin'])): ?>
                            <form action="<?php echo e(route('job-orders.sync.lark')); ?>" method="POST" class="d-inline"
                                id="syncLarkForm">
                                <?php echo csrf_field(); ?>
                                <button type="button" class="btn btn-info btn-sm flex-shrink-0" id="btnSyncLark"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                    title="Sync job orders from Lark Base">
                                    <i class="fas fa-sync me-1" id="syncIcon"></i>
                                    <span id="syncText">Sync from Lark</span>
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="#" id="export-btn" class="btn btn-outline-success btn-sm flex-shrink-0">
                            <i class="bi bi-file-earmark-excel me-1"></i> Export
                        </a>
                    </div>
                </div>

                <!-- Alerts -->
                <?php if(session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo session('success'); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo session('error'); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- filter form -->
                <div class="mb-3">
                    <form id="filter-form" class="row g-1">
                        <div class="col-md-3">
                            <select id="filter-project" name="project" class="form-select form-select-sm select2">
                                <option value="">All Projects</option>
                                <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($project->id); ?>"><?php echo e($project->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select id="filter-department" name="department" class="form-select form-select-sm select2">
                                <option value="">All Departments</option>
                                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $department): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($department->id); ?>"><?php echo e($department->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="text" id="custom-search" name="search" class="form-control form-control-sm"
                                placeholder="Search...">
                        </div>
                        <div class="col-md-3">
                            <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm"
                                title="Reset All Filters">
                                <i class="fas fa-times me-1"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-hover table-sm align-middle" id="datatable">
                    <thead class="table-light text-nowrap">
                        <tr>
                            <th style="display:none">ID</th>
                            <th>Name</th>
                            <th>Project</th>
                            <th>Department</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Description</th>
                            <th>Notes</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        #datatable td {
            vertical-align: middle;
        }

        .dataTables_wrapper {
            overflow: visible !important;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function() {
            // Initialize DataTable with Server-Side Processing
            const table = $('#datatable').DataTable({
                processing: false,
                serverSide: true,
                searching: false,
                stateSave: true,
                ajax: {
                    url: "<?php echo e(route('job-orders.index')); ?>",
                    data: function(d) {
                        d.project = $('#filter-project').val();
                        d.department = $('#filter-department').val();
                        d.custom_search = $('#custom-search').val();
                    },
                    error: function(xhr, error, thrown) {
                        console.error('DataTables AJAX Error:', xhr);
                        Swal.fire('Error', 'Failed to load data. Please refresh the page.', 'error');
                    }
                },
                columns: [{
                        data: 'id',
                        name: 'id',
                        visible: false
                    },

                    {
                        data: 'name',
                        name: 'name',
                        width: '15%'
                    },
                    {
                        data: 'project_name',
                        name: 'project.name',
                        width: '15%'
                    },
                    {
                        data: 'department_name',
                        name: 'department.name',
                        width: '12%'
                    },
                    {
                        data: 'start_date',
                        name: 'start_date',
                        width: '10%'
                    },
                    {
                        data: 'end_date',
                        name: 'end_date',
                        width: '10%'
                    },
                    {
                        data: 'description',
                        name: 'description',
                        width: '15%'
                    },
                    {
                        data: 'notes',
                        name: 'notes',
                        width: '10%'
                    },
                    {
                        data: 'actions',
                        name: 'actions',
                        orderable: false,
                        searchable: false,
                        width: '8%',
                        className: 'text-center'
                    }
                ],
                order: [
                    [1, 'desc']
                ],
                pageLength: 15,
                lengthMenu: [
                    [10, 15, 25, 50, 100],
                    [10, 15, 25, 50, 100]
                ],
                language: {
                    emptyTable: '<div class="text-muted py-2">No job orders available</div>',
                    zeroRecords: '<div class="text-muted py-2">No matching records found</div>',
                    infoEmpty: "Showing 0 to 0 of 0 entries",
                    infoFiltered: "(filtered from _MAX_ total entries)",
                    lengthMenu: "Show _MENU_ entries per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ entries",
                },
                dom: 't<' +
                    '"row datatables-footer-row align-items-center"' +
                    '<"col-md-7 d-flex align-items-center gap-2 datatables-left"l<"vr-divider mx-2">i>' +
                    '<"col-md-5 dataTables_paginate justify-content-end"p>' +
                    '>',
                responsive: true,
                stateSave: true,
                drawCallback: function() {
                    const container = this.api().table().container();
                    setTimeout(() => {
                        initializeTooltips(container);
                    }, 100);
                }
            });

            // Debounced Filter Functionality
            const debouncedFilter = debounce(function() {
                table.draw();
            }, 300);

            $('#filter-project, #filter-department, #custom-search')
                .on('change input', debouncedFilter);

            $('#reset-filters').on('click', function() {
                $('#filter-project, #filter-department, #custom-search')
                    .val('').trigger('change');
                table.draw();
            });

            // Export button handler
            $('#export-btn').on('click', function(e) {
                e.preventDefault();
                const params = {
                    project: $('#filter-project').val(),
                    department: $('#filter-department').val(),
                    search: $('#custom-search').val()
                };
                const query = $.param(params);
                window.location.href = '<?php echo e(route('job-orders.export')); ?>' + '?' + query;
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            $('#filter-project').attr('data-placeholder', 'All Projects');
            $('#filter-department').attr('data-placeholder', 'All Departments');

            // Delete Confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');

                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            // Sync from Lark button handler
            const syncBtn = document.getElementById('btnSyncLark');
            const syncForm = document.getElementById('syncLarkForm');

            if (syncBtn && syncForm) {
                syncBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (confirm(
                            'Sync all job orders from Lark?\n\nThis will:\n- Fetch latest data from Lark Base\n- Create/update job orders\n- Mark with "Sync from Lark"\n- Deactivate missing job orders\n\nContinue?'
                        )) {
                        // Show loading state
                        const syncIcon = document.getElementById('syncIcon');
                        const syncText = document.getElementById('syncText');

                        syncBtn.disabled = true;
                        syncIcon.classList.add('fa-spin');
                        syncText.textContent = 'Syncing...';
                        syncBtn.classList.remove('btn-info');
                        syncBtn.classList.add('btn-secondary');

                        // Submit form
                        syncForm.submit();
                    }
                });
            }
        });

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function initializeTooltips(container = document) {
            $(container).find('[data-bs-toggle="tooltip"]').tooltip('dispose');
            const tooltipElements = container.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipElements.forEach(element => {
                new bootstrap.Tooltip(element, {
                    trigger: 'hover focus',
                    placement: 'top',
                    boundary: 'viewport',
                    container: 'body'
                });
            });
        }

        document.addEventListener("DOMContentLoaded", function() {
            initializeTooltips();
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/production/job-orders/index.blade.php ENDPATH**/ ?>