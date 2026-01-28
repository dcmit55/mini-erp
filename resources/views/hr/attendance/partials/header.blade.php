<div class="row mb-4 align-items-center header-flex">
    <div class="col-md-6">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-calendar-check gradient-icon me-2" style="font-size: 1.5rem;"></i>
            <div>
                <h5 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Daily Attendance</h5>
                <p class="text-muted mb-0 small">Daily employee attendance management</p>
            </div>
        </div>
    </div>
    <div class="col-md-6 text-md-end mt-3 mt-md-0">
        <div class="d-flex flex-wrap justify-content-md-end gap-2 header-time-cards">
            <!-- Button to List -->
            <a href="{{ route('attendance.list') }}" class="btn btn-outline-primary btn-sm shadow-sm">
                <i class="bi bi-list-ul me-1"></i> Attendance List
            </a>

            <!-- Current Time Display -->
            <div class="d-flex align-items-center bg-light border rounded px-3 py-1 shadow-sm"
                style="min-width: 180px;">
                <i class="bi bi-clock text-primary me-2"></i>
                <div class="d-flex flex-column">
                    <small class="text-muted mb-0" style="font-size: 0.7rem; line-height: 1;">Current
                        Time</small>
                    <strong id="current-time"
                        style="font-size: 0.95rem; line-height: 1.2;">{{ now()->format('H:i:s') }}</strong>
                </div>
            </div>

            <!-- Current Date Display -->
            <div class="d-flex align-items-center bg-primary text-white rounded px-3 py-1 shadow-sm"
                style="min-width: 180px;">
                <i class="bi bi-calendar3 me-2"></i>
                <div class="d-flex flex-column">
                    <small class="mb-0" style="font-size: 0.7rem; line-height: 1; opacity: 0.9;">Today's
                        Date</small>
                    <strong
                        style="font-size: 0.95rem; line-height: 1.2;">{{ now()->format('d M Y') }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>