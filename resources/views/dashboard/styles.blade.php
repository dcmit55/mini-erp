<style>
    /* Professional ERP Dashboard Styles */
    .bg-gradient-brand {
        background: linear-gradient(45deg, #8F12FE, #4A25AA) !important;
    }

    .bg-gradient-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    /* Brand Color Classes */
    .text-brand-icon {
        color: #8F12FE !important;
    }

    .text-brand {
        color: #8F12FE !important;
    }

    .bg-brand {
        background-color: rgba(144, 18, 254, 0.1) !important;
    }

    .border-brand {
        border-color: #8F12FE !important;
    }

    /* Role-specific Colors */
    .text-purple {
        color: #8F12FE !important;
    }

    .bg-purple {
        background-color: rgba(144, 18, 254, 0.1) !important;
    }

    .text-pink {
        color: #c2185b !important;
    }

    .bg-pink {
        background-color: rgba(233, 30, 98, 0.1) !important;
    }

    .text-cyan {
        color: #138496 !important;
    }

    .bg-cyan {
        background-color: rgba(23, 163, 184, 0.1) !important;
    }

    .btn-outline-brand {
        color: #8F12FE;
        border-color: #8F12FE;
        background: transparent;
    }

    .btn-outline-brand:hover {
        color: #fff;
        background: linear-gradient(45deg, #8F12FE, #4A25AA);
        border-color: #8F12FE;
        box-shadow: 0 4px 15px rgba(143, 18, 254, 0.2);
    }

    .btn-brand {
        color: #fff;
        background: linear-gradient(45deg, #8F12FE, #4A25AA);
        border-color: #8F12FE;
    }

    .btn-brand:hover {
        color: #fff;
        background: linear-gradient(45deg, #7A0FE8, #3D1F8F);
        border-color: #7A0FE8;
        box-shadow: 0 4px 15px rgba(143, 18, 254, 0.3);
    }

    .card {
        border-radius: 12px !important;
        overflow: hidden !important;
    }

    .card-hover {
        transition: all 0.3s ease;
    }

    .card-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15) !important;
    }

    .metric-icon {
        width: 60px;
        height: 60px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .activity-icon,
    .deadline-icon {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .dept-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .dept-card:hover {
        background-color: #eae3fd !important;
        transform: translateY(-2px);
    }

    .list-group-item {
        transition: background-color 0.2s ease;
    }

    .list-group-item:hover {
        background-color: #f8f9fa;
    }

    /* Chart container styling */
    #trendsChart,
    #requestStatusChart {
        max-height: 300px;
        min-height: 180px;
    }

    /* Animation for loading states */
    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Custom badge colors */
    .text-bg-pending {
        background-color: #ffc107 !important;
        color: #000 !important;
    }

    /* Gradient effects for cards */
    .card {
        border-radius: 12px;
    }

    .rounded-3 {
        border-radius: 12px !important;
    }

    /* Professional shadows */
    .shadow-sm {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    }

    .shadow-lg {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12) !important;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .metric-value {
            font-size: 1.5rem !important;
        }

        .dashboard-clock-container {
            text-align: center !important;
            margin-top: 1rem;
        }

        .avatar-wrapper {
            margin-bottom: 1rem;
        }
    }

    @media (max-width: 576px) {
        .low-stock-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 0.5rem !important;
        }

        .low-stock-filter-controls {
            width: 100%;
            flex-wrap: wrap;
            gap: 0.5rem !important;
            margin-top: 0.5rem;
        }

        .low-stock-filter-controls select {
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
        }

        .low-stock-filter-controls button {
            width: auto !important;
            min-width: 36px !important;
            max-width: 48% !important;
            display: inline-block !important;
        }
    }

    @media (max-width: 768px) {
        #trendsChart {
            min-height: 260px !important;
            max-height: 320px !important;
        }

        .card-body>#trendsChart {
            min-height: 260px !important;
        }

        .card-body {
            padding-bottom: 0.5rem !important;
        }
    }
</style>