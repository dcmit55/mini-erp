@extends('layouts.app')

@section('title', 'National Holidays')

@section('content')
<div class="container mt-4">
    <div class="card shadow rounded">
        <div class="card-body">
            <div class="d-flex align-items-center gap-2 mb-3">
                <i class="fas fa-calendar-alt me-2" style="font-size:1.3rem;"></i>
                <h2 class="mb-0" style="font-size:1.3rem;">National Holidays</h2>
                <div class="ms-auto d-flex gap-2">
                    <form method="GET" action="{{ route('national-holidays.index') }}" class="d-flex gap-2">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                            @if(!$years->contains(now()->year))
                                <option value="{{ now()->year }}" {{ $year == now()->year ? 'selected' : '' }}>{{ now()->year }}</option>
                            @endif
                        </select>
                    </form>
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addHolidayModal">
                        <i class="fas fa-plus me-1"></i> Tambah
                    </button>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <table class="table table-sm table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th style="width:40px;" class="text-center">No</th>
                        <th style="width:120px;">Tanggal</th>
                        <th>Nama Hari Libur</th>
                        <th style="width:130px;" class="text-center">Tipe</th>
                        <th style="width:90px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($holidays as $i => $h)
                    <tr>
                        <td class="text-center text-muted">{{ $i + 1 }}</td>
                        <td style="white-space:nowrap;">{{ \Carbon\Carbon::parse($h->date)->format('d/m/Y') }}</td>
                        <td>{{ $h->name }}</td>
                        <td class="text-center">
                            @if($h->is_joint_leave)
                                <span class="badge bg-warning text-dark">Cuti Bersama</span>
                            @else
                                <span class="badge bg-danger">Libur Nasional</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary"
                                        onclick="openEditModal({{ $h->id }}, '{{ $h->date->format('Y-m-d') }}', @json($h->name), {{ $h->is_joint_leave ? 1 : 0 }})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('national-holidays.destroy', $h->id) }}" method="POST"
                                      onsubmit="return confirm('Hapus \'{{ addslashes($h->name) }}\'?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Tidak ada data hari libur untuk tahun {{ $year }}.
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-bs-toggle="modal" data-bs-target="#addHolidayModal">Tambah sekarang</button>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Modal Tambah --}}
<div class="modal fade" id="addHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('national-holidays.store') }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Tambah Hari Libur</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" name="date" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama Hari Libur</label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="contoh: Hari Raya Idul Fitri 1447H" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_joint_leave" value="1" id="addJointLeave">
                        <label class="form-check-label small" for="addJointLeave">Cuti Bersama</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- Modal Edit --}}
<div class="modal fade" id="editHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" id="editHolidayForm">
            @csrf
            @method('PUT')
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Edit Hari Libur</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small">Tanggal</label>
                        <input type="date" name="date" id="editDate" class="form-control form-control-sm" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Nama Hari Libur</label>
                        <input type="text" name="name" id="editName" class="form-control form-control-sm" required>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_joint_leave" value="1" id="editJointLeave">
                        <label class="form-check-label small" for="editJointLeave">Cuti Bersama</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary btn-sm">Simpan</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, date, name, isJointLeave) {
    document.getElementById('editHolidayForm').action = '/national-holidays/' + id;
    document.getElementById('editDate').value = date;
    document.getElementById('editName').value = name;
    document.getElementById('editJointLeave').checked = isJointLeave == 1;
    new bootstrap.Modal(document.getElementById('editHolidayModal')).show();
}
</script>
@endsection
