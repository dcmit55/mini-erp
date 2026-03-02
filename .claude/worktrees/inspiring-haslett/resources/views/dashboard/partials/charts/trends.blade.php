<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent border-0 py-3">
        <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0 fw-bold">Monthly Trends</h5>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-brand dropdown-toggle" type="button" 
                        data-bs-toggle="dropdown" id="trendsFilterBtn">
                    <i class="fas fa-filter me-1"></i> Last 6 Months
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item trends-filter" href="#" data-months="6">Last 6 Months</a></li>
                    <li><a class="dropdown-item trends-filter" href="#" data-months="12">Last Year</a></li>
                    <li><a class="dropdown-item trends-filter" href="#" data-months="3">Last 3 Months</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <canvas id="trendsChart" height="100"></canvas>
    </div>
</div>