<script>
    $(document).ready(function() {
        // Initialize DataTable dengan server-side processing
        const table = $('#auditTable').DataTable({
            processing: false,
            serverSide: true,
            searching: false, // Disable default search, gunakan custom
            ajax: {
                url: "{{ route('audit.index') }}",
                data: function(d) {
                    // Add filter parameters
                    d.event = $('#eventFilter').val();
                    d.auditable_type = $('#modelFilter').val();
                    d.date_from = $('#dateFrom').val();
                    d.date_to = $('#dateTo').val();
                    d.custom_search = $('#custom-search').val();
                }
            },
            columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'formatted_date',
                    name: 'created_at'
                },
                {
                    data: 'user_name',
                    name: 'user.username'
                },
                {
                    data: 'model_name',
                    name: 'auditable_type'
                },
                {
                    data: 'event_badge',
                    name: 'event'
                },
                {
                    data: 'changes',
                    name: 'changes',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'ip_address',
                    name: 'ip_address'
                },
                {
                    data: 'actions',
                    name: 'actions',
                    orderable: false,
                    searchable: false
                }
            ],
            order: [
                []
            ],
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],
            language: {
                emptyTable: '<div class="text-muted py-2">No audit logs available</div>',
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
            stateSave: false,
            drawCallback: function() {
                $('[data-bs-toggle="tooltip"]').tooltip();
            }
        });

        // Filter functionality
        $('#eventFilter, #modelFilter').on('change', function() {
            table.ajax.reload();
        });

        $('#dateFrom, #dateTo').on('change', function() {
            table.ajax.reload();
        });

        $('#custom-search').on('input', debounce(function() {
            table.ajax.reload();
        }, 500));

        // Reset filters
        $('#reset-filters').on('click', function() {
            $('#eventFilter, #modelFilter').val('').trigger('change');
            $('#dateFrom, #dateTo').val('');
            $('#custom-search').val('');
            table.ajax.reload();
        });

        // Debounce function
        function debounce(func, wait) {
            let timeout;
            return function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, arguments), wait);
            };
        }

        // Select All Checkbox
        $(document).on('click', '#selectAllCheckbox', function() {
            const isChecked = $(this).is(':checked');
            $('.select-audit').prop('checked', isChecked);
            updateBulkDeleteBtn();
        });

        // Individual Checkbox
        $(document).on('change', '.select-audit', function() {
            updateBulkDeleteBtn();
        });

        function updateBulkDeleteBtn() {
            const selectedCount = $('.select-audit:checked').length;
            if (selectedCount > 0) {
                $('#bulkDeleteBtn').prop('disabled', false).html(
                    `<i class="bi bi-trash3 me-1"></i> Bulk Delete (${selectedCount})`
                );
            } else {
                $('#bulkDeleteBtn').prop('disabled', true).html(
                    '<i class="bi bi-trash3 me-1"></i> Bulk Delete'
                );
            }
        }

        // Bulk Delete
        $('#bulkDeleteBtn').on('click', function() {
            const selectedIds = $('.select-audit:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedIds.length === 0) {
                Swal.fire('Warning', 'Please select at least one audit log.', 'warning');
                return;
            }

            Swal.fire({
                title: 'Delete Selected Audit Logs?',
                html: `You are about to permanently delete <strong>${selectedIds.length}</strong> audit log(s).`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('audit.bulkDelete') }}",
                        method: 'POST',
                        data: {
                            ids: selectedIds,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                            $('#selectAllCheckbox').prop('checked', false);
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to delete audit logs.', 'error');
                        }
                    });
                }
            });
        });

        // Individual Delete
        $(document).on('click', '.delete-audit-btn', function() {
            const auditId = $(this).data('id');

            Swal.fire({
                title: 'Delete Audit Log?',
                text: 'This audit log will be permanently deleted.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ url('audit') }}/" + auditId,
                        method: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to delete audit log.', 'error');
                        }
                    });
                }
            });
        });

        // Delete by Date Range
        $('#confirmDeleteByDateBtn').on('click', function() {
            const formData = $('#deleteByDateForm').serialize();

            if (!$('#deleteDateFrom').val() || !$('#deleteDateTo').val()) {
                Swal.fire('Error', 'Please fill in both date fields.', 'error');
                return;
            }

            Swal.fire({
                title: 'Delete Audit Logs by Date Range?',
                html: `<strong>${$('#deleteDateFrom').val()}</strong> to <strong>${$('#deleteDateTo').val()}</strong><br>All matching audit logs will be permanently deleted.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete them!',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('audit.deleteByDateRange') }}",
                        method: 'POST',
                        data: formData,
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            $('#deleteByDateModal').modal('hide');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to delete audit logs.', 'error');
                        }
                    });
                }
            });
        });

        // Purge Old Logs
        $('#confirmPurgeBtn').on('click', function() {
            const days = $('#purgeDays').val();

            Swal.fire({
                title: 'Purge Old Audit Logs?',
                html: `Logs older than <strong>${days} days</strong> will be permanently deleted. This cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, purge them!',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "{{ route('audit.purgeOldLogs') }}",
                        method: 'POST',
                        data: {
                            days: days,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire('Success', response.message, 'success');
                            $('#purgeOldModal').modal('hide');
                            table.ajax.reload();
                        },
                        error: function(xhr) {
                            Swal.fire('Error', xhr.responseJSON?.message ||
                                'Failed to purge audit logs.', 'error');
                        }
                    });
                }
            });
        });

        // Initialize Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            placeholder: function() {
                return $(this).data('placeholder');
            },
            allowClear: true
        });
    });

    // Global Functions
    function showChanges(auditId) {
        $('#changesContent').html(`
            <div class="text-center">
                <div class="spinner-border audit-spinner text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        `);

        $.get(`{{ url('audit/changes') }}/${auditId}`)
            .done(function(data) {
                const eventBadgeClasses = {
                    created: 'badge-created',
                    updated: 'badge-updated',
                    deleted: 'badge-deleted',
                    restored: 'badge-restored'
                };
                const badgeClass = eventBadgeClasses[data.event] || 'bg-secondary';

                let html = `
                    <div class="mb-3">
                        <strong>Model:</strong> ${data.model}<br>
                        <strong>Event:</strong> <span class="badge ${badgeClass}">${data.event}</span><br>
                        <strong>Date:</strong> ${data.created_at}<br>
                        <strong>User:</strong> ${data.user_name || 'System'}
                    </div>
                `;

                if (data.event === 'created') {
                    html += '<h6>New Values:</h6>';
                    html += formatValues(data.new_values);
                } else if (data.event === 'updated') {
                    html += '<div class="row">';
                    html += '<div class="col-md-6"><h6>Old Values:</h6>' + formatValues(data.old_values) + '</div>';
                    html += '<div class="col-md-6"><h6>New Values:</h6>' + formatValues(data.new_values) + '</div>';
                    html += '</div>';
                } else if (data.event === 'deleted') {
                    html += '<h6>Deleted Values:</h6>';
                    html += formatValues(data.old_values);
                } else if (data.event === 'restored') {
                    html += '<h6>Restored Values:</h6>';
                    html += formatValues(data.new_values);
                }

                $('#changesContent').html(html);
            })
            .fail(function() {
                $('#changesContent').html('<div class="alert alert-danger">Failed to load changes.</div>');
            });
    }

    function formatValues(values) {
        if (!values || Object.keys(values).length === 0) {
            return '<p class="text-muted">No data</p>';
        }

        let html = '<table class="table table-sm table-bordered">';
        Object.keys(values).forEach(key => {
            const value = values[key];
            const formattedValue = (value === null || value === '') 
                ? '<em class="text-muted">null</em>' 
                : escapeHtml(value.toString());
            html += `<tr><td><strong>${key}:</strong></td><td>${formattedValue}</td></tr>`;
        });
        html += '</table>';
        return html;
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
</script>