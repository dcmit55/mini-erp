{{--
    Project Status Badges Component

    Menampilkan project statuses (multi-select dari Lark) dengan multiple badges berwarna

    Usage:
    @include('components.project-status-badges', ['statuses' => $project->project_status])
--}}

@php
    // Mapping status ke warna badge (sesuai dengan gambar yang Anda berikan)
    $statusColors = [
        // Status positive
        'completed' => 'bg-success',
        'done' => 'bg-success',
        'finished' => 'bg-success',
        'approved' => 'bg-success',

        // Status in progress
        'in progress' => 'bg-primary',
        'ongoing' => 'bg-primary',
        'processing' => 'bg-primary',
        'active' => 'bg-primary',

        // Status pending
        'pending' => 'bg-warning text-dark',
        'waiting' => 'bg-warning text-dark',
        'review' => 'bg-warning text-dark',

        // Status on hold
        'on hold' => 'bg-secondary',
        'paused' => 'bg-secondary',
        'suspended' => 'bg-secondary',

        // Status negative
        'cancelled' => 'bg-danger',
        'rejected' => 'bg-danger',
        'failed' => 'bg-danger',

        // Status info
        'draft' => 'bg-info',
        'new' => 'bg-info',
        'planned' => 'bg-info',
    ];

    $statuses = $statuses ?? '';

    // Split by comma jika multi-value
    $statusArray = array_filter(array_map('trim', explode(',', $statuses)));
@endphp

@if (!empty($statusArray))
    <div class="d-flex flex-wrap gap-1">
        @foreach ($statusArray as $status)
            @php
                $statusLower = strtolower($status);

                // Cari warna berdasarkan keyword
                $badgeClass = 'bg-secondary'; // Default
                foreach ($statusColors as $keyword => $color) {
                    if (str_contains($statusLower, $keyword) || $statusLower === $keyword) {
                        $badgeClass = $color;
                        break;
                    }
                }
            @endphp

            <span class="badge {{ $badgeClass }} rounded-pill">
                {{ $status }}
            </span>
        @endforeach
    </div>
@else
    <span class="text-muted">-</span>
@endif
