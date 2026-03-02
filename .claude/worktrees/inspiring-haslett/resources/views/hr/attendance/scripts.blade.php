<script>
    $(document).ready(function() {
        // Global Variables
        const currentDate = '{{ $date }}';
        let selectedEmployeeIds = [];
        let isUpdating = false;

        // Initialize Clock
        function initializeClock() {
            setInterval(function() {
                const now = new Date();
                const timeString = now.toLocaleTimeString('en-US', {
                    hour12: false,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit'
                });
                $('#current-time').text(timeString);
            }, 1000);
        }

        // Show Toast Notification
        function showToast(message, type = 'info') {
            const toast = $('#toast');
            const toastBody = toast.find('.toast-body');
            const toastHeader = toast.find('.toast-header');

            toastBody.text(message);
            toastHeader.removeClass('bg-success bg-danger bg-warning bg-info text-white text-dark');

            switch(type) {
                case 'success':
                    toastHeader.addClass('bg-success text-white');
                    break;
                case 'error':
                    toastHeader.addClass('bg-danger text-white');
                    break;
                case 'warning':
                    toastHeader.addClass('bg-warning text-dark');
                    break;
                case 'info':
                default:
                    toastHeader.addClass('bg-info text-white');
            }

            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
        }

        // Handle Checkbox Selection
        function initializeCheckboxes() {
            // Select All Checkbox
            $('#select-all').on('change', function() {
                const isChecked = $(this).is(':checked');
                $('.employee-checkbox').prop('checked', isChecked);
                updateSelectedCount();
                toggleEmployeeSelection();
            });

            // Individual Checkboxes
            $(document).on('change', '.employee-checkbox', function() {
                updateSelectedCount();
                toggleEmployeeSelection();
            });
        }

        // Update Selected Count
        function updateSelectedCount() {
            const count = $('.employee-checkbox:checked').length;
            $('#selected-count').text(count);
            selectedEmployeeIds = $('.employee-checkbox:checked').map(function() {
                return $(this).val();
            }).get();
        }

        // Toggle Employee Selection Style
        function toggleEmployeeSelection() {
            $('.employee-card').removeClass('selected');
            $('.employee-checkbox:checked').each(function() {
                $(this).closest('.employee-card').addClass('selected');
            });
        }

        // Handle Status Button Click
        function initializeStatusButtons() {
            $(document).on('click', '.status-btn:not([onclick])', function() {
                if (isUpdating) return;

                const button = $(this);
                const employeeId = button.data('employee-id');
                const status = button.data('status');

                if (status === 'late') {
                    openLateModal(employeeId);
                } else {
                    updateAttendance(employeeId, status, button);
                }
            });
        }

        // Open Late Modal
        function openLateModal(employeeId) {
            $('#late-employee-id').val(employeeId);
            $('#late-time').val('');
            $('#lateModal').modal('show');
        }

        // Handle Late Form Submission
        function initializeLateForm() {
            $('#lateForm').on('submit', function(e) {
                e.preventDefault();
                
                const employeeId = $('#late-employee-id').val();
                const lateTime = $('#late-time').val();

                if (!lateTime) {
                    $('#late-time').addClass('is-invalid');
                    showToast('Please enter late time', 'warning');
                    return;
                }

                updateAttendance(employeeId, 'late', null, lateTime);
                $('#lateModal').modal('hide');
            });
        }

        // Handle Bulk Actions
        function initializeBulkActions() {
            // Bulk Present
            $('#btn-bulk-present').on('click', function() {
                if (selectedEmployeeIds.length === 0) {
                    showToast('Please select at least one employee', 'warning');
                    return;
                }

                if (confirm(`Mark ${selectedEmployeeIds.length} employee(s) as present?`)) {
                    bulkUpdateAttendance(selectedEmployeeIds, 'present');
                }
            });

            // Bulk Absent
            $('#btn-bulk-absent').on('click', function() {
                if (selectedEmployeeIds.length === 0) {
                    showToast('Please select at least one employee', 'warning');
                    return;
                }

                if (confirm(`Mark ${selectedEmployeeIds.length} employee(s) as absent?`)) {
                    bulkUpdateAttendance(selectedEmployeeIds, 'absent');
                }
            });

            // Bulk Late
            $('#btn-bulk-late').on('click', function() {
                if (selectedEmployeeIds.length === 0) {
                    showToast('Please select at least one employee', 'warning');
                    return;
                }

                generateBulkLateInputs(selectedEmployeeIds);
                $('#bulk-late-modal').modal('show');
            });
        }

        // Generate Bulk Late Inputs
        function generateBulkLateInputs(employeeIds) {
            const container = $('#bulk-late-employee-inputs');
            let html = '';

            employeeIds.forEach((empId, index) => {
                const empCard = $(`.employee-card[data-employee-id="${empId}"]`);
                const empName = empCard.find('.fw-bold').text() || `Employee ${empId}`;

                html += `
                    <div class="col-md-6">
                        <div class="bulk-late-card">
                            <div class="card-body p-3">
                                <label class="form-label small mb-2">
                                    <strong>${empName}</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-clock"></i>
                                    </span>
                                    <input type="time"
                                        class="form-control late-time-input"
                                        name="late_time[${empId}]"
                                        data-employee-id="${empId}"
                                        required>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });

            container.html(html);
            $('#bulk-late-employee-count').text(employeeIds.length);
        }

        // Handle Bulk Late Form Submission
        function initializeBulkLateForm() {
            $('#bulk-late-form').on('submit', function(e) {
                e.preventDefault();

                const lateTimeInputs = {};
                let allFilled = true;

                $('.late-time-input').each(function() {
                    const empId = $(this).data('employee-id');
                    const time = $(this).val();

                    if (!time) {
                        $(this).addClass('is-invalid');
                        allFilled = false;
                    } else {
                        $(this).removeClass('is-invalid');
                        lateTimeInputs[empId] = time;
                    }
                });

                if (!allFilled) {
                    showToast('Please enter late time for all employees', 'warning');
                    return;
                }

                if (confirm('Mark selected employees as late?')) {
                    bulkUpdateAttendanceWithIndividualTimes(selectedEmployeeIds, lateTimeInputs);
                    $('#bulk-late-modal').modal('hide');
                }
            });
        }

        // Update Single Attendance
        function updateAttendance(employeeId, status, button = null, lateTime = null) {
            if (isUpdating) return;
            isUpdating = true;

            const data = {
                _token: '{{ csrf_token() }}',
                employee_id: employeeId,
                date: currentDate,
                status: status
            };

            if (lateTime) {
                data.late_time = lateTime;
            }

            if (button) {
                button.prop('disabled', true);
            }

            $.ajax({
                url: '{{ route('attendance.store') }}',
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        updateEmployeeUI(employeeId, status, response.data);
                        updateSummaryCards();
                        showToast(response.message || 'Attendance updated', 'success');

                        if (response.skillGapAnalysis) {
                            updateSkillGapUI(response.skillGapAnalysis);
                        }
                    }
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to update attendance';
                    showToast(errorMsg, 'error');
                },
                complete: function() {
                    isUpdating = false;
                    if (button) {
                        button.prop('disabled', false);
                    }
                }
            });
        }

        // Bulk Update Attendance
        function bulkUpdateAttendance(employeeIds, status, lateTime = null) {
            const data = {
                _token: '{{ csrf_token() }}',
                employee_ids: employeeIds,
                date: currentDate,
                status: status
            };

            if (lateTime) {
                data.bulk_late_time = lateTime;
            }

            $.ajax({
                url: '{{ route('attendance.bulk-update') }}',
                type: 'POST',
                data: data,
                beforeSend: function() {
                    $('#employee-list').addClass('loading');
                },
                success: function(response) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    showToast('Failed to bulk update', 'error');
                },
                complete: function() {
                    $('#employee-list').removeClass('loading');
                }
            });
        }

        // Bulk Update with Individual Times
        function bulkUpdateAttendanceWithIndividualTimes(employeeIds, lateTimeInputs) {
            const data = {
                _token: '{{ csrf_token() }}',
                date: currentDate,
                status: 'late',
                employees_with_times: []
            };

            employeeIds.forEach(empId => {
                data.employees_with_times.push({
                    employee_id: empId,
                    late_time: lateTimeInputs[empId]
                });
            });

            $.ajax({
                url: '{{ route('attendance.bulk-update-individual') }}',
                type: 'POST',
                data: JSON.stringify(data),
                contentType: 'application/json',
                beforeSend: function() {
                    $('#employee-list').addClass('loading');
                },
                success: function(response) {
                    showToast(response.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: function(xhr) {
                    const errorMsg = xhr.responseJSON?.message || 'Failed to bulk update';
                    showToast(errorMsg, 'error');
                },
                complete: function() {
                    $('#employee-list').removeClass('loading');
                }
            });
        }

        // Update Employee UI
        function updateEmployeeUI(employeeId, status, responseData) {
            const rowCard = $(`.employee-card[data-employee-id="${employeeId}"]`);
            const btnGroup = rowCard.find('.status-btn-group');

            // Reset all buttons
            btnGroup.find('.status-btn').removeClass(
                'btn-success btn-danger btn-warning active btn-outline-success btn-outline-danger btn-outline-warning'
            );

            // Set button classes based on status
            btnGroup.find('.status-btn').each(function() {
                const btnStatus = $(this).data('status');
                const outlineClass = btnStatus === 'present' ? 'btn-outline-success' :
                                  btnStatus === 'absent' ? 'btn-outline-danger' : 
                                  'btn-outline-warning';
                $(this).addClass(outlineClass);
            });

            // Activate current status button
            const activeBtn = btnGroup.find(`[data-status="${status}"]`);
            const activeClass = status === 'present' ? 'btn-success active' :
                              status === 'absent' ? 'btn-danger active' : 
                              'btn-warning active';
            
            activeBtn.removeClass(`btn-outline-${status === 'present' ? 'success' : status === 'absent' ? 'danger' : 'warning'}`)
                     .addClass(activeClass);

            // Update recorded time
            if (responseData && responseData.recorded_time) {
                rowCard.find('.recorded-time').html(`
                    <small class="text-muted">
                        <i class="bi bi-clock-history"></i>
                        Recorded at: ${responseData.recorded_time}
                    </small>
                `);
            }
        }

        // Update Summary Cards
        function updateSummaryCards() {
            const total = $('.employee-card').length;
            const present = $('.status-btn[data-status="present"]').filter('.btn-success, .active').length;
            const absent = $('.status-btn[data-status="absent"]').filter('.btn-danger, .active').length;
            const late = $('.status-btn[data-status="late"]').filter('.btn-warning, .active').length;

            animateCounter('#summary-total', total);
            animateCounter('#summary-present', present);
            animateCounter('#summary-absent', absent);
            animateCounter('#summary-late', late);
        }

        // Animate Counter
        function animateCounter(selector, newValue) {
            const element = $(selector);
            const currentValue = parseInt(element.text()) || 0;
            
            if (currentValue !== newValue) {
                element.fadeOut(100, function() {
                    $(this).text(newValue).fadeIn(100);
                });
            }
        }

        // Update Skill Gap UI
        function updateSkillGapUI(skillGapAnalysis) {
            const alertContainer = $('.alert-skill-gap');

            if (skillGapAnalysis.total_affected_employees > 0) {
                if (alertContainer.length === 0) {
                    setTimeout(() => location.reload(), 1000);
                    return;
                }

                alertContainer.fadeOut(150, function() {
                    const strongElements = $(this).find('strong');
                    
                    if (strongElements.length > 0) {
                        $(strongElements[0]).text(skillGapAnalysis.total_affected_employees);
                    }
                    
                    if (strongElements.length > 1) {
                        $(strongElements[1]).text(Object.keys(skillGapAnalysis.missing_skills).length);
                    }

                    $(this).removeClass('alert-warning alert-danger')
                           .addClass(skillGapAnalysis.has_critical_impact ? 'alert-danger' : 'alert-warning');
                    
                    $(this).fadeIn(150);
                });
            } else {
                if (alertContainer.length > 0) {
                    alertContainer.fadeOut(200, function() {
                        $(this).remove();
                    });
                }
            }
        }

        // Auto-submit filter on change
        function initializeFilterForm() {
            $('select[name="department_id"], select[name="position"], select[name="status"], input[name="date"]')
                .on('change', function() {
                    $('#filter-form').submit();
                });
        }

        // Initialize all components
        function initializeAll() {
            initializeClock();
            initializeCheckboxes();
            initializeStatusButtons();
            initializeLateForm();
            initializeBulkActions();
            initializeBulkLateForm();
            initializeFilterForm();
            
            // Initial update
            updateSelectedCount();
            updateSummaryCards();
        }

        // Start initialization
        initializeAll();
    });
</script>