/**
 * Form Utilities Library - SIMPLE VERSION
 * Fungsi umum yang bisa dipakai di semua form
 */

// Initialize Select2
function initSelect2(selector = '.select2') {
    $(selector).select2({
        placeholder: "Select an option",
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 5
    });
}

// Format currency to Indonesian Rupiah
function formatCurrency(amount) {
    return 'Rp ' + parseInt(amount || 0).toLocaleString('id-ID');
}

// Calculate total from quantity and price
function calculateTotal(quantity, unitPrice, freight = 0, otherCosts = 0) {
    const qty = parseInt(quantity) || 0;
    const price = parseInt(unitPrice) || 0;
    const freightCost = parseInt(freight) || 0;
    const other = parseInt(otherCosts) || 0;
    
    const totalPrice = qty * price;
    const invoiceTotal = totalPrice + freightCost + other;
    
    return { totalPrice, invoiceTotal };
}

// Toggle section visibility
function toggleSection(sectionElement, show = true) {
    if (!sectionElement) return;
    
    if (show) {
        sectionElement.classList.remove('d-none');
    } else {
        sectionElement.classList.add('d-none');
    }
}

// Clear validation errors
function clearValidation(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const invalidFields = form.querySelectorAll('.is-invalid');
    invalidFields.forEach(field => {
        field.classList.remove('is-invalid');
    });
}

// Show loading state on button
function showLoading(button, text = 'Loading...') {
    if (!button) return null;
    
    const originalHTML = button.innerHTML;
    const originalDisabled = button.disabled;
    
    button.innerHTML = `<i class="fas fa-spinner fa-spin me-1"></i>${text}`;
    button.disabled = true;
    
    // Return function to restore button
    return function restore() {
        button.innerHTML = originalHTML;
        button.disabled = originalDisabled;
    };
}

// Debounce function
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