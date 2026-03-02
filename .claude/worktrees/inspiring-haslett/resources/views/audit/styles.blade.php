<style>
    /* Icon Gradient */
    .gradient-icon {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    /* Filter Form Styling */
    #filter-form {
        background: #f8f9fa;
        padding: .75rem;
        border-radius: 0.5rem;
        border: 1px solid #dee2e6;
    }

    /* Pagination Styling */
    .pagination {
        --bs-pagination-padding-x: 0.75rem;
        --bs-pagination-padding-y: 0.375rem;
        --bs-pagination-color: #6c757d;
        --bs-pagination-bg: #fff;
        --bs-pagination-border-width: 1px;
        --bs-pagination-border-color: #dee2e6;
        --bs-pagination-border-radius: 0.375rem;
        --bs-pagination-hover-color: #495057;
        --bs-pagination-hover-bg: #e9ecef;
        --bs-pagination-hover-border-color: #dee2e6;
        --bs-pagination-focus-color: #495057;
        --bs-pagination-focus-bg: #e9ecef;
        --bs-pagination-focus-box-shadow: 0 0 0 0.25rem rgba(143, 18, 254, 0.25);
        --bs-pagination-active-color: #fff;
        --bs-pagination-active-bg: #8F12FE;
        --bs-pagination-active-border-color: #4A25AA;
        --bs-pagination-disabled-color: #6c757d;
        --bs-pagination-disabled-bg: #fff;
        --bs-pagination-disabled-border-color: #dee2e6;
    }

    .page-link {
        transition: all 0.15s ease-in-out;
    }

    .page-link:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .page-item.active .page-link {
        background: linear-gradient(135deg, #8F12FE 0%, #4A25AA 100%);
        border-color: #8F12FE;
        box-shadow: 0 2px 4px rgba(143, 18, 254, 0.3);
    }

    /* Divider */
    .vr-divider {
        width: 1px;
        height: 24px;
        background: #dee2e6;
        display: inline-block;
        vertical-align: middle;
    }

    /* DataTables Footer */
    .datatables-footer-row {
        border-top: 1px solid #eee;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }

    .datatables-left {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .dataTables_paginate {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }

    /* Table Styling */
    #auditTable tbody td {
        padding: 10px 8px;
        vertical-align: middle;
        border-bottom: 1px solid #f1f3f4;
    }

    #auditTable thead th {
        font-weight: 600;
        background-color: #f8f9fa;
    }

    /* Modal Table */
    #changesModal table {
        table-layout: fixed;
        width: 100%;
    }

    #changesModal td {
        word-break: break-all;
        white-space: pre-line;
        max-width: 250px;
        vertical-align: top;
    }

    /* Badge Colors for Events */
    .badge-created {
        background-color: #28a745 !important;
        color: white !important;
    }

    .badge-updated {
        background-color: #ffc107 !important;
        color: #212529 !important;
    }

    .badge-deleted {
        background-color: #dc3545 !important;
        color: white !important;
    }

    .badge-restored {
        background-color: #17a2b8 !important;
        color: white !important;
    }

    /* Responsive Adjustments */
    @media (max-width: 767.98px) {
        .datatables-footer-row {
            flex-direction: column !important;
            gap: 0.5rem;
        }

        .datatables-left {
            flex-direction: column !important;
            gap: 0.5rem;
        }

        .vr-divider {
            display: none;
        }

        .dataTables_paginate {
            justify-content: center !important;
        }

        #auditTable thead th {
            font-size: 0.8rem;
            padding: 8px 4px;
        }

        #auditTable tbody td {
            padding: 8px 4px;
            font-size: 0.85rem;
        }

        #filter-form .col-md-1,
        #filter-form .col-md-2,
        #filter-form .col-md-3 {
            margin-bottom: 0.5rem;
        }
    }

    /* Loading Animation */
    .spinner-border.audit-spinner {
        width: 1.5rem;
        height: 1.5rem;
    }

    /* Card Styling */
    .card-shadow {
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.3s ease;
    }

    .card-shadow:hover {
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
    }

    /* Button Styling */
    .btn-audit-danger {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border-color: #dc3545;
        color: white;
    }

    .btn-audit-danger:hover {
        background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
        border-color: #c82333;
        color: white;
    }

    /* Alert Styling */
    .alert-audit-warning {
        border-left: 4px solid #ffc107;
        background-color: #fff8e1;
    }

    .alert-audit-danger {
        border-left: 4px solid #dc3545;
        background-color: #f8d7da;
    }
</style>