@extends('layouts.app')

@section('content')
    @php
        // wip_photo_url uses asset('storage/') for local paths — works offline, no API proxy needed
        $wipJobs = $jobOrders->filter(fn($jo) => $jo->hasWipPhotos() && $jo->wip_photo_url);
        $designJobs = $jobOrders->filter(
            fn($jo) => !empty($jo->project_images) || !empty($jo->latest_designs) || !empty($jo->final_images),
        );
    @endphp

    <div class="container-fluid mt-4">
        {{-- Header --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-3 gap-3">
            <div class="d-flex align-items-center gap-2">
                <a href="{{ route('job-orders.index') }}" class="btn btn-sm btn-outline-secondary px-3">
                    <i class="fas fa-arrow-left me-1"></i><span class="d-none d-sm-inline">Back</span>
                </a>
                <div>
                    <h5 class="mb-0 fw-semibold" style="color:#4A25AA;">Job Order Gallery</h5>
                    <small class="text-muted">Visual stream of all production assets</small>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap align-items-center">
                <form action="{{ route('job-orders.gallery.index') }}" method="GET" class="d-flex gap-2" id="filterForm">
                    <select name="project" class="form-select form-select-sm select2" onchange="this.form.submit()"
                        style="min-width:180px;">
                        <option value="">All Projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" {{ request('project') == $project->id ? 'selected' : '' }}>
                                {{ $project->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @can('production.jo.edit')
                    <form action="{{ route('job-orders.sync.lark') }}" method="POST" class="d-inline" id="syncLarkForm">
                        @csrf
                        <button type="button" class="btn btn-sm btn-info rounded-pill px-3" id="btnSyncLark">
                            <i class="fas fa-sync me-1" id="syncIcon"></i>
                            <span id="syncText">Sync from Lark</span>
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        {{-- Tab Navigation --}}
        <div class="d-flex gap-2 mb-4 border-bottom pb-2">
            <button class="btn btn-sm btn-purple rounded-pill px-3 tab-btn active-tab" id="btn-wip"
                onclick="switchTab('wip')">
                <i class="bi bi-camera-fill me-1"></i> WIP Photos
                <span class="badge bg-white text-purple ms-1">{{ $wipJobs->count() }}</span>
            </button>
            <button class="btn btn-sm btn-outline-purple rounded-pill px-3 tab-btn" id="btn-design"
                onclick="switchTab('design')">
                <i class="bi bi-palette-fill me-1"></i> Design & Final
                <span class="badge bg-purple text-white ms-1">{{ $designJobs->count() }}</span>
            </button>
        </div>

        {{-- Alerts --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ===================== WIP SECTION ===================== --}}
        <div id="section-wip">
            @if ($wipJobs->count() > 0)
                <div class="row g-3">
                    @foreach ($wipJobs as $jo)
                        <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                            <div class="gallery-card shadow-sm rounded-3 overflow-hidden">
                                <div class="gallery-photo-wrap">
                                    <a href="{{ $jo->wip_photo_url }}" data-fancybox="wip-gallery"
                                        data-caption="<strong>{{ e($jo->name) }}</strong><br><span class='text-muted small'>WIP | {{ e($jo->project->name ?? 'No Project') }}</span>
@if ($jo->description)
<br><p class='mt-2 mb-0'>{{ e(Str::limit($jo->description, 120)) }}</p>
@endif">
                                        <img src="{{ $jo->wip_photo_url }}" class="gallery-photo"
                                            alt="{{ $jo->name }}" loading="lazy">
                                        <div class="gallery-overlay">
                                            <span class="badge bg-warning text-dark x-small">WIP</span>
                                        </div>
                                    </a>
                                </div>
                                <div class="gallery-desc p-2">
                                    <div class="fw-semibold small text-truncate" title="{{ $jo->name }}">
                                        {{ $jo->name }}</div>
                                    <div class="text-muted x-small text-truncate">{{ $jo->project->name ?? '—' }}</div>
                                    @if ($jo->description)
                                        <div class="text-muted x-small mt-1 desc-clamp">{{ $jo->description }}</div>
                                    @endif
                                    @if ($jo->status)
                                        <span
                                            class="badge rounded-pill mt-1 status-badge-{{ Str::slug($jo->status) }} x-small">{{ $jo->status }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-camera text-muted" style="font-size:4rem;opacity:.2;"></i>
                    <p class="text-muted mt-3">Belum ada WIP photo. Sync dari Lark untuk memuat foto.</p>
                </div>
            @endif
        </div>

        {{-- ===================== DESIGN & FINAL SECTION ===================== --}}
        <div id="section-design" style="display:none;">
            @if ($designJobs->count() > 0)
                @foreach ($designJobs as $jo)
                    @php
                        $designPhotos = array_merge(
                            array_map(
                                fn($p) => ['path' => $p, 'type' => 'Project Image', 'label' => 'project'],
                                $jo->project_images ?? [],
                            ),
                            array_map(
                                fn($p) => ['path' => $p, 'type' => 'Latest Design', 'label' => 'design'],
                                $jo->latest_designs ?? [],
                            ),
                            array_map(
                                fn($p) => ['path' => $p, 'type' => 'Final Image', 'label' => 'final'],
                                $jo->final_images ?? [],
                            ),
                        );
                    @endphp
                    @if (count($designPhotos) > 0)
                        {{-- Group header per job order --}}
                        <div class="d-flex align-items-center gap-2 mb-2 mt-4">
                            <span class="fw-semibold small">{{ $jo->name }}</span>
                            <span class="text-muted x-small">· {{ $jo->project->name ?? '—' }}</span>
                            @if ($jo->status)
                                <span
                                    class="badge rounded-pill x-small status-badge-{{ Str::slug($jo->status) }}">{{ $jo->status }}</span>
                            @endif
                        </div>
                        <div class="row g-2 mb-2">
                            @foreach ($designPhotos as $idx => $photo)
                                @php $url = asset('storage/' . $photo['path']); @endphp
                                <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                                    <div class="gallery-card shadow-sm rounded-3 overflow-hidden">
                                        <div class="gallery-photo-wrap">
                                            <a href="{{ $url }}" data-fancybox="design-{{ $jo->id }}"
                                                data-caption="<strong>{{ e($jo->name) }}</strong><br><span class='text-muted small'>{{ e($photo['type']) }} | {{ e($jo->project->name ?? 'No Project') }}</span>
@if ($jo->description)
<br><p class='mt-2 mb-0'>{{ e(Str::limit($jo->description, 120)) }}</p>
@endif">
                                                <img src="{{ $url }}" class="gallery-photo"
                                                    alt="{{ $jo->name }}" loading="lazy">
                                                <div class="gallery-overlay">
                                                    <span
                                                        class="badge bg-light text-dark x-small">{{ $photo['type'] }}</span>
                                                </div>
                                            </a>
                                        </div>
                                        <div class="gallery-desc p-2">
                                            <div class="fw-semibold small text-truncate" title="{{ $jo->name }}">
                                                {{ $jo->name }}</div>
                                            <div class="text-muted x-small text-truncate">{{ $jo->project->name ?? '—' }}
                                            </div>
                                            <div class="text-muted x-small mt-1 text-truncate">{{ $photo['type'] }}</div>
                                            @if ($jo->description && $idx === 0)
                                                <div class="text-muted x-small mt-1 desc-clamp">{{ $jo->description }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if (!$loop->last)
                            <hr class="my-2 opacity-25">
                        @endif
                    @endif
                @endforeach
            @else
                <div class="text-center py-5">
                    <i class="bi bi-palette text-muted" style="font-size:4rem;opacity:.2;"></i>
                    <p class="text-muted mt-3">Belum ada design/final image. Sync dari Lark untuk memuat foto.</p>
                </div>
            @endif
        </div>
    </div>

    <style>
        .btn-purple {
            background-color: #4A25AA;
            border-color: #4A25AA;
            color: #fff;
        }

        .btn-purple:hover {
            background-color: #3a1d88;
            color: #fff;
        }

        .btn-outline-purple {
            border-color: #4A25AA;
            color: #4A25AA;
        }

        .btn-outline-purple:hover {
            background-color: #4A25AA;
            color: #fff;
        }

        .bg-purple {
            background-color: #4A25AA !important;
        }

        .text-purple {
            color: #4A25AA !important;
        }

        .gallery-card {
            background: #fff;
            cursor: pointer;
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .12) !important;
        }

        .gallery-photo-wrap {
            position: relative;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            background: #f0f0f0;
        }

        .gallery-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: transform .3s ease;
        }

        .gallery-card:hover .gallery-photo {
            transform: scale(1.05);
        }

        .gallery-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, .55) 0%, transparent 45%);
            opacity: 0;
            transition: opacity .25s ease;
            display: flex;
            align-items: flex-end;
            padding: .5rem;
        }

        .gallery-card:hover .gallery-overlay {
            opacity: 1;
        }

        .gallery-desc {
            border-top: 1px solid #f0f0f0;
        }

        .desc-clamp {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .x-small {
            font-size: .68rem;
        }

        /* Status badge colours */
        .status-badge-on-progress {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-badge-delivered {
            background: #d4edda;
            color: #155724;
        }

        .status-badge-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }

        [data-bs-theme="dark"] .gallery-card {
            background: #1e1e2d;
        }

        [data-bs-theme="dark"] .gallery-desc {
            border-color: #2d2d3d;
        }
    </style>

@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                theme: 'bootstrap-5',
                placeholder: 'Filter by Project'
            });

            document.getElementById('btnSyncLark')?.addEventListener('click', function() {
                if (confirm(
                        'Sync all job orders from Lark?\n\nThis will download latest images and data. Continue?'
                        )) {
                    this.disabled = true;
                    document.getElementById('syncIcon').classList.add('fa-spin');
                    document.getElementById('syncText').textContent = 'Syncing...';
                    document.getElementById('syncLarkForm').submit();
                }
            });
        });

        function switchTab(tab) {
            document.getElementById('section-wip').style.display = (tab === 'wip') ? '' : 'none';
            document.getElementById('section-design').style.display = (tab === 'design') ? '' : 'none';
            document.getElementById('btn-wip').className = 'btn btn-sm rounded-pill px-3 tab-btn ' + (tab === 'wip' ?
                'btn-purple active-tab' : 'btn-outline-purple');
            document.getElementById('btn-design').className = 'btn btn-sm rounded-pill px-3 tab-btn ' + (tab === 'design' ?
                'btn-purple active-tab' : 'btn-outline-purple');
        }
    </script>
@endpush
