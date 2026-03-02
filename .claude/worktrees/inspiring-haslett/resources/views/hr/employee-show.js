// resources/js/hr/employee-show.js
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between display and edit mode
    document.querySelectorAll('.toggle-edit').forEach(button => {
        button.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            const displayDiv = document.getElementById(`${section}-display`);
            const editDiv = document.getElementById(`${section}-edit`);
            
            if (displayDiv && editDiv) {
                displayDiv.classList.add('d-none');
                editDiv.classList.remove('d-none');
            }
        });
    });
    
    // Cancel edit
    document.querySelectorAll('.cancel-edit').forEach(button => {
        button.addEventListener('click', function() {
            const section = this.getAttribute('data-section');
            const displayDiv = document.getElementById(`${section}-display`);
            const editDiv = document.getElementById(`${section}-edit`);
            
            if (displayDiv && editDiv) {
                editDiv.classList.add('d-none');
                displayDiv.classList.remove('d-none');
            }
        });
    });
    
    // Handle inline field editing
    document.querySelectorAll('.editable-field').forEach(field => {
        field.addEventListener('click', function(e) {
            if (!e.target.closest('.edit-icon')) return;
            
            const fieldName = this.getAttribute('data-field');
            const currentValue = this.textContent.trim();
            
            // Create inline edit form
            const form = document.createElement('form');
            form.className = 'inline-edit-form d-inline-flex align-items-center gap-2';
            form.innerHTML = `
                <input type="text" class="form-control form-control-sm" 
                       value="${currentValue}" name="${fieldName}" 
                       style="width: 200px;">
                <button type="submit" class="btn btn-success btn-sm">
                    <i class="bi bi-check"></i>
                </button>
                <button type="button" class="btn btn-secondary btn-sm cancel-inline">
                    <i class="bi bi-x"></i>
                </button>
            `;
            
            // Replace content with form
            const originalContent = this.innerHTML;
            this.innerHTML = '';
            this.appendChild(form);
            form.querySelector('input').focus();
            
            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const newValue = this.querySelector('input').value;
                
                // Send AJAX request
                fetch(`/employees/{{ $employee->id }}/update-field`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        field: fieldName,
                        value: newValue
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update display
                        field.innerHTML = data.new_display;
                        showToast('Field updated successfully', 'success');
                    } else {
                        throw new Error(data.message || 'Update failed');
                    }
                })
                .catch(error => {
                    field.innerHTML = originalContent;
                    showToast(error.message, 'error');
                });
            });
            
            // Handle cancel
            form.querySelector('.cancel-inline').addEventListener('click', function() {
                field.innerHTML = originalContent;
            });
        });
    });
    
    // Handle AJAX form submissions
    document.querySelectorAll('.ajax-form').forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="spinner-border spinner-border-sm"></i>';
            submitBtn.disabled = true;
            
            const formData = new FormData(this);
            
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page or update specific sections
                    showToast(data.message || 'Updated successfully', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    throw new Error(data.message || 'Update failed');
                }
            })
            .catch(error => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
                showToast(error.message, 'error');
            });
        });
    });
    
    // Toast notification helper
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        const container = document.querySelector('.toast-container') || createToastContainer();
        container.appendChild(toast);
        
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }
    
    function createToastContainer() {
        const container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
        return container;
    }
});