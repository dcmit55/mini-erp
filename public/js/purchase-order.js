/**
 * Purchase Order Form JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2
    $('.select2-joborder, .select2-internal-project, .select2-material, .select2-supplier').select2({
        placeholder: "Select an option",
        allowClear: true,
        width: '100%',
        minimumResultsForSearch: 5
    });

    // Get all elements
    const clientProjectRadio = document.getElementById('clientProjectType');
    const internalProjectRadio = document.getElementById('internalProjectType');
    const clientProjectSection = document.querySelector('.client-project-section');
    const internalProjectSection = document.querySelector('.internal-project-section');
    const jobOrderSelect = document.getElementById('jobOrderSelect');
    const internalProjectSelect = document.getElementById('internalProjectSelect');
    const departmentIdInput = document.getElementById('departmentId');
    const projectIdInput = document.getElementById('projectId');
    
    const restockRadio = document.getElementById('restockType');
    const newItemRadio = document.getElementById('newItemType');
    const restockSection = document.getElementById('restockSection');
    const newItemSection = document.getElementById('newItemSection');
    const materialSelect = document.getElementById('materialSelect');
    const newItemName = document.getElementById('newItemName');
    const unitSelect = document.getElementById('unitSelect');
    const categorySelect = document.getElementById('categorySelect');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unitPrice');
    const freightInput = document.getElementById('freight');
    const otherCostsInput = document.getElementById('otherCosts');
    
    const existingSupplierRadio = document.getElementById('existingSupplier');
    const newSupplierRadio = document.getElementById('newSupplier');
    const existingSupplierSection = document.getElementById('existingSupplierSection');
    const newSupplierSection = document.getElementById('newSupplierSection');
    const supplierSelect = document.getElementById('supplierSelect');
    const newSupplierName = document.getElementById('newSupplierName');
    
    const onlineOrderRadio = document.getElementById('onlineOrder');
    const offlineOrderRadio = document.getElementById('offlineOrder');
    const newSupplierOrderTypes = document.querySelectorAll('.new-supplier-order-type');
    
    const resiNumberInput = document.getElementById('resiNumber');
    
    const totalPriceInput = document.getElementById('totalPrice');
    const invoiceTotalInput = document.getElementById('invoiceTotal');
    const displayTotalPrice = document.getElementById('displayTotalPrice');
    const displayInvoiceTotal = document.getElementById('displayInvoiceTotal');
    
    const clientProjectInfo = document.getElementById('clientProjectInfo');
    const internalProjectInfo = document.getElementById('internalProjectInfo');
    const purchaseForm = document.getElementById('purchaseForm');
    const poNumberInput = document.getElementById('poNumber');

    // Toggle project type sections
    function toggleProjectType() {
        const isClientProject = clientProjectRadio.checked;
        
        if (isClientProject) {
            clientProjectSection.classList.remove('d-none');
            internalProjectSection.classList.add('d-none');
            
            jobOrderSelect.required = true;
            internalProjectSelect.required = false;
            
            $(jobOrderSelect).prop('disabled', false).trigger('change');
            $(internalProjectSelect).prop('disabled', true).trigger('change');
            
            internalProjectSelect.value = '';
            $(internalProjectSelect).trigger('change');
        } else {
            clientProjectSection.classList.add('d-none');
            internalProjectSection.classList.remove('d-none');
            
            jobOrderSelect.required = false;
            internalProjectSelect.required = true;
            
            $(jobOrderSelect).prop('disabled', true).trigger('change');
            $(internalProjectSelect).prop('disabled', false).trigger('change');
            
            jobOrderSelect.value = '';
            $(jobOrderSelect).trigger('change');
        }
        
        departmentIdInput.value = '';
        projectIdInput.value = '';
        clearProjectInfo();
    }

    // Toggle purchase type sections
    function togglePurchaseType() {
        const isRestock = restockRadio.checked;
        
        if (isRestock) {
            restockSection.classList.remove('d-none');
            newItemSection.classList.add('d-none');
            materialSelect.required = true;
            newItemName.required = false;
            
            materialSelect.disabled = false;
            newItemName.disabled = true;
        } else {
            restockSection.classList.add('d-none');
            newItemSection.classList.remove('d-none');
            materialSelect.required = false;
            newItemName.required = true;
            
            materialSelect.disabled = true;
            newItemName.disabled = false;
        }
    }

    // Toggle supplier type sections
    function toggleSupplierType() {
        const isExistingSupplier = existingSupplierRadio.checked;
        
        if (isExistingSupplier) {
            existingSupplierSection.classList.remove('d-none');
            newSupplierSection.classList.add('d-none');
            
            supplierSelect.required = true;
            newSupplierName.required = false;
            
            supplierSelect.disabled = false;
            newSupplierName.disabled = true;
            document.querySelectorAll('input[name="is_offline_order"]').forEach(radio => {
                radio.disabled = false;
            });
        } else {
            existingSupplierSection.classList.add('d-none');
            newSupplierSection.classList.remove('d-none');
            
            supplierSelect.required = false;
            newSupplierName.required = true;
            
            supplierSelect.disabled = true;
            newSupplierName.disabled = false;
            document.querySelectorAll('input[name="is_offline_order"]').forEach(radio => {
                radio.disabled = true;
            });
        }
    }

    // Calculate totals
    function calculateTotals() {
        const quantity = parseInt(quantityInput.value) || 0;
        const unitPrice = parseInt(unitPriceInput.value) || 0;
        const freight = parseInt(freightInput.value) || 0;
        const otherCosts = parseInt(otherCostsInput.value) || 0;
        
        const totalPrice = quantity * unitPrice;
        const invoiceTotal = totalPrice + freight + otherCosts;
        
        totalPriceInput.value = totalPrice;
        invoiceTotalInput.value = invoiceTotal;
        
        displayTotalPrice.textContent = formatCurrency(totalPrice);
        displayInvoiceTotal.textContent = formatCurrency(invoiceTotal);
    }

    // Clear project info display
    function clearProjectInfo() {
        clientProjectInfo.innerHTML = '<small class="text-muted">Select a job order to see details</small>';
        internalProjectInfo.innerHTML = '<small class="text-muted">Select an internal project to see details</small>';
    }

    // Display client project info - FIXED VERSION
    function displayClientProjectInfo(departmentId, departmentName, projectId, projectName) {
        if (departmentId && projectId) {
            clientProjectInfo.innerHTML = `
                <div class="w-100">
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="project-info-label">Department:</div>
                        <div class="project-info-value">${departmentName || 'N/A'}</div>
                    </div>
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="project-info-label">Project:</div>
                        <div class="project-info-value">${projectName || 'N/A'}</div>
                    </div>
                </div>
            `;
            
            departmentIdInput.value = departmentId;
            projectIdInput.value = projectId;
        } else {
            clearProjectInfo();
            departmentIdInput.value = '';
            projectIdInput.value = '';
        }
    }

    // Display internal project info - FIXED VERSION
    function displayInternalProjectInfo(project, department, job, description, departmentId) {
        if (project && department && job) {
            internalProjectInfo.innerHTML = `
                <div class="w-100">
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                        <div class="project-info-label">Project:</div>
                        <div class="project-info-value">${project || 'N/A'}</div>
                    </div>
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="project-info-label">Department:</div>
                        <div class="project-info-value">${department || 'N/A'}</div>
                    </div>
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="project-info-label">Job:</div>
                        <div class="project-info-value">${job || 'N/A'}</div>
                    </div>
                    ${description ? `
                    <div class="project-info-item">
                        <div class="project-info-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="project-info-label">Description:</div>
                        <div class="project-info-value small">${description}</div>
                    </div>
                    ` : ''}
                </div>
            `;
            
            departmentIdInput.value = departmentId || '';
            projectIdInput.value = '';
        } else {
            clearProjectInfo();
            departmentIdInput.value = '';
            projectIdInput.value = '';
        }
    }

    // Event listener for job order selection - FIXED VERSION
    function handleJobOrderSelection() {
        const selectedOption = jobOrderSelect.options[jobOrderSelect.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) {
            clearProjectInfo();
            departmentIdInput.value = '';
            projectIdInput.value = '';
            return;
        }
        
        // Get data attributes directly from the option element
        const departmentId = selectedOption.getAttribute('data-deptid');
        const departmentName = selectedOption.getAttribute('data-deptname');
        const projectId = selectedOption.getAttribute('data-projid');
        const projectName = selectedOption.getAttribute('data-projname');
        
        console.log('Job Order Selected:', { 
            departmentId, 
            departmentName, 
            projectId, 
            projectName 
        });
        
        displayClientProjectInfo(departmentId, departmentName, projectId, projectName);
    }
    
    // Event listener for internal project selection - FIXED VERSION
    function handleInternalProjectSelection() {
        const selectedOption = internalProjectSelect.options[internalProjectSelect.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) {
            clearProjectInfo();
            departmentIdInput.value = '';
            return;
        }
        
        const project = selectedOption.getAttribute('data-project');
        const department = selectedOption.getAttribute('data-department');
        const job = selectedOption.getAttribute('data-job');
        const description = selectedOption.getAttribute('data-description');
        const departmentId = selectedOption.getAttribute('data-department-id');
        
        console.log('Internal Project Selected:', { 
            project, 
            department, 
            job, 
            description, 
            departmentId 
        });
        
        displayInternalProjectInfo(project, department, job, description, departmentId);
    }

    // Auto-fill unit price, unit and category when material is selected - FIXED VERSION
    function handleMaterialSelection() {
        const selectedOption = materialSelect.options[materialSelect.selectedIndex];
        
        if (!selectedOption || !selectedOption.value) return;
        
        const materialPrice = selectedOption.getAttribute('data-price');
        const unitId = selectedOption.getAttribute('data-unit-id');
        const categoryId = selectedOption.getAttribute('data-category-id');
        
        console.log('Material Selected:', { materialPrice, unitId, categoryId });
        
        if (materialPrice && materialPrice > 0) {
            unitPriceInput.value = materialPrice;
        }
        
        if (unitId && unitId !== 'null') {
            unitSelect.value = unitId;
        }
        
        if (categoryId && categoryId !== 'null') {
            categorySelect.value = categoryId;
        }
        
        calculateTotals();
    }

    // Set up event listeners
    function setupEventListeners() {
        // Project Type Toggle
        clientProjectRadio.addEventListener('change', toggleProjectType);
        internalProjectRadio.addEventListener('change', toggleProjectType);
        
        // Purchase Type Toggle
        restockRadio.addEventListener('change', togglePurchaseType);
        newItemRadio.addEventListener('change', togglePurchaseType);
        
        // Supplier Type Toggle
        existingSupplierRadio.addEventListener('change', toggleSupplierType);
        newSupplierRadio.addEventListener('change', toggleSupplierType);
        
        // Job Order Selection
        jobOrderSelect.addEventListener('change', handleJobOrderSelection);
        
        // Internal Project Selection
        internalProjectSelect.addEventListener('change', handleInternalProjectSelection);
        
        // Material Selection
        materialSelect.addEventListener('change', handleMaterialSelection);
        
        // Quantity & Price Calculations
        const calculationInputs = [quantityInput, unitPriceInput, freightInput, otherCostsInput];
        calculationInputs.forEach(input => {
            if (input) {
                input.addEventListener('input', debounce(calculateTotals, 300));
                input.addEventListener('change', calculateTotals);
            }
        });
        
        // Form Submission
        purchaseForm.addEventListener('submit', handleFormSubmit);
    }

    // Handle form submission
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const unitPrice = parseInt(unitPriceInput.value);
        const quantity = parseInt(quantityInput.value);
        const isClientProject = clientProjectRadio.checked;
        const isRestock = restockRadio.checked;
        const isExistingSupplier = existingSupplierRadio.checked;
        const poNumber = poNumberInput.value.trim();
        
        console.log('Form Submit:', {
            poNumber,
            isClientProject,
            isRestock,
            isExistingSupplier,
            unitPrice,
            quantity
        });
        
        // Clear previous error states
        const errorFields = document.querySelectorAll('.is-invalid');
        errorFields.forEach(field => field.classList.remove('is-invalid'));
        
        // Validate PO Number
        if (!poNumber) {
            poNumberInput.classList.add('is-invalid');
            poNumberInput.focus();
            alert('Please enter PO Number');
            return;
        }
        
        // Validate project type selection
        if (isClientProject) {
            if (!jobOrderSelect.value) {
                jobOrderSelect.classList.add('is-invalid');
                alert('Please select a job order for client project');
                jobOrderSelect.focus();
                return;
            }
        } else {
            if (!internalProjectSelect.value) {
                internalProjectSelect.classList.add('is-invalid');
                alert('Please select an internal project');
                internalProjectSelect.focus();
                return;
            }
        }
        
        // Validate department is filled
        if (!departmentIdInput.value) {
            alert('Department information is missing. Please select a valid job order or internal project.');
            return;
        }
        
        // Validate purchase type specific fields
        if (isRestock) {
            if (!materialSelect.value) {
                materialSelect.classList.add('is-invalid');
                alert('Please select a material for restock purchase');
                materialSelect.focus();
                return;
            }
        } else {
            if (!newItemName.value.trim()) {
                newItemName.classList.add('is-invalid');
                alert('Please enter new item name');
                newItemName.focus();
                return;
            }
        }
        
        // Validate supplier type specific fields
        if (isExistingSupplier) {
            if (!supplierSelect.value) {
                supplierSelect.classList.add('is-invalid');
                alert('Please select a supplier');
                supplierSelect.focus();
                return;
            }
        } else {
            if (!newSupplierName.value.trim()) {
                newSupplierName.classList.add('is-invalid');
                alert('Please enter new supplier name');
                newSupplierName.focus();
                return;
            }
        }
        
        // Validate required fields
        const requiredFields = purchaseForm.querySelectorAll('[required]');
        let isValid = true;
        let firstInvalidField = null;
        
        requiredFields.forEach(field => {
            if (!field.value.trim() || field.value === '') {
                // Skip supplier select if using new supplier
                if (field.name === 'supplier_id' && !isExistingSupplier) return;
                // Skip internal project select if using client project
                if (field.name === 'internal_project_id' && isClientProject) return;
                // Skip job order select if using internal project
                if (field.name === 'job_order_id' && !isClientProject) return;
                // Skip material select if new item
                if (field.name === 'material_id' && !isRestock) return;
                // Skip new item name if restock
                if (field.name === 'new_item_name' && isRestock) return;
                // Skip is_offline_order if new supplier
                if (field.name === 'is_offline_order' && !isExistingSupplier) return;
                // Skip new_supplier_is_offline_order if existing supplier
                if (field.name === 'new_supplier_is_offline_order' && isExistingSupplier) return;
                
                isValid = false;
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
                field.classList.add('is-invalid');
            }
        });
        
        if (!isValid) {
            alert('Please fill in all required fields');
            if (firstInvalidField) {
                firstInvalidField.focus();
            }
            return;
        }
        
        if (unitPrice <= 0) {
            alert('Unit price must be greater than 0');
            unitPriceInput.classList.add('is-invalid');
            unitPriceInput.focus();
            return;
        }
        
        if (quantity <= 0) {
            alert('Quantity must be greater than 0');
            quantityInput.classList.add('is-invalid');
            quantityInput.focus();
            return;
        }
        
        // Show loading state
        const submitBtn = purchaseForm.querySelector('button[type="submit"]');
        const restoreButton = showLoading(submitBtn, 'Creating...');
        
        // Submit form
        setTimeout(() => {
            purchaseForm.submit();
        }, 100);
    }

    // Initialize on page load
    console.log('Initializing purchase order form...');
    
    // Initialize form state
    toggleProjectType();
    togglePurchaseType();
    toggleSupplierType();
    calculateTotals();
    
    // Setup event listeners
    setupEventListeners();
    
    console.log('Purchase order form initialized successfully');
});