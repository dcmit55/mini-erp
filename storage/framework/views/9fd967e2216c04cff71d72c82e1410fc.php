

<?php $__env->startSection('content'); ?>
    <div class="container-fluid mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <div class="d-flex align-items-center mb-2 mb-lg-0">
                        <i class="fas fa-project-diagram gradient-icon me-2" style="font-size: 1.5rem;"></i>
                        <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Projects</h2>
                    </div>

                    <!-- Spacer untuk mendorong tombol ke kanan -->
                    <div class="ms-lg-auto d-flex flex-wrap gap-2">
                        <a href="<?php echo e(route('projects.create')); ?>" class="btn btn-primary btn-sm flex-shrink-0">
                            <i class="bi bi-plus-circle me-1"></i> Create Project
                        </a>
                        <?php if(in_array(auth()->user()->role, ['super_admin', 'admin'])): ?>
                            <form action="<?php echo e(route('projects.sync.lark')); ?>" method="POST" class="d-inline"
                                id="syncLarkForm">
                                <?php echo csrf_field(); ?>
                                <button type="button" class="btn btn-info btn-sm flex-shrink-0" id="btnSyncLark"
                                    data-bs-toggle="tooltip" data-bs-placement="bottom"
                                    title="Sync projects from Lark Base">
                                    <i class="fas fa-sync me-1" id="syncIcon"></i>
                                    <span id="syncText">Sync from Lark</span>
                                </button>
                            </form>
                        <?php endif; ?>
                        <a href="<?php echo e(route('projects.export', request()->query())); ?>"
                            class="btn btn-outline-success btn-sm flex-shrink-0">
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

                <?php if(session('warning')): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <?php echo session('warning'); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <form id="filter-form" method="GET" action="<?php echo e(route('projects.index')); ?>" class="row g-2">
                        <div class="col-lg-2">
                            <select id="filter-quantity" name="quantity" class="form-select select2">
                                <option value="">All Quantity</option>
                                <?php $__currentLoopData = $allQuantities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qty): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($qty); ?>" <?php echo e(request('quantity') == $qty ? 'selected' : ''); ?>>
                                        <?php echo e($qty); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-department" name="department" class="form-select select2">
                                <option value="">All Department</option>
                                <?php $__currentLoopData = $departments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $dept): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($dept->name); ?>"
                                        <?php echo e(request('department') == $dept->name ? 'selected' : ''); ?>>
                                        <?php echo e($dept->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <select id="filter-status" name="status" class="form-select select2">
                                <option value="">All Status</option>
                                <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($status->id); ?>"
                                        <?php echo e(request('status') == $status->id ? 'selected' : ''); ?>>
                                        <?php echo e($status->name); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-lg-2 align-self-end">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="<?php echo e(route('projects.index')); ?>" class="btn btn-secondary"
                                title="Reset All Filters">Reset</a>
                        </div>
                    </form>
                </div>

                <!-- Table -->
                <table class="table table-sm table-hover align-middle" id="datatable">
                    <thead class="table-light align-middle">
                        <tr>
                            <th>No</th>
                            <th>Name</th>
                            <th>Sales</th>
                            <th>Department</th>
                            <th class="text-center">Quantity</th>
                            <th>Deadline</th>
                            <th>Status</th>
                            <th>Stage</th>
                            <th>Project Status</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $projects; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $project): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td class="text-center"><?php echo e($loop->iteration); ?></td>
                                <td><?php echo e($project->name); ?></td>
                                <td>
                                    <?php if($project->sales): ?>
                                        <span class="badge bg-info" style="font-weight:500;">
                                            <?php echo e($project->sales); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($project->department): ?>
                                        <?php echo $__env->make('components.department-badge', [
                                            'department' => $project->department->name,
                                        ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?php echo e($project->qty === null ? '-' : $project->qty); ?></td>
                                <td><?php echo e($project->deadline ? \Carbon\Carbon::parse($project->deadline)->translatedFormat('d F Y') : '-'); ?>

                                </td>
                                <td>
                                    <?php if($project->status): ?>
                                        <span class="badge <?php echo e($project->status->badgeClass()); ?>" style="font-weight:500;">
                                            <?php echo e($project->status->name); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($project->stage ?? '-'); ?></td>
                                <td>
                                    <?php echo $__env->make('components.project-status-badges', [
                                        'statuses' => $project->project_status,
                                    ], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                                </td>
                                <td>
                                    <span class="badge bg-secondary"><?php echo e($project->created_by ?? '-'); ?></span>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        <a href="<?php echo e(route('projects.edit', $project)); ?>" class="btn btn-sm btn-warning"
                                            data-bs-toggle="tooltip" data-bs-placement="bottom" title="Edit"><i
                                                class="bi bi-pencil-square"></i></a>
                                        <?php if(auth()->user()->username === $project->created_by || auth()->user()->role === 'super_admin'): ?>
                                            <form action="<?php echo e(route('projects.destroy', $project)); ?>" method="POST"
                                                class="delete-form">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('DELETE'); ?>
                                                <button type="button" class="btn btn-sm btn-danger btn-delete"
                                                    data-bs-toggle="tooltip" data-bs-placement="bottom" title="Delete"><i
                                                        class="bi bi-trash3"></i></button>
                                            </form>
                                        <?php endif; ?>
                                        <button type="button" class="btn btn-info btn-sm btn-show-image"
                                            title="View Image"
                                            data-img="<?php echo e($project->img ? asset('storage/' . $project->img) : ''); ?>"
                                            data-name="<?php echo e($project->name); ?>">
                                            <i class="bi bi-file-earmark-image"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('styles'); ?>
    <style>
        .artisan-action {
            transition: all 0.3s ease;
        }

        .artisan-action:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .btn:disabled {
            cursor: not-allowed !important;
        }
    </style>
<?php $__env->stopPush(); ?>
<?php $__env->startPush('scripts'); ?>
    <script>
        $(document).ready(function() {
            $('#datatable').DataTable({
                responsive: true,
                stateSave: true,
            });

            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true
            });

            // SweetAlert for delete confirmation
            $(document).on('click', '.btn-delete', function(e) {
                e.preventDefault();
                let form = $(this).closest('form');
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, delete it!',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.btn-show-image', function() {
                const imgSrc = $(this).data('img'); // Ambil URL gambar
                const imgName = $(this).data('name'); // Ambil nama gambar

                if (imgSrc) {
                    // Buat elemen Fancybox secara dinamis
                    Fancybox.show([{
                        src: imgSrc,
                        type: "image",
                        caption: imgName, // Tambahkan caption
                        downloadSrc: imgSrc, // URL untuk tombol download
                    }], {
                        Toolbar: {
                            display: [
                                "zoom", // Tombol zoom
                                "fullscreen", // Tombol fullscreen
                                "download", // Tombol download
                                "close", // Tombol close
                            ],
                        },
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Oops...',
                        text: 'No image available!',
                    });
                }
            });
        });

        // Artisan Actions
        function initializeArtisanActions() {
            document.querySelectorAll('.artisan-action').forEach(button => {
                button.addEventListener('click', function() {
                    const action = this.dataset.action;

                    Swal.fire({
                        title: 'Processing...',
                        text: `Executing ${action}...`,
                        icon: 'info',
                        scrollbarPadding: false,
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    fetch(`/artisan/${action}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                Swal.fire({
                                    title: 'Success!',
                                    text: data.message,
                                    icon: 'success',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Reload halaman setelah sync berhasil
                                    if (action === 'lark:fetch-job-orders') {
                                        location.reload();
                                    }
                                });
                            } else {
                                Swal.fire({
                                    title: 'Error!',
                                    text: data.message,
                                    icon: 'error',
                                    confirmButtonText: 'OK'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                title: 'Error!',
                                text: 'An error occurred. Please try again.',
                                icon: 'error',
                                confirmButtonText: 'OK'
                            });
                            console.error('Error:', error);
                        });
                });
            });
        }

        // Initialize saat DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            initializeArtisanActions();

            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.forEach(function(tooltipTriggerEl) {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Sync from Lark button handler
            const syncBtn = document.getElementById('btnSyncLark');
            const syncForm = document.getElementById('syncLarkForm');

            if (syncBtn && syncForm) {
                syncBtn.addEventListener('click', function(e) {
                    e.preventDefault();

                    if (confirm(
                            'Sync all projects from Lark?\n\nThis will:\n- Fetch latest data from Lark Base\n- Create/update projects\n- Mark with "Sync from Lark"\n- Deactivate missing projects\n\nContinue?'
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
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\27JAN\resources\views/production/projects/index.blade.php ENDPATH**/ ?>