@extends('layouts.app')

@php
if (!function_exists('roleColor')) {
    function roleColor(string $role): string {
        return match(true) {
            str_contains($role, 'super')       => '#6f42c1',
            str_contains($role, 'hr')          => '#0d6efd',
            str_contains($role, 'finance')     => '#198754',
            str_contains($role, 'logistic')    => '#fd7e14',
            str_contains($role, 'procurement') => '#20c997',
            str_contains($role, 'mascot')      => '#e83e8c',
            str_contains($role, 'costume')     => '#d63384',
            str_contains($role, 'animatronic') => '#6610f2',
            str_contains($role, 'timing')      => '#0dcaf0',
            str_contains($role, 'general')     => '#6c757d',
            default                            => '#495057',
        };
    }
}

if (!function_exists('roleIcon')) {
    function roleIcon(string $role): string {
        return match(true) {
            str_contains($role, 'super')       => 'fa-crown',
            str_contains($role, 'hr')          => 'fa-users',
            str_contains($role, 'finance')     => 'fa-coins',
            str_contains($role, 'logistic')    => 'fa-boxes',
            str_contains($role, 'procurement') => 'fa-file-invoice',
            str_contains($role, 'mascot')      => 'fa-star',
            str_contains($role, 'costume')     => 'fa-tshirt',
            str_contains($role, 'animatronic') => 'fa-robot',
            str_contains($role, 'timing')      => 'fa-stopwatch',
            str_contains($role, 'general')     => 'fa-user',
            default                            => 'fa-shield-alt',
        };
    }
}
@endphp

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm rounded">
        <div class="card-body">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-shield-alt gradient-icon" style="font-size:1.3rem;"></i>
                    <h2 class="mb-0" style="font-size:1.2rem;">Role Management</h2>
                </div>
                @can('admin.users.edit')
                <a href="{{ route('admin.roles.create') }}" class="btn btn-sm btn-primary" style="font-size:0.8rem;">
                    <i class="fas fa-plus me-1"></i>Add Role
                </a>
                @endcan
            </div>

            {{-- Alert --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @php
            $groups = [
                'HR & Admin'            => ['super_admin', 'admin', 'admin_hr'],
                'Production'            => ['admin_mascot', 'admin_costume', 'admin_animatronic'],
                'Finance & Procurement' => ['admin_finance', 'admin_procurement'],
                'Others'                => ['admin_logistic', 'timing', 'general'],
            ];

            $rolesByName = $roles->keyBy('name');
            @endphp

            @php
            $allGroupedNames = collect($groups)->flatten()->toArray();
            $customRoles = $roles->filter(fn($r) => !in_array($r->name, $allGroupedNames));
            @endphp

            @foreach ($groups as $groupLabel => $roleNames)
                <div class="mb-4">
                    {{-- Group Header --}}
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#aaa;">
                            {{ $groupLabel }}
                        </span>
                        <div style="flex:1;height:1px;background:#eee;"></div>
                    </div>

                    {{-- Cards --}}
                    <div class="row g-2">
                        @foreach ($roleNames as $roleName)
                            @php $role = $rolesByName[$roleName] ?? null; @endphp
                            @if ($role)
                                @php
                                    $color = roleColor($role->name);
                                    $icon  = roleIcon($role->name);
                                @endphp
                                <div class="col-12 col-sm-6 col-md-4">
                                    @can('admin.users.edit')
                                    <a href="{{ route('admin.roles.edit', $role) }}"
                                        class="text-decoration-none d-block rounded p-3 h-100"
                                        style="border:1px solid {{ $color }}30; background:{{ $color }}08; transition:background 0.15s;"
                                        onmouseover="this.style.background='{{ $color }}18'"
                                        onmouseout="this.style.background='{{ $color }}08'">

                                        <div class="d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                    style="width:30px;height:30px;background:{{ $color }}20;">
                                                    <i class="fas {{ $icon }}" style="font-size:0.75rem;color:{{ $color }};"></i>
                                                </span>
                                                <span style="font-size:0.82rem;font-weight:600;color:#333;">
                                                    {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                                </span>
                                            </div>
                                            <i class="fas fa-chevron-right" style="font-size:0.65rem;color:#bbb;"></i>
                                        </div>

                                        <div class="mt-2" style="padding-left:38px;">
                                            <span class="badge rounded-pill px-2"
                                                style="font-size:0.7rem;background:{{ $color }}20;color:{{ $color }};border:1px solid {{ $color }}30;">
                                                {{ $role->permissions_count }} permissions
                                            </span>
                                        </div>

                                    </a>
                                    @endcan
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Custom Roles (not in predefined groups) --}}
            @if ($customRoles->isNotEmpty())
                <div class="mb-4">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span style="font-size:0.7rem;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;color:#aaa;">
                            Custom Roles
                        </span>
                        <div style="flex:1;height:1px;background:#eee;"></div>
                    </div>

                    <div class="row g-2">
                        @foreach ($customRoles as $role)
                            @php
                                $color = roleColor($role->name);
                                $icon  = roleIcon($role->name);
                            @endphp
                            <div class="col-12 col-sm-6 col-md-4">
                                @can('admin.users.edit')
                                <a href="{{ route('admin.roles.edit', $role) }}"
                                    class="text-decoration-none d-block rounded p-3 h-100"
                                    style="border:1px solid {{ $color }}30; background:{{ $color }}08; transition:background 0.15s;"
                                    onmouseover="this.style.background='{{ $color }}18'"
                                    onmouseout="this.style.background='{{ $color }}08'">

                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                                style="width:30px;height:30px;background:{{ $color }}20;">
                                                <i class="fas {{ $icon }}" style="font-size:0.75rem;color:{{ $color }};"></i>
                                            </span>
                                            <span style="font-size:0.82rem;font-weight:600;color:#333;">
                                                {{ ucwords(str_replace('_', ' ', $role->name)) }}
                                            </span>
                                        </div>
                                        <i class="fas fa-chevron-right" style="font-size:0.65rem;color:#bbb;"></i>
                                    </div>

                                    <div class="mt-2" style="padding-left:38px;">
                                        <span class="badge rounded-pill px-2"
                                            style="font-size:0.7rem;background:{{ $color }}20;color:{{ $color }};border:1px solid {{ $color }}30;">
                                            {{ $role->permissions_count }} permissions
                                        </span>
                                        <span class="badge rounded-pill px-2 ms-1"
                                            style="font-size:0.7rem;background:#6c757d20;color:#6c757d;border:1px solid #6c757d30;">
                                            custom
                                        </span>
                                    </div>

                                </a>
                                @endcan
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>
@endsection
