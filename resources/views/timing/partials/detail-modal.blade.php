{{-- Shared Detail Modal + JS — include on any timing page --}}
<div class="modal fade" id="timingDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title fw-semibold"><i class="bi bi-eye me-1"></i>Session Detail</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3" id="timingDetailModalBody">
                <div class="text-center py-5 text-muted">
                    <div class="spinner-border spinner-border-sm me-2"></div>Loading...
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    let modalTimerInterval = null;
    let modalStatsInterval = null;

    // Delegate click on detail buttons (works for dynamically loaded content too)
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.detail-modal-btn');
        if (!btn) return;

        const timingId = btn.dataset.timingId;
        if (!timingId) return;

        const modalEl = document.getElementById('timingDetailModal');
        const body    = document.getElementById('timingDetailModalBody');

        // Clear previous intervals
        if (modalTimerInterval) { clearInterval(modalTimerInterval); modalTimerInterval = null; }
        if (modalStatsInterval) { clearInterval(modalStatsInterval); modalStatsInterval = null; }

        // Show loading
        body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border spinner-border-sm me-2"></div>Loading...</div>';

        // Show modal
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();

        // Fetch partial
        fetch('/timing/' + timingId + '/detail-partial', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.text())
        .then(html => {
            body.innerHTML = html;

            // Read data from attributes (innerHTML does not execute scripts)
            const meta = document.getElementById('modal-data');
            if (!meta) return;

            const isRunning   = meta.dataset.isRunning   === '1';
            const isCompleted = meta.dataset.isCompleted === '1';
            const netSeconds  = parseInt(meta.dataset.netSeconds, 10) || 0;
            const statsUrl    = meta.dataset.statsUrl;

            // Live timer
            if (isRunning) {
                let elapsed = netSeconds;
                const pad = n => String(n).padStart(2, '0');
                function tick() {
                    const el = document.getElementById('modal-big-timer');
                    if (!el) { clearInterval(modalTimerInterval); return; }
                    el.textContent = pad(Math.floor(elapsed/3600)) + ':' + pad(Math.floor(elapsed%3600/60)) + ':' + pad(elapsed%60);
                    elapsed++;
                }
                tick();
                modalTimerInterval = setInterval(tick, 1000);
            }

            // Stats polling
            if (!isCompleted && statsUrl) {
                const pad2   = n => String(n).padStart(2, '0');
                const toHHMM = m => pad2(Math.floor(m/60)) + ':' + pad2(m%60);

                function fetchStats() {
                    fetch(statsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(r => r.json())
                        .then(d => {
                            const el = id => document.getElementById(id);
                            if (el('modal-gross'))  el('modal-gross').textContent  = toHHMM(d.gross_minutes);
                            if (el('modal-paused')) el('modal-paused').textContent = d.total_paused > 0 ? d.total_paused + ' min' : '—';
                            if (el('modal-net'))    el('modal-net').textContent    = toHHMM(d.net_active_minutes);
                        })
                        .catch(() => {});
                }
                modalStatsInterval = setInterval(fetchStats, 15000);
            }
        })
        .catch(() => {
            body.innerHTML = '<div class="text-center text-danger py-5"><i class="bi bi-exclamation-triangle fs-3"></i><p class="mt-2">Failed to load detail</p></div>';
        });
    });

    // Cleanup on modal hide
    document.getElementById('timingDetailModal')?.addEventListener('hidden.bs.modal', function () {
        if (modalTimerInterval) { clearInterval(modalTimerInterval); modalTimerInterval = null; }
        if (modalStatsInterval) { clearInterval(modalStatsInterval); modalStatsInterval = null; }
    });
})();
</script>
