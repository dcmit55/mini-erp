{{--
    Break Heartbeat — include this partial on any timing page (index/monitor).
    It calls /timing/heartbeat every 30s which:
      1) Runs TimingBreakService server-side (freeze/unfreeze)
      2) Returns break windows so JS can show visual indicators
--}}
<script>
(function () {
    const HEARTBEAT_URL = '{{ route("timing.heartbeat") }}';
    const HEARTBEAT_INTERVAL = 30000; // 30 seconds

    let lastInBreak = false;

    function checkHeartbeat() {
        fetch(HEARTBEAT_URL, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            const now = data.now; // H:i:s from server
            let inBreak = false;

            // Check if current time falls in any break window
            (data.break_windows || []).forEach(shift => {
                (shift.windows || []).forEach(w => {
                    if (now >= w.start && now < w.end) {
                        inBreak = true;
                    }
                });
            });

            // Show/hide break indicator
            const banner = document.getElementById('break-banner');
            if (banner) {
                banner.style.display = inBreak ? 'block' : 'none';
            }

            // If state changed, reload active sessions to reflect freeze/unfreeze
            if (inBreak !== lastInBreak) {
                lastInBreak = inBreak;
                // Trigger the page's own reload function if it exists
                if (typeof loadActiveSessions === 'function') loadActiveSessions();
                if (typeof refreshData === 'function') refreshData();
            }
        })
        .catch(() => {}); // silent
    }

    // Run immediately on page load, then every 30s
    checkHeartbeat();
    setInterval(checkHeartbeat, HEARTBEAT_INTERVAL);
})();
</script>

{{-- Break banner (hidden by default) --}}
<div id="break-banner" class="alert alert-info text-center fw-bold py-2 mb-0 rounded-0 position-sticky" style="top:0;z-index:1050;display:none;">
    <i class="bi bi-cup-hot me-2"></i>BREAK TIME — Timing sessions auto-paused
</div>
