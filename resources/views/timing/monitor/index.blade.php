@extends('layouts.app')

@section('content')
    <div class="container-fluid py-3">
        <!-- Header dengan button navigasi -->
        <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
            <div class="d-flex align-items-center">
                <i class="fas fa-tv gradient-icon me-2" style="font-size: 1.5rem;"></i>
                <h2 class="mb-0" style="font-size:1.2rem;"> Timing Monitor - Running Sessions</h2>
            </div>
            <div class="ms-lg-auto d-flex gap-2 flex-wrap">
                <button id="available-employees-btn" class="btn btn-success btn-sm">
                    <i class="bi bi-people me-1"></i> Available Employees
                </button>
                <button id="refresh-btn" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                </button>
                <a href="{{ route('costume-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-cut me-1"></i> Costume Timing
                </a>
                <a href="{{ route('animatronics-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-robot me-1"></i> Animatronics
                </a>
                <a href="{{ route('mascot-timing.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-masks-theater me-1"></i> Mascot Timing
                </a>
            </div>
        </div>

        <!-- Statistics Cards - SEMUA SAMA LEBAR -->
        <div class="row g-2 mb-3">
            <div class="col">
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 small">Total Running</h6>
                                <h2 class="mb-0 fw-bold" id="total-running">{{ $totalRunning }}</h2>
                            </div>
                            <i class="fas fa-play-circle fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-success text-white">
                    <div class="card-body py-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-0 small">Active Employees</h6>
                                <h2 class="mb-0 fw-bold" id="total-employees">{{ $totalEmployees }}</h2>
                            </div>
                            <i class="fas fa-users fa-2x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-info text-white">
                    <div class="card-body py-2">
                        <div class="text-center">
                            <small class="d-block">Costume</small>
                            <h2 class="mb-0 fw-bold" id="costume-running">{{ $costumeRunning }}</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-warning text-dark">
                    <div class="card-body py-2">
                        <div class="text-center">
                            <small class="d-block">Animatronics</small>
                            <h2 class="mb-0 fw-bold" id="animatronics-running">{{ $animatronicsRunning }}</h2>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card shadow-sm border-0 bg-danger text-white">
                    <div class="card-body py-2">
                        <div class="text-center">
                            <small class="d-block">Mascot</small>
                            <h2 class="mb-0 fw-bold" id="mascot-running">{{ $mascotRunning }}</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Session Type Summary -->
        <div class="row g-2 mb-3">
            <div class="col-md-4">
                <div class="card shadow-sm" style="background-color:#E8F5E9; border-top:3px solid #4CAF50;">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">📦 Mass Production</div>
                            <small class="text-muted" style="font-size: 10px;">Produksi massal</small>
                        </div>
                        <h3 class="mb-0 fw-bold" style="color:#4CAF50;">{{ $totalMassProduction }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm" style="background-color:#FFF3E0; border-top:3px solid #F59E0B;">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">🔬 Sample</div>
                            <small class="text-muted" style="font-size: 10px;">Produksi sampel</small>
                        </div>
                        <h3 class="mb-0 fw-bold" style="color:#F59E0B;">{{ $totalSample }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow-sm" style="background-color:#FEE2E2; border-top:3px solid #DC2626;">
                    <div class="card-body py-2 px-3 d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold small">🔧 Repair / Rework</div>
                            <small class="text-muted" style="font-size: 10px;">Perbaikan</small>
                        </div>
                        <h3 class="mb-0 fw-bold" style="color:#DC2626;">{{ $totalRepair }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Running Sessions by Department -->
        @if ($runningSessions->count() > 0)
            @foreach ($runningSessions as $departmentName => $sessions)
                @php
                    $deptBorderColor = match (true) {
                        stripos($departmentName, 'Costume') !== false => '#4facfe',
                        stripos($departmentName, 'Animatronic') !== false ||
                            stripos($departmentName, 'Animation') !== false
                            => '#ff6b6b',
                        stripos($departmentName, 'Mascot') !== false => '#f9d423',
                        default => '#667eea',
                    };
                    $isCostumeDept = stripos($departmentName, 'Costume') !== false || stripos($departmentName, 'Sewing') !== false;
                @endphp
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-white py-2 border-bottom"
                        style="border-left: 4px solid {{ $deptBorderColor }}; border-radius: 8px 8px 0 0;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-building text-secondary"></i>
                            <h6 class="mb-0 fw-semibold">{{ $departmentName }}</h6>
                            <span class="badge bg-secondary">{{ $sessions->count() }} Running</span>
                        </div>
                    </div>
                    <div class="card-body p-2">
                        @if($isCostumeDept)
                            {{-- Costume: station sections vertikal (Office → Cutting → Sewing → Finishing) --}}
                            @php
                                $stationDefs  = [
                                    'office'    => ['label' => 'Office',    'color' => '#6c8ebf'],
                                    'cutting'   => ['label' => 'Cutting',   'color' => '#b8860b'],
                                    'sewing'    => ['label' => 'Sewing',    'color' => '#2d7a4f'],
                                    'finishing' => ['label' => 'Finishing', 'color' => '#0891b2'],
                                ];
                                $byStation    = $sessions->groupBy('station');
                                $assignedKeys = ['office', 'cutting', 'sewing', 'finishing'];
                                $unassigned   = $sessions->filter(fn($s) => !in_array($s->station, $assignedKeys));
                                $isFirstSt    = true;
                            @endphp

                            @if($sessions->isEmpty())
                                <div class="text-center text-muted py-3" style="font-size:12px;">Tidak ada sesi berjalan</div>
                            @else
                                @foreach($stationDefs as $stKey => $stInfo)
                                    @php $stSessions = $byStation->get($stKey, collect()); @endphp
                                    @if($stSessions->isNotEmpty())
                                        <div class="{{ $isFirstSt ? '' : 'mt-3' }} mb-1">
                                            <div class="d-flex align-items-center gap-2 mb-2 pb-1" style="border-bottom:2px solid {{ $stInfo['color'] }}30;">
                                                <span class="fw-semibold px-2 py-0" style="font-size:.75rem; color:{{ $stInfo['color'] }}; background:{{ $stInfo['color'] }}15; border-radius:4px; border-left:3px solid {{ $stInfo['color'] }};">{{ $stInfo['label'] }}</span>
                                                <span class="text-muted" style="font-size:.7rem;">{{ $stSessions->count() }} person(s)</span>
                                            </div>
                                            <div class="row g-2">
                                                @foreach($stSessions as $session)
                                                    @php
                                                        $sessionType = $session->session_type ?? 'mass_production';
                                                        $isSample    = $sessionType === 'sample';
                                                        $isRepair    = $sessionType === 'repair';
                                                        if ($isSample) { $cardBg = '#FFF3E0'; $borderColor = '#F59E0B'; $badgeText = 'Sample'; $badgeBg = '#F59E0B'; }
                                                        elseif ($isRepair) { $cardBg = '#FEE2E2'; $borderColor = '#DC2626'; $badgeText = 'Repair'; $badgeBg = '#DC2626'; }
                                                        else { $cardBg = '#E8F5E9'; $borderColor = '#4CAF50'; $badgeText = 'Production'; $badgeBg = '#4CAF50'; }
                                                    @endphp
                                                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6" id="session-{{ $session->id }}">
                                                        <div class="card shadow-sm w-100" style="background:{{ $cardBg }}; border-top:3px solid {{ $borderColor }}; border-radius:8px;">
                                                            <div class="card-body p-2 d-flex flex-column">
                                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                                    <span class="badge px-2 py-1" style="background:{{ $badgeBg }}; color:white; font-size:8px;">{{ $badgeText }}</span>
                                                                    <span class="text-muted" style="font-size:8px;"><i class="bi bi-clock"></i> {{ $session->start_time }}</span>
                                                                </div>
                                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                                    <div class="flex-shrink-0">
                                                                        @if ($session->employee->photo)
                                                                            <img src="{{ asset('storage/' . $session->employee->photo) }}" class="rounded-circle" width="44" height="44" style="object-fit:cover; border:2px solid {{ $borderColor }};">
                                                                        @else
                                                                            <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px; height:44px; background:{{ $borderColor }}20;"><i class="bi bi-person text-secondary"></i></div>
                                                                        @endif
                                                                    </div>
                                                                    <div class="flex-grow-1" style="min-width:0;">
                                                                        <div class="fw-semibold text-truncate" style="font-size:.78rem;">{{ $session->employee->name ?? 'Unknown' }}</div>
                                                                        <div class="text-muted text-truncate" style="font-size:8px;">{{ $session->employee->position ?? 'N/A' }}</div>
                                                                    </div>
                                                                </div>
                                                                <div class="text-center mb-1 py-1 bg-white bg-opacity-60 rounded">
                                                                    <span class="duration-display fw-bold font-monospace" style="font-size:13px; color:{{ $borderColor }};" data-start-time="{{ $session->start_time }}">{{ $session->duration }}</span>
                                                                </div>
                                                                <div style="font-size:8px; word-break:break-word; overflow-wrap:break-word;">
                                                                    <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">JO:</span><span class="text-end" style="max-width:65%;">{{ $session->jobOrder->name ?? 'N/A' }}</span></div>
                                                                    <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Step:</span><span style="max-width:65%;">{{ $session->step }}</span></div>
                                                                    <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Project:</span><span style="max-width:65%;">{{ $session->jobOrder->project->name ?? 'N/A' }}</span></div>
                                                                    <div class="d-flex justify-content-between"><span class="text-muted flex-shrink-0">Part:</span><span style="max-width:65%;">{{ $session->parts }}</span></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                        @php $isFirstSt = false; @endphp
                                    @endif
                                @endforeach

                                @if($unassigned->isNotEmpty())
                                    <div class="{{ $isFirstSt ? '' : 'mt-3' }} mb-1">
                                        <div class="d-flex align-items-center gap-2 mb-2 pb-1" style="border-bottom:2px solid #dee2e6;">
                                            <span class="fw-semibold px-2 py-0" style="font-size:.75rem; color:#6c757d; background:#f1f3f5; border-radius:4px; border-left:3px solid #adb5bd;">Unassigned</span>
                                            <span class="text-muted" style="font-size:.7rem;">{{ $unassigned->count() }} person(s)</span>
                                        </div>
                                        <div class="row g-2">
                                            @foreach($unassigned as $session)
                                                @php
                                                    $isRepair    = ($session->session_type ?? 'mass_production') === 'repair';
                                                    $cardBg      = $isRepair ? '#FEF3E8' : '#E8F5E9';
                                                    $borderColor = $isRepair ? '#F59E0B' : '#4CAF50';
                                                    $badgeText   = $isRepair ? 'Repair' : 'Production';
                                                    $badgeBg     = $isRepair ? '#F59E0B' : '#4CAF50';
                                                @endphp
                                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6" id="session-{{ $session->id }}">
                                                    <div class="card shadow-sm w-100" style="background:{{ $cardBg }}; border-top:3px solid {{ $borderColor }}; border-radius:8px;">
                                                        <div class="card-body p-2 d-flex flex-column">
                                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                                <span class="badge px-2 py-1" style="background:{{ $badgeBg }}; color:white; font-size:8px;">{{ $badgeText }}</span>
                                                                <span class="text-muted" style="font-size:8px;"><i class="bi bi-clock"></i> {{ $session->start_time }}</span>
                                                            </div>
                                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                                <div class="flex-shrink-0">
                                                                    @if ($session->employee->photo)
                                                                        <img src="{{ asset('storage/' . $session->employee->photo) }}" class="rounded-circle" width="44" height="44" style="object-fit:cover; border:2px solid {{ $borderColor }};">
                                                                    @else
                                                                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width:44px; height:44px; background:{{ $borderColor }}20;"><i class="bi bi-person text-secondary"></i></div>
                                                                    @endif
                                                                </div>
                                                                <div class="flex-grow-1" style="min-width:0;">
                                                                    <div class="fw-semibold text-truncate" style="font-size:.78rem;">{{ $session->employee->name ?? 'Unknown' }}</div>
                                                                    <div class="text-muted text-truncate" style="font-size:8px;">{{ $session->employee->position ?? 'N/A' }}</div>
                                                                </div>
                                                            </div>
                                                            <div class="text-center mb-1 py-1 bg-white bg-opacity-60 rounded">
                                                                <span class="duration-display fw-bold font-monospace" style="font-size:13px; color:{{ $borderColor }};" data-start-time="{{ $session->start_time }}">{{ $session->duration }}</span>
                                                            </div>
                                                            <div style="font-size:8px; word-break:break-word; overflow-wrap:break-word;">
                                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">JO:</span><span class="text-end" style="max-width:65%;">{{ $session->jobOrder->name ?? 'N/A' }}</span></div>
                                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Step:</span><span style="max-width:65%;">{{ $session->step }}</span></div>
                                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Project:</span><span style="max-width:65%;">{{ $session->jobOrder->project->name ?? 'N/A' }}</span></div>
                                                                <div class="d-flex justify-content-between"><span class="text-muted flex-shrink-0">Part:</span><span style="max-width:65%;">{{ $session->parts }}</span></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            @endif
                        @else
                            {{-- Dept lain: flat grid seperti semula --}}
                            @if($sessions->isEmpty())
                                <div class="text-center text-muted py-3" style="font-size:12px;">Tidak ada sesi berjalan</div>
                            @else

                        <div class="row g-2">
                            @foreach ($sessions as $session)
                                @php
                                    $isRepair = ($session->session_type ?? 'mass_production') === 'repair';
                                    $cardBg = $isRepair ? '#FEF3E8' : '#E8F5E9';
                                    $borderColor = $isRepair ? '#F59E0B' : '#4CAF50';
                                    $badgeText = $isRepair ? 'Repair' : 'Production';
                                    $badgeBg = $isRepair ? '#F59E0B' : '#4CAF50';
                                @endphp
                                <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6 d-flex"
                                    id="session-{{ $session->id }}">
                                    <div class="card w-100 shadow-sm"
                                        style="background: {{ $cardBg }}; border-top: 3px solid {{ $borderColor }}; border-radius: 8px;">
                                        <div class="card-body p-2 d-flex flex-column">
                                            <!-- Badge & Time -->
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge px-2 py-1"
                                                    style="background: {{ $badgeBg }}; color: white; font-size: 9px;">{{ $badgeText }}</span>
                                                <span class="text-muted" style="font-size: 9px;"><i class="bi bi-clock"></i>
                                                    {{ $session->start_time }}</span>
                                            </div>

                                            <!-- Employee Info: Foto di kiri, Nama & Position di kanan -->
                                            <div class="d-flex align-items-center gap-3 mb-3">
                                                <div class="flex-shrink-0">
                                                    @if ($session->employee->photo)
                                                        <img src="{{ asset('storage/' . $session->employee->photo) }}"
                                                            class="rounded-circle" width="60" height="60"
                                                            loading="lazy"
                                                            style="object-fit: cover; border: 2px solid {{ $borderColor }};">
                                                    @else
                                                        <div class="rounded-circle d-flex align-items-center justify-content-center"
                                                            style="width: 60px; height: 60px; background: {{ $borderColor }}20;">
                                                            <i class="bi bi-person text-secondary fs-3"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="flex-grow-1" style="min-width: 0;">
                                                    <div class="fw-semibold small text-truncate">
                                                        {{ $session->employee->name ?? 'Unknown' }}</div>
                                                    <div class="text-muted"
                                                        style="font-size: 9px; word-break: break-word;">
                                                        {{ $session->employee->position ?? 'N/A' }}</div>
                                                </div>
                                            </div>

                                            <!-- Duration -->
                                            <div class="text-center mb-2 py-1 bg-white bg-opacity-60 rounded">
                                                <span class="duration-display fw-bold font-monospace"
                                                    style="font-size: 14px; color: {{ $borderColor }};"
                                                    data-start-time="{{ $session->start_time }}">
                                                    {{ $session->duration }}
                                                </span>
                                            </div>

                                            <!-- Job Info dengan word-wrap -->
                                            <div
                                                style="font-size: 9px; word-break: break-word; overflow-wrap: break-word;">
                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">JO:</span><span class="text-end" style="word-break: break-word; overflow-wrap: break-word; max-width: 65%;">{{ $session->jobOrder->name ?? 'N/A' }}</span></div>
                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Step:</span><span style="word-break: break-word; overflow-wrap: break-word; max-width: 65%;">{{ $session->step }}</span></div>
                                                <div class="d-flex justify-content-between mb-1"><span class="text-muted flex-shrink-0">Project:</span><span style="word-break: break-word; overflow-wrap: break-word; max-width: 65%;">{{ $session->jobOrder->project->name ?? 'N/A' }}</span></div>
                                                <div class="d-flex justify-content-between"><span class="text-muted flex-shrink-0">Part:</span><span style="word-break: break-word; overflow-wrap: break-word; max-width: 65%;">{{ $session->parts }}</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="bi bi-clock-history text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-2">No Running Sessions</h5>
                    <p class="text-muted small">Start a timing session from Costume, Animatronics, or Mascot Timing</p>
                    <div class="d-flex gap-2 justify-content-center mt-2">
                        <a href="{{ route('costume-timing.index') }}" class="btn btn-sm btn-outline-primary">Costume</a>
                        <a href="{{ route('animatronics-timing.index') }}"
                            class="btn btn-sm btn-outline-danger">Animatronics</a>
                        <a href="{{ route('mascot-timing.index') }}" class="btn btn-sm btn-outline-warning">Mascot</a>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Available Employees Modal -->
    <div class="modal fade" id="availableEmployeesModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title"><i class="bi bi-people me-2"></i>Available Employees (Not Running)</h6>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="available-employees-loading" class="text-center py-4">
                        <div class="spinner-border text-primary spinner-border-sm"></div>
                        <p class="mt-2 small">Loading...</p>
                    </div>
                    <div id="available-employees-content" class="d-none"></div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .gradient-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .card {
            transition: transform 0.15s, box-shadow 0.15s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.1) !important;
        }

        .duration-display {
            font-feature-settings: "tnum";
            font-variant-numeric: tabular-nums;
        }

        /* Membuat semua 5 card sama lebar */
        .row.g-2.mb-3 .col {
            flex: 1;
            min-width: 0;
        }

        /* Untuk card body flex column */
        .d-flex.flex-column {
            display: flex !important;
            flex-direction: column !important;
        }

        .w-100 {
            width: 100% !important;
        }

        .flex-grow-1 {
            flex-grow: 1 !important;
        }

        .flex-shrink-0 {
            flex-shrink: 0 !important;
        }

        .text-truncate {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* Untuk teks yang perlu wrap */
        [style*="word-break: break-word"] {
            word-break: break-word;
            overflow-wrap: break-word;
        }
    </style>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            // Duration timer — single RAF loop, updates all cards at once
            function calculateDuration(startTime) {
                try {
                    const today = new Date();
                    const [hours, minutes, seconds] = startTime.split(':');
                    const start = new Date(today.getFullYear(), today.getMonth(), today.getDate(),
                        +hours, +minutes, +seconds);
                    const diffInSeconds = Math.floor((Date.now() - start) / 1000);
                    if (diffInSeconds < 0) return '00:00:00';
                    const h = Math.floor(diffInSeconds / 3600);
                    const m = Math.floor((diffInSeconds % 3600) / 60);
                    const s = diffInSeconds % 60;
                    return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                } catch (e) {
                    return '00:00:00';
                }
            }

            function startDurationTimers() {
                // Use a single interval instead of RAF to avoid 60fps DOM updates
                setInterval(function() {
                    const els = document.querySelectorAll('.duration-display');
                    for (let i = 0; i < els.length; i++) {
                        const st = els[i].dataset.startTime;
                        if (st) els[i].textContent = calculateDuration(st);
                    }
                }, 1000);
            }

            function refreshData() {
                $.ajax({
                    url: '{{ route('timing-monitor.running') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            $('#total-running').text(response.statistics.total_running);
                            $('#total-employees').text(response.statistics.total_employees);
                            $('#costume-running').text(response.statistics.costume_running);
                            $('#animatronics-running').text(response.statistics.animatronics_running);
                            $('#mascot-running').text(response.statistics.mascot_running);
                        }
                    }
                });
            }

            $('#refresh-btn').on('click', function() {
                const btn = $(this);
                btn.prop('disabled', true).html(
                    '<span class="spinner-border spinner-border-sm me-1"></span> Refreshing...');
                setTimeout(() => location.reload(), 500);
            });

            $('#available-employees-btn').on('click', function() {
                $('#availableEmployeesModal').modal('show');
                loadAvailableEmployees();
            });

            function loadAvailableEmployees() {
                $('#available-employees-loading').removeClass('d-none');
                $('#available-employees-content').addClass('d-none');

                $.ajax({
                    url: '{{ route('timing-monitor.available-employees') }}',
                    method: 'GET',
                    success: function(response) {
                        if (response.success) {
                            displayAvailableEmployees(response.employees);
                        }
                    },
                    error: function() {
                        $('#available-employees-loading').html(
                            '<div class="alert alert-danger small">Failed to load</div>');
                    }
                });
            }

            function displayAvailableEmployees(employees) {
                $('#available-employees-loading').addClass('d-none');
                const content = $('#available-employees-content');
                content.removeClass('d-none').empty();

                if (employees.length === 0) {
                    content.html(
                        '<div class="text-center py-4"><i class="bi bi-check-circle text-success fs-2"></i><p class="mt-2 small">All employees are currently running!</p></div>'
                        );
                    return;
                }

                const byDepartment = {};
                employees.forEach(emp => {
                    const dept = emp.department || 'Unknown';
                    if (!byDepartment[dept]) byDepartment[dept] = [];
                    byDepartment[dept].push(emp);
                });

                let html = '';
                Object.keys(byDepartment).sort().forEach(dept => {
                    const emps = byDepartment[dept];
                    html += `<div class="mb-3">
                        <h6 class="border-bottom pb-1 small">${dept} <span class="badge bg-secondary">${emps.length}</span></h6>
                        <div class="row g-2">`;
                    emps.forEach(emp => {
                        const photoHtml = emp.photo ?
                            `<img src="/storage/${emp.photo}" class="rounded-circle" width="50" height="50" style="object-fit: cover;">` :
                            `<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width:50px;height:50px;"><i class="bi bi-person text-white fs-4"></i></div>`;
                        html += `<div class="col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm">
                                <div class="card-body p-3 text-center">
                                    ${photoHtml}
                                    <div class="fw-semibold small mt-2">${emp.name}</div>
                                    <div class="small text-muted" style="font-size: 9px;">${emp.position || 'N/A'}</div>
                                    <span class="badge bg-success mt-1" style="font-size: 8px;">Available</span>
                                </div>
                            </div>
                        </div>`;
                    });
                    html += `</div></div>`;
                });
                content.html(html);
            }

            startDurationTimers();
            setInterval(refreshData, 30000);
        });
    </script>
@endsection
