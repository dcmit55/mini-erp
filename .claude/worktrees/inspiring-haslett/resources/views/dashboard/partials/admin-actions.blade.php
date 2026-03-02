<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0 py-3">
                <h5 class="card-title mb-0 fw-bold text-danger">
                    <i class="fas fa-tools me-2"></i> System Administration
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-2">
                    <div class="col-md-2 col-sm-4 col-6">
                        <a href="{{ url('/log-viewer') }}" target="_blank" class="btn btn-outline-dark w-100">
                            <i class="bi bi-journal-text d-block mb-1"></i>
                            <small>Log Viewer</small>
                        </a>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-brand w-100 artisan-action" data-action="storage-link">
                            <i class="bi bi-link d-block mb-1"></i>
                            <small>Storage Link</small>
                        </button>
                    </div>
                </div>
                <div class="row g-2">
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-danger w-100 artisan-action" data-action="clear-cache">
                            <i class="bi bi-trash d-block mb-1"></i>
                            <small>Clear Cache</small>
                        </button>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-warning w-100 artisan-action" data-action="config-clear">
                            <i class="bi bi-gear d-block mb-1"></i>
                            <small>Clear Config</small>
                        </button>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-success w-100 artisan-action" data-action="config-cache">
                            <i class="bi bi-gear-fill d-block mb-1"></i>
                            <small>Cache Config</small>
                        </button>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-info w-100 artisan-action" data-action="optimize">
                            <i class="bi bi-lightning d-block mb-1"></i>
                            <small>Optimize</small>
                        </button>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <button class="btn btn-outline-secondary w-100 artisan-action" data-action="optimize-clear">
                            <i class="bi bi-lightning-fill d-block mb-1"></i>
                            <small>Clear Optimize</small>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>