{{--
    Department Badge Component

    Menampilkan department/type of project dengan badge berwarna

    Usage:
    @include('components.department-badge', ['department' => $project->department])
--}}

@php
    // Mapping department ke warna badge
    $departmentColors = [
        'Animatronic' => 'bg-orangey',
        'marketing' => 'bg-success',
        'sales' => 'bg-info',
        'logistics' => 'bg-warning text-dark',
        'finance' => 'bg-danger',
        'hr' => 'bg-secondary',
        'it' => 'bg-dark',
        'mascot' => 'bg-purple',
        'r&d' => 'bg-teal',
        '' => 'bg-orange',
    ];

    $department = $department ?? '';
    $departmentLower = strtolower(trim($department));

    // Cari warna berdasarkan keyword
    $badgeClass = 'bg-secondary'; // Default
    foreach ($departmentColors as $keyword => $color) {
        if (str_contains($departmentLower, $keyword)) {
            $badgeClass = $color;
            break;
        }
    }
@endphp

@if (!empty($department))
    <span class="badge {{ $badgeClass }} rounded-pill">
        {{ $department }}
    </span>
@else
    <span class="text-muted">-</span>
@endif
