// public/js/hr/employee-form.js
document.addEventListener('DOMContentLoaded', function() {
    // Photo preview function
    window.previewPhoto = function(input) {
        const file = input.files[0];
        const preview = document.getElementById('photo-preview');
        const feedback = document.getElementById('photo-feedback');
        
        if (!file) {
            if (feedback) feedback.textContent = '';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!allowedTypes.includes(file.type)) {
            showFeedback(feedback, 'Invalid file type. Only JPG, PNG, JPEG allowed.', 'danger');
            return;
        }
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            showFeedback(feedback, 'File size too large. Maximum 2MB allowed.', 'danger');
            return;
        }
        
        // Preview image
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            showFeedback(feedback, 'Photo uploaded successfully!', 'success');
        };
        reader.readAsDataURL(file);
    };
    
    // Employee number validation
    const employeeNoInput = document.getElementById('employee_no');
    if (employeeNoInput) {
        employeeNoInput.addEventListener('blur', function() {
            const value = this.value;
            const feedback = document.getElementById('employee-no-feedback');
            
            if (!value) {
                if (feedback) feedback.textContent = '';
                return;
            }
            
            // Check if number already exists (AJAX)
            fetch('/employees/check-employee-no', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    employee_no: value,
                    employee_id: window.employeeId || null
                })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.available) {
                    showFeedback(feedback, `Employee number DCM-${value.padStart(4, '0')} already exists.`, 'danger');
                } else {
                    showFeedback(feedback, 'Employee number is available.', 'success');
                }
            })
            .catch(error => {
                console.error('Validation error:', error);
            });
        });
    }
    
    // KTP ID validation
    const ktpInput = document.getElementById('ktp_id');
    if (ktpInput) {
        ktpInput.addEventListener('blur', function() {
            const value = this.value;
            
            if (!value) return;
            
            if (value.length !== 16) {
                showFeedback(this.parentNode.querySelector('.validation-feedback'), 
                           'KTP ID must be exactly 16 digits', 'danger');
            }
        });
    }
    
    // Date validation
    const hireDateInput = document.getElementById('hire_date');
    const contractEndInput = document.getElementById('contract_end_date');
    
    if (hireDateInput && contractEndInput) {
        hireDateInput.addEventListener('change', function() {
            if (this.value) {
                contractEndInput.min = this.value;
            }
        });
        
        contractEndInput.addEventListener('change', function() {
            const hireDate = hireDateInput.value;
            const contractEndDate = this.value;
            
            if (hireDate && contractEndDate) {
                if (new Date(contractEndDate) < new Date(hireDate)) {
                    showFeedback(this.parentNode.querySelector('.validation-feedback'),
                               'Contract end date must be after hire date', 'danger');
                }
            }
        });
    }
    
    // Date of birth validation
    const dobInput = document.getElementById('date_of_birth');
    if (dobInput) {
        dobInput.addEventListener('change', function() {
            const dob = new Date(this.value);
            const today = new Date();
            const age = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
            
            if (age < 17) {
                showFeedback(this.parentNode.querySelector('.validation-feedback'),
                           'Employee must be at least 17 years old', 'danger');
            }
        });
    }
    
    // Form submission handler
    const form = document.getElementById('employee-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate required files for new employees
            if (!this.hasAttribute('data-edit-mode')) {
                const photoInput = document.getElementById('photo');
                if (photoInput && !photoInput.files.length) {
                    e.preventDefault();
                    showToast('Please upload an employee photo', 'warning');
                    return;
                }
            }
            
            // Show loading state
            const submitBtn = document.getElementById('submit-btn');
            const spinner = document.getElementById('submit-spinner');
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
        });
    }
    
    // Helper functions
    function showFeedback(element, message, type) {
        if (!element) return;
        
        element.textContent = message;
        element.className = `validation-feedback text-${type}`;
    }
    
    function showToast(message, type = 'info') {
        // Simple toast implementation
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        setTimeout(() => {
            toast.remove();
        }, 5000);
    }
    
    // Auto-dismiss alerts
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            if (alert.classList.contains('show')) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);
});