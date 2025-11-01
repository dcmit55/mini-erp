<div class="card sticky-top related-items-panel" style="top: 20px;">
    <div class="card-header bg-gradient border-0">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-boxes text-primary" style="font-size: 1.2rem;"></i>
            <h6 class="mb-0">Related Items</h6>
            <small class="text-muted ms-auto">
                <span class="related-items-badge">0</span> items
            </small>
        </div>
        <small class="text-muted d-block mt-2">from Purchase Request</small>
    </div>
    <div class="card-body related-items-container" style="max-height: 600px; overflow-y: auto;">
        <div class="text-center py-5">
            <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
            <p class="text-muted mt-3 mb-0">
                Select a project to view<br>related purchase requests
            </p>
        </div>
    </div>
</div>

@push('styles')
    <style>
        .related-items-panel {
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            border-left: 4px solid #4e73df;
        }

        .related-items-panel .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #fff 100%);
            border-bottom: 1px solid #dee2e6;
        }

        .related-item-card {
            border: 1px solid #e3e6f0;
            border-left: 4px solid #4e73df;
            transition: all 0.3s ease;
            margin-bottom: 12px;
        }

        .related-item-card:hover {
            box-shadow: 0 0.3rem 0.5rem rgba(0, 0, 0, 0.1);
            border-left-color: #36b9cc;
            transform: translateX(2px);
        }

        .related-item-card .card-body {
            padding: 12px 15px;
        }

        .related-item-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .related-item-material {
            font-weight: 600;
            color: #2e59d9;
            word-break: break-word;
        }

        .related-item-status {
            white-space: nowrap;
            flex-shrink: 0;
        }

        .related-item-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
        }

        .info-label {
            font-weight: 500;
            color: #666;
        }

        .info-value {
            color: #333;
            text-align: right;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .related-items-panel {
                position: static !important;
            }
        }

        /* Scrollbar styling */
        .related-items-container::-webkit-scrollbar {
            width: 6px;
        }

        .related-items-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .related-items-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }

        .related-items-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
@endpush
