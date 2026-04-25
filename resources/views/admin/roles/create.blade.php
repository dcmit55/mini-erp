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
                        <h2 class="mb-0" style="font-size:1.2rem;">Add New Role</h2>
                        <small class="text-muted" style="font-size:0.75rem;">
                            Role baru akan langsung terdeteksi oleh sistem
                        </small>
                    </div>
                    <i class="fas fa-shield-alt gradient-icon" style="font-size:1.3rem;"></i>
                </div>
            </div>

            {{-- Validation errors --}}
            @if ($errors->any())
                <div class="alert alert-danger py-2 small">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('admin.roles.store') }}" method="POST">
                @csrf

                {{-- Role Name --}}
                <div class="mb-4 p-3 border rounded" style="background:#fafafa;">
                    <label class="form-label fw-semibold" style="font-size:0.82rem;">
                        <i class="fas fa-tag me-1" style="color:#6f42c1;"></i>Role Name
                    </label>
                    <input type="text"
                        name="name"
                        class="form-control form-control-sm @error('name') is-invalid @enderror"
                        style="font-size:0.82rem;max-width:320px;"
                        placeholder="contoh: admin_warehouse"
                        value="{{ old('name') }}"
                        autocomplete="off">
                    <div class="form-text" style="font-size:0.72rem;">
                        Gunakan huruf kecil, angka, dan underscore saja. Contoh: <code>admin_warehouse</code>
                    </div>
                    @error('name')
                        <div class="invalid-feedback" style="font-size:0.72rem;">{{ $message }}</div>
                    @enderror
                </div>

                {{-- Module groups --}}
                @php
                $moduleLabels = [
                    'admin'        => ['label' => 'Admin',        'icon' => 'fa-user-cog',      'color' => '#6f42c1'],
                    'hr'           => ['label' => 'HR',           'icon' => 'fa-users',         'color' => '#0d6efd'],
                    'production'   => ['label' => 'Production',   'icon' => 'fa-industry',      'color' => '#e83e8c'],
                    'logistic'     => ['label' => 'Logistic',     'icon' => 'fa-boxes',         'color' => '#fd7e14'],
                    'procurement'  => ['label' => 'Procurement',  'icon' => 'fa-file-invoice',  'color' => '#20c997'],
                    'finance'      => ['label' => 'Finance',      'icon' => 'fa-coins',         'color' => '#198754'],
                    'lark'         => ['label' => 'Lark',         'icon' => 'fa-sync-alt',      'color' => '#0dcaf0'],
                    'feature'      => ['label' => 'Feature',      'icon' => 'fa-bullhorn',      'color' => '#ffc107'],
                ];
                @endphp

                <div class="row g-3">
                    @foreach ($allPermissions as $module => $permissions)
                        @php
                        $meta = $moduleLabels[$module] ?? ['label' => ucfirst($module), 'icon' => 'fa-key', 'color' => '#6c757d'];
                        @endphp

                        <div class="col-12 col-md-6">
                            <div class="border rounded p-3 h-100" style="background:#fafafa;">

                                {{-- Module header --}}
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas {{ $meta['icon'] }}" style="color:{{ $meta['color'] }};font-size:0.85rem;"></i>
                                        <span style="font-size:0.82rem;font-weight:600;color:{{ $meta['color'] }};">
                                            {{ $meta['label'] }}
                                        </span>
                                    </div>
                                    <label class="d-flex align-items-center gap-1 mb-0" style="cursor:pointer;font-size:0.72rem;color:#888;">
                                        <input type="checkbox"
                                            class="module-toggle"
                                            data-module="{{ $module }}">
                                        all
                                    </label>
                                </div>

                                {{-- Permission checkboxes --}}
                                <div class="row g-1">
                                    @foreach ($permissions as $permission)
                                        @php
                                        $parts = explode('.', $permission->name);
                                        $label = implode(' ', array_slice($parts, 1));
                                        $oldPerms = old('permissions', []);
                                        @endphp
                                        <div class="col-6">
                                            <label class="d-flex align-items-center gap-1 mb-0"
                                                style="font-size:0.72rem;cursor:pointer;" title="{{ $permission->name }}">
                                                <input type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission->name }}"
                                                    class="perm-check perm-{{ $module }}"
                                                    {{ in_array($permission->name, $oldPerms) ? 'checked' : '' }}>
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
                        <i class="fas fa-plus me-1"></i>Create Role
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
