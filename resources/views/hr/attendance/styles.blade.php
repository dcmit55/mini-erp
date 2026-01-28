<style>
    /* Gradient Icon */
    .gradient-icon {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Current Time & Date Cards */
    .bg-light.border {
        background-color: #f8f9fa !important;
        border-color: #dee2e6 !important;
        transition: all 0.3s ease;
    }

    .bg-light.border:hover {
        background-color: #e9ecef !important;
        border-color: #adb5bd !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1) !important;
    }

    .bg-primary.rounded {
        background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%) !important;
        transition: all 0.3s ease;
    }

    .bg-primary.rounded:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3) !important;
    }

    /* Button Styling */
    .btn-outline-primary.btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        transition: all 0.3s ease;
    }

    .btn-outline-primary.btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(13, 110, 253, 0.2);
    }

    /* Summary Cards */
    .summary-card {
        transition: all 0.3s ease;
    }

    .summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1) !important;
    }

    /* Avatar Circle */
    .avatar-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        color: white;
    }

    /* Status Button */
    .status-btn {
        transition: all 0.3s;
    }

    .status-btn.active {
        transform: scale(1.07);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.07);
    }

    /* Employee Card */
    .employee-card {
        transition: box-shadow 0.2s, transform 0.2s;
    }

    .employee-card:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.12);
        transform: translateY(-1px);
    }

    .employee-card.selected {
        background-color: rgba(13, 110, 253, 0.05);
        border-left: 4px solid #0d6efd;
    }

    /* Skill Gap Alert Styling */
    .alert-skill-gap {
        border-left-width: 5px !important;
    }

    .alert-skill-gap.alert-danger {
        border-left-color: #dc3545 !important;
    }

    .alert-skill-gap.alert-warning {
        border-left-color: #ffc107 !important;
    }

    .alert-danger .btn-close-white {
        filter: brightness(0) invert(1);
    }

    /* Modal Styling */
    .modal-xl {
        max-width: 1200px;
    }

    .card.border-danger {
        border-width: 2px;
        border-color: #dc3545 !important;
    }

    .card.border-warning {
        border-width: 2px;
        border-color: #ffc107 !important;
    }

    .bulk-late-card {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        background-color: #f8f9fa;
        transition: all 0.2s;
    }

    .bulk-late-card:hover {
        background-color: #e9ecef;
    }

    /* Filter Form */
    .filter-form .form-control,
    .filter-form .form-select {
        border-radius: 50px;
        border: 1px solid #ced4da;
    }

    .filter-form .form-control:focus,
    .filter-form .form-select:focus {
        border-color: #8F12FE;
        box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
    }

    /* Bulk Action Buttons */
    .btn-bulk-action {
        padding: 0.4rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s;
    }

    .btn-bulk-action:hover {
        transform: translateY(-2px);
    }

    /* Toast Notification */
    .toast {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: none;
    }

    /* Print Styles */
    @media print {
        .modal-header,
        .modal-footer,
        .btn,
        .filter-form,
        .alert {
            display: none !important;
        }

        .modal-body {
            padding: 0 !important;
        }

        .card {
            break-inside: avoid;
            page-break-inside: avoid;
            border: 1px solid #dee2e6 !important;
        }
    }

    /* Responsive Design */
    @media (max-width: 767.98px) {
        /* Header Responsive */
        .header-flex {
            flex-direction: column !important;
            align-items: stretch !important;
        }

        .header-time-cards {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        .bg-light.border,
        .bg-primary.rounded,
        .btn-outline-primary.btn-sm {
            width: 100%;
            justify-content: center;
        }

        /* Summary Cards Responsive */
        .summary-card .card-body {
            padding: 0.75rem !important;
        }

        .summary-card h3 {
            font-size: 1.5rem !important;
        }

        /* Employee List Responsive */
        .employee-card .card-body {
            padding: 1rem 0.5rem !important;
        }

        .employee-card .d-flex.flex-row {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        .status-btn-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem !important;
            justify-content: center;
        }

        .status-btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.8rem;
            flex: 1;
            min-width: 90px;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            font-size: 15px;
        }

        /* Filter Form Responsive */
        .filter-form .row.g-3 > div {
            margin-bottom: 0.75rem;
        }

        .filter-form button[type="submit"] {
            width: 100%;
            margin-top: 0.5rem;
        }

        /* Bulk Action Buttons Responsive */
        .bulk-actions-container {
            flex-direction: column !important;
            gap: 0.5rem !important;
        }

        .btn-bulk-action {
            width: 100%;
        }

        /* Modal Responsive */
        .modal-dialog {
            margin: 0.5rem;
        }

        .bulk-late-card {
            margin-bottom: 0.75rem;
        }
    }

    @media (min-width: 768px) and (max-width: 991.98px) {
        /* Medium screens */
        .employee-card .col-md-4 {
            flex: 0 0 50%;
            max-width: 50%;
        }

        .status-btn {
            font-size: 0.8rem;
            padding: 0.3rem 0.6rem;
        }
    }

    /* Animation Classes */
    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    .slide-up {
        animation: slideUp 0.3s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideUp {
        from { transform: translateY(10px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    /* Selection Counter */
    #selected-count {
        color: #0d6efd;
        font-weight: bold;
    }

    /* Loading States */
    .loading {
        opacity: 0.7;
        pointer-events: none;
    }

    .loading::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 20px;
        height: 20px;
        border: 2px solid #f3f3f3;
        border-top: 2px solid #0d6efd;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }
</style>