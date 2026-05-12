{{--
    Partial view: costing project card grid + pagination.
    Used by: finance.costing.index (full page) AND ProjectCostingController::ajaxSearch (AJAX).
    Variables: $projects (LengthAwarePaginator), $cardSummaries (array)
--}}
@if ($projects->isEmpty())
    <div class="empty-state">
        <i class="fas fa-inbox d-block"></i>
        <p class="mb-0 fw-semibold">No projects found</p>
        <small>Try adjusting your filters or <a href="{{ route('costing.report') }}">reset</a> them.</small>
    </div>
@else
    <div class="row g-4">
        @foreach ($projects as $project)
            @php
                // ── Dept badge ──
                $typeDept = $project->type_dept ?? '';
                $deptSlug = strtolower($typeDept);
                $badgeClass = match (true) {
                    str_contains($deptSlug, 'mascot') => 'mascot',
                    str_contains($deptSlug, 'costume') => 'costume',
                    str_contains($deptSlug, 'animatronic') => 'animatronic',
                    str_contains($deptSlug, 'plush') => 'plush',
                    default => 'default',
                };
                $bgClass = $badgeClass . '-bg';
                $deptEmoji = match ($badgeClass) {
                    'mascot' => '🦊',
                    'costume' => '⚔️',
                    'animatronic' => '🤖',
                    'plush' => '🧸',
                    default => '🏢',
                };

                $larkFolder = !empty($typeDept) ? "Lark · {$typeDept} Folder" : 'Lark';

                $jobOrderCount = $project->jobOrders->count();
                $salesName = $project->sales ?? '-';
                $deadline = $project->deadline ? \Carbon\Carbon::parse($project->deadline)->format('d M Y') : '-';

                // ── Summary data from controller ──
                $summary = $cardSummaries[$project->id] ?? [];
                $intlPo = $summary['intl_po'] ?? 0;
                $localPo = $summary['local_po'] ?? 0;
                $usageIdr = $summary['usage_idr'] ?? 0;
                $totalHours = $summary['total_hours'] ?? 0;
                $materialCost = $summary['material_cost'] ?? 0;
                $workmanshipCost = $summary['workmanship_cost'] ?? 0;
                $freightCost = $summary['freight_cost'] ?? 0;

                $sellingPrice = $intlPo + $localPo;
                $actualCost = $summary['actual_project_cost'] ?? 0;
                $profit = $sellingPrice - $actualCost;
                $profitPct = $sellingPrice > 0 ? round(($profit / $sellingPrice) * 100, 1) : null;
                $hasData = $sellingPrice > 0 || $actualCost > 0;

                $fmt = fn($n) => 'Rp ' . number_format($n, 0, ',', '.');
                $fmtK = function ($n) {
                    if ($n >= 1_000_000) {
                        return 'Rp ' . number_format($n / 1_000_000, 1) . 'M';
                    }
                    if ($n >= 1_000) {
                        return 'Rp ' . number_format($n / 1_000, 0) . 'k';
                    }
                    return 'Rp ' . number_format($n, 0);
                };

                // Build flat list of ALL photos across all JOs in this project
                // Each entry: ['url' => ..., 'jo_name' => ...']
$allSlides = [];
foreach ($project->jobOrders as $jo) {
    $photos = $jo->wip_photos ?? [];
    foreach ($photos as $p) {
        if ($p) {
            // If already a full URL (Lark tmp_url), use directly.
            // Otherwise treat as a local storage path (legacy downloaded files).
            $photoUrl =
                str_starts_with($p, 'http://') || str_starts_with($p, 'https://')
                    ? $p
                    : asset('storage/' . $p);
            $allSlides[] = ['url' => $photoUrl, 'jo_name' => $jo->name];
        }
    }
}
$hasSlides = count($allSlides) > 0;

$carouselId = 'joCarousel-' . $project->id;
$isWip = str_contains($project->project_status ?? '', 'WIP');
            @endphp

            <div class="col-xl-4 col-lg-6 col-md-12 col-sm-12">
                <div class="pc-card-wrapper {{ $isWip ? 'pc-wip-wrapper' : '' }}">

                    <a href="{{ route('costing.detail', $project->id) }}"
                        class="project-card {{ $isWip ? 'pc-wip-card' : '' }}">

                        {{-- WIP ribbon badge --}}
                        @if ($isWip)
                            <span class="pc-wip-ribbon" title="Status: {{ $project->project_status }}">
                                <i class="fas fa-hammer me-1"></i>WIP
                            </span>
                        @endif

                        {{-- ══ LEFT: photo panel ══ --}}
                        <div class="pc-photo-panel {{ $bgClass }}">
                            @if ($hasSlides)
                                <div id="{{ $carouselId }}" class="carousel slide pc-jo-carousel"
                                    data-bs-ride="carousel" data-bs-interval="3000">
                                    <div class="carousel-inner">
                                        @foreach ($allSlides as $idx => $slide)
                                            <div class="carousel-item {{ $idx === 0 ? 'active' : '' }}">
                                                <img src="{{ $slide['url'] }}" class="pc-jo-carousel-img"
                                                    alt="{{ e($slide['jo_name']) }}" loading="lazy">
                                                <div class="pc-jo-slide-label">{{ $slide['jo_name'] }}</div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @if (count($allSlides) > 1)
                                        <button class="carousel-control-prev" type="button"
                                            data-bs-target="#{{ $carouselId }}" data-bs-slide="prev">
                                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Previous</span>
                                        </button>
                                        <button class="carousel-control-next" type="button"
                                            data-bs-target="#{{ $carouselId }}" data-bs-slide="next">
                                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                            <span class="visually-hidden">Next</span>
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <div class="pc-photo-panel-inner" style="{{ $hasSlides ? 'z-index:1;' : '' }}">
                                @if (!empty($typeDept))
                                    <span class="pc-cat-badge">{{ $typeDept }}</span>
                                @endif

                                @if (!$hasSlides)
                                    @if (!empty($project->photo))
                                        <img src="{{ asset('storage/' . $project->photo) }}" class="pc-photo-img"
                                            alt="{{ $project->name }}" loading="lazy">
                                    @else
                                        <div class="pc-photo-placeholder">{{ $deptEmoji }}</div>
                                    @endif
                                @endif

                                @if (!empty($project->lark_record_id))
                                    <span class="lark-tag">
                                        <span class="dot"></span>{{ $larkFolder }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- ══ RIGHT: content body ══ --}}
                        <div class="pc-body">
                            <div class="mb-2">
                                <span class="pc-name" title="{{ $project->name }}">
                                    {{ $project->name }}
                                </span>
                            </div>

                            <div class="section-title">ACTUALS</div>
                            <div class="pc-row">
                                <span class="pc-lbl">Actual Project Cost</span>
                                <span class="pc-val">{{ $hasData ? $fmt($actualCost) : '—' }}</span>
                            </div>
                            <div class="pc-row">
                                <span class="pc-lbl">Total Timing Cost</span>
                                <span class="pc-val">{{ $workmanshipCost > 0 ? $fmt($workmanshipCost) : '—' }}</span>
                            </div>
                            <div class="pc-row">
                                <span class="pc-lbl">Total Project Time</span>
                                <span class="pc-val">{{ $totalHours > 0 ? $totalHours . ' hrs' : '—' }}</span>
                            </div>

                            <div class="stats-strip">
                                <div class="stats-box bg-warning-subtle text-warning-emphasis">
                                    <div class="label">INT'L PO</div>
                                    <div class="value">{{ $intlPo > 0 ? $fmtK($intlPo) : '—' }}</div>
                                </div>
                                <div class="stats-box bg-success-subtle text-success-emphasis">
                                    <div class="label">LOCAL PO</div>
                                    <div class="value">{{ $localPo > 0 ? $fmtK($localPo) : '—' }}</div>
                                </div>
                                <div class="stats-box bg-primary-subtle text-primary-emphasis">
                                    <div class="label">USAGE</div>
                                    <div class="value">{{ $usageIdr > 0 ? $fmtK($usageIdr) : '—' }}</div>
                                </div>
                            </div>
                        </div>{{-- /pc-body --}}
                    </a>{{-- /project-card --}}

                    {{-- ══ VIEW PHOTOS BUTTON + Hidden Fancybox gallery links ══ --}}
                    @if ($hasSlides)
                        <div style="display:none;" aria-hidden="true">
                            @foreach ($allSlides as $slide)
                                <a href="{{ $slide['url'] }}" data-fancybox="costing-gallery-{{ $project->id }}"
                                    data-caption="{{ e($slide['jo_name']) }} — {{ e($project->name) }}"
                                    class="pc-gallery-anchor"></a>
                            @endforeach
                        </div>
                        <button type="button" class="pc-view-photos-btn btn-open-gallery"
                            data-project-id="{{ $project->id }}" data-total="{{ count($allSlides) }}">
                            <i class="bi bi-images"></i>
                            {{ count($allSlides) }} Photo{{ count($allSlides) > 1 ? 's' : '' }}
                        </button>
                    @elseif (!empty($project->photo))
                        <a href="{{ asset('storage/' . $project->photo) }}" data-fancybox
                            data-caption="{{ e($project->name) }}" class="pc-view-photos-btn">
                            <i class="bi bi-image"></i> View Photo
                        </a>
                    @endif

                </div>{{-- /pc-card-wrapper --}}
            </div>{{-- /col --}}
        @endforeach
    </div>{{-- /row --}}

    {{-- ── Pagination ── --}}
    <div class="d-flex justify-content-between align-items-center mt-4" id="costing-pagination">
        <div class="text-muted small">
            Showing {{ $projects->firstItem() ?? 0 }}–{{ $projects->lastItem() ?? 0 }}
            of {{ $projects->total() }} projects
        </div>
        <nav aria-label="Page navigation">
            {{ $projects->appends(request()->query())->onEachSide(1)->links('pagination::bootstrap-5') }}
        </nav>
    </div>
@endif
