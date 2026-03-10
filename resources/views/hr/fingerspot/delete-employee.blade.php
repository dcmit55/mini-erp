@extends('layouts.app')

@section('title', 'Delete Employee from Device')

@section('content')
<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-12">
            {{-- Cloud configuration info (smaller text) --}}
            <div class="alert alert-info border-0 shadow-sm mb-4 py-2 px-3 small">
                <i class="fas fa-info-circle me-2"></i>
                Device ID: <strong>{{ $defaultDeviceId ?: '(not set in .env)' }}</strong>
                &nbsp;·&nbsp; API Token: <strong>{{ config('fingerspot.api_token') ? '✓ Configured' : '✗ Not configured' }}</strong>
            </div>

            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success border-0 rounded-0 d-flex align-items-center px-4 py-3 mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <div class="flex-grow-1">{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger border-0 rounded-0 d-flex align-items-center px-4 py-3 mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <div class="flex-grow-1">{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            {{-- Search filter --}}
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-3">
                    <form method="GET" action="{{ route('fingerspot.delete-employee.form') }}">
                        <div class="row g-2">
                            <div class="col-md-5">
                                <div class="input-group input-group-sm">
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Search by NIK or Name..." 
                                           value="{{ request('search') }}">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    @if(request()->has('search') && request('search') != '')
                                    <a href="{{ route('fingerspot.delete-employee.form') }}" 
                                       class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i>
                                    </a>
                                    @endif
                                </div>
                            </div>
                            @if(request()->anyFilled(['search']))
                            <div class="col-md-2">
                                <a href="{{ route('fingerspot.delete-employee.form') }}" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-times me-1"></i>Clear
                                </a>
                            </div>
                            @endif
                        </div>
                    </form>
                </div>
            </div>

            {{-- Main table --}}
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" id="deleteEmployeeTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="border-0 ps-4" style="width: 70px;">No</th>
                                    <th class="border-0">NIK</th>
                                    <th class="border-0">Nama Karyawan</th>
                                    <th class="border-0">ID di Mesin</th>
                                    <th class="border-0 text-center" style="width: 150px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($error)
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-danger">
                                                <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                                                <h5>Gagal mengambil data dari mesin</h5>
                                                <p class="mb-0 small">{{ $error }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @else
                                @forelse($employees as $employee)
                                    @php
                                        $deviceId = ltrim(preg_replace('/[^0-9]/', '', $employee->employee_no), '0');
                                    @endphp
                                    <tr class="align-middle">
                                        <td class="ps-4">
                                            <span class="table-number">
                                                {{ ($employees->currentPage() - 1) * $employees->perPage() + $loop->iteration }}
                                            </span>
                                        </td>
                                        <td>{{ $employee->employee_no }}</td>
                                        <td>{{ $employee->name }}</td>
                                        <td>
                                            <span class="badge bg-light text-dark border px-3 py-1">
                                                {{ $deviceId }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('fingerspot.delete-employee') }}"
                                                  method="POST"
                                                  class="d-inline"
                                                  onsubmit="return confirm('Hapus {{ $employee->name }} (ID: {{ $deviceId }}) dari mesin fingerprint?')">
                                                @csrf
                                                <input type="hidden" name="cloud_id" value="{{ $defaultDeviceId }}">
                                                <input type="hidden" name="pin" value="{{ $deviceId }}">
                                                <button type="submit"
                                                        class="btn btn-sm btn-outline-danger border-0 px-3 py-1 action-btn"
                                                        data-bs-toggle="tooltip" title="Hapus dari Mesin">
                                                    <i class="fas fa-trash me-1"></i>Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="fas fa-fingerprint fa-3x mb-3"></i>
                                                <h5>Tidak ada karyawan ditemukan</h5>
                                                @if(request()->has('search') && request('search') != '')
                                                    <p class="mb-0">Tidak ada hasil untuk "{{ request('search') }}"</p>
                                                    <a href="{{ route('fingerspot.delete-employee.form') }}"
                                                       class="btn btn-outline-primary btn-sm rounded-pill px-4 mt-3">
                                                        <i class="fas fa-times me-1"></i>Hapus Filter
                                                    </a>
                                                @else
                                                    <p class="mb-0">Belum ada karyawan yang pernah melakukan scan di mesin fingerprint.</p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($employees->hasPages())
                <div class="card-footer bg-white border-0 py-3 px-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $employees->firstItem() }} to {{ $employees->lastItem() }} of {{ $employees->total() }} entries
                        </div>
                        <div>
                            {{ $employees->appends(request()->query())->links('pagination::bootstrap-4') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .table-number {
        display: inline-block;
        width: 36px;
        height: 36px;
        line-height: 36px;
        background-color: #eef2ff;
        color: #4f46e5;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: all 0.2s;
        text-align: center;
    }

    tr:hover .table-number {
        background-color: #4f46e5;
        color: white;
        transform: scale(1.05);
    }

    .table th {
        font-weight: 600;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #64748b;
        padding: 1rem 0.75rem;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }

    .table td {
        padding: 1rem 0.75rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        font-size: 0.9rem;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8fafc;
    }

    .action-btn {
        border-radius: 6px;
        transition: all 0.2s;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .action-btn:hover {
        background-color: #f1f5f9;
        transform: translateY(-1px);
    }

    .alert.small {
        font-size: 0.85rem;
    }

    .badge.bg-light {
        background-color: #f8fafc !important;
        border: 1px solid #e2e8f0 !important;
        color: #374151 !important;
        font-weight: 500;
    }

    /* Responsive for mobile */
    @media (max-width: 768px) {
        .table-responsive {
            border: 0;
        }

        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1rem;
        }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border: none;
            border-bottom: 1px solid #f1f5f9;
            text-align: left;
        }

        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 0.75rem;
            min-width: 120px;
            margin-right: 1rem;
        }

        .table tbody td:first-child {
            display: block;
            text-align: center;
            padding: 0.5rem 0 1rem 0;
            border-bottom: 2px solid #eef2ff;
        }

        .table tbody td:first-child:before {
            content: "No";
            display: block;
            margin-bottom: 0.5rem;
            min-width: auto;
        }

        .table-number {
            margin: 0 auto;
        }

        .action-btn {
            width: 100%;
            margin-top: 0.5rem;
            text-align: center;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Set data-label for responsive
    const tableHeaders = document.querySelectorAll('#deleteEmployeeTable thead th');
    tableHeaders.forEach((header, index) => {
        const text = header.textContent.trim();
        if (text) {
            const cells = document.querySelectorAll(`#deleteEmployeeTable tbody td:nth-child(${index + 1})`);
            cells.forEach(cell => {
                cell.setAttribute('data-label', text);
            });
        }
    });

    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            const closeBtn = alert.querySelector('.btn-close');
            if (closeBtn) closeBtn.click();
        });
    }, 5000);
});
</script>
@endsection