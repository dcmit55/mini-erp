@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm rounded">
        <div class="card-body">

            {{-- Header --}}
            <div class="d-flex align-items-center justify-content-between mb-3">
                <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-secondary py-0 px-2"
                    style="font-size:0.75rem;">
                    <i class="fas fa-arrow-left me-1"></i>Back
                </a>
                <div class="d-flex align-items-center gap-2">
                    <div class="text-end">
                        <h2 class="mb-0" style="font-size:1.2rem;">Edit Role Permissions</h2>
                        <small class="text-muted" style="font-size:0.75rem;">
                            Role: <strong>{{ ucwords(str_replace('_', ' ', $role->name)) }}</strong>
                        </small>
                    </div>
                    <i class="fas fa-shield-alt gradient-icon" style="font-size:1.3rem;"></i>
                </div>
            </div>

            {{-- Alert --}}
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                    {!! session('success') !!}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <form action="{{ route('admin.roles.update', $role) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Module groups --}}
                @php
                $moduleLabels = [
                    'admin'        => ['label' => 'Admin',        'icon' => 'fa-user-cog',      'color' => '#6f42c1'],
                    'hr'           => ['label' => 'HR',           'icon' => 'fa-users',         'color' => '#0d6efd'],
                    'production'   => ['label' => 'Production',   'icon' => 'fa-industry',      'color' => '#e83e8c'],
                    'timing'       => ['label' => 'Timing',       'icon' => 'fa-stopwatch',     'color' => '#fd7e14'],
                    'logistic'     => ['label' => 'Logistic',     'icon' => 'fa-boxes',         'color' => '#fd7e14'],
                    'procurement'  => ['label' => 'Procurement',  'icon' => 'fa-file-invoice',  'color' => '#20c997'],
                    'finance'      => ['label' => 'Finance',      'icon' => 'fa-coins',         'color' => '#198754'],
                    'lark'         => ['label' => 'Lark',         'icon' => 'fa-sync-alt',      'color' => '#0dcaf0'],
                    'feature'      => ['label' => 'Feature',      'icon' => 'fa-bullhorn',      'color' => '#ffc107'],
                ];

                // Split production permissions: timing-related vs non-timing
                $timingKeywords = ['timing', 'monitor'];
                $splitPermissions = collect();
                foreach ($allPermissions as $module => $permissions) {
                    if ($module === 'production') {
                        $timingPerms = $permissions->filter(function ($p) use ($timingKeywords) {
                            $parts = explode('.', $p->name);
                            $sub = $parts[1] ?? '';
                            foreach ($timingKeywords as $kw) {
                                if (str_contains($sub, $kw)) return true;
                            }
                            return false;
                        });
                        $prodPerms = $permissions->reject(function ($p) use ($timingKeywords) {
                            $parts = explode('.', $p->name);
                            $sub = $parts[1] ?? '';
                            foreach ($timingKeywords as $kw) {
                                if (str_contains($sub, $kw)) return true;
                            }
                            return false;
                        });
                        if ($prodPerms->isNotEmpty()) $splitPermissions['production'] = $prodPerms;
                        if ($timingPerms->isNotEmpty()) $splitPermissions['timing'] = $timingPerms;
                    } else {
                        $splitPermissions[$module] = $permissions;
                    }
                }
                @endphp

                <div class="row g-3">
                    @foreach ($splitPermissions as $module => $permissions)
                        @php
                        $meta  = $moduleLabels[$module] ?? ['label' => ucfirst($module), 'icon' => 'fa-key', 'color' => '#6c757d'];
                        $allChecked = $permissions->every(fn($p) => in_array($p->name, $rolePermissions));
                        @endphp

                        <div class="col-12 {{ $module === 'timing' ? 'col-md-12' : 'col-md-6' }}">
                            <div class="border rounded p-3 h-100"
                                style="background:{{ $module === 'timing' ? '#fff8f0' : '#fafafa' }}; {{ $module === 'timing' ? 'border-color:#fd7e14!important;' : '' }}">

                                {{-- Module header --}}
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas {{ $meta['icon'] }}" style="color:{{ $meta['color'] }};font-size:0.85rem;"></i>
                                        <span style="font-size:0.82rem;font-weight:600;color:{{ $meta['color'] }};">
                                            {{ $meta['label'] }}
                                        </span>
                                    </div>
                                    {{-- Select all toggle --}}
                                    <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer;font-size:0.72rem;color:#888;">
                                        <input type="checkbox"
                                            class="module-toggle"
                                            data-module="{{ $module }}"
                                            {{ $allChecked ? 'checked' : '' }}>
                                        all
                                    </label>
                                </div>

                                {{-- Permission checkboxes --}}
                                <div class="row g-1">
                                    @foreach ($permissions as $permission)
                                        @php
                                        $parts  = explode('.', $permission->name);
                                        $label  = implode(' ', array_slice($parts, 1));
                                        @endphp
                                        <div class="{{ $module === 'timing' ? 'col-4 col-md-3' : 'col-6' }}">
                                            <label class="d-flex align-items-center gap-1 mb-0"
                                                style="font-size:0.72rem;cursor:pointer;" title="{{ $permission->name }}">
                                                <input type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->name }}"
                                                    class="perm-check perm-{{ $module }}"
                                                    {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                                                <span class="text-truncate">{{ $label }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>

                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Actions --}}
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-secondary"
                        style="font-size:0.8rem;">Cancel</a>
                    <button type="submit" class="btn btn-sm btn-primary" style="font-size:0.8rem;">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.module-toggle').forEach(toggle => {
    toggle.addEventListener('change', function () {
        const module = this.dataset.module;
        document.querySelectorAll(`.perm-${module}`)
            .forEach(cb => cb.checked = this.checked);
    });
});

// Sync "all" toggle state when individual checkboxes change
document.querySelectorAll('.perm-check').forEach(cb => {
    cb.addEventListener('change', function () {
        const module = this.classList[1].replace('perm-', '');
        const allChecks = document.querySelectorAll(`.perm-${module}`);
        const allChecked = [...allChecks].every(c => c.checked);
        const toggle = document.querySelector(`.module-toggle[data-module="${module}"]`);
        if (toggle) toggle.checked = allChecked;
    });
});
</script>
@endsection
