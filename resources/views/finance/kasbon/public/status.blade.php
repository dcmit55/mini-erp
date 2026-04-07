@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 680px;">

    <div class="mb-4">
        <h5 class="fw-semibold mb-1">Cek Status Kasbon</h5>
        <p class="text-muted small mb-0">Masukkan nomor referensi pengajuan kasbon Anda.</p>
    </div>

    {{-- Search form --}}
    <form method="GET" action="{{ route('kasbon.status') }}" class="mb-4">
        <div class="input-group">
            <input type="text" name="ref" class="form-control form-control-sm"
                   placeholder="KSB-YYYYMMDD-XXXX" value="{{ $ref }}" required>
            <button type="submit" class="btn btn-primary btn-sm px-4">
                <i class="fas fa-search me-1"></i>Cek
            </button>
        </div>
    </form>

    @if ($ref && !$kasbon)
        <div class="alert alert-warning small">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Nomor referensi <strong>{{ $ref }}</strong> tidak ditemukan. Pastikan nomor referensi yang Anda masukkan benar.
        </div>
    @endif

    @if ($kasbon)
    {{-- Status badge --}}
    @php
        $badgeMap = [
            'pending'      => ['bg-warning text-dark',  'PENDING'],
            'under_review' => ['bg-info text-dark',     'SEDANG DIREVIEW'],
            'approved'     => ['bg-success',            'DISETUJUI'],
            'rejected'     => ['bg-danger',             'DITOLAK'],
            'disbursed'    => ['bg-primary',            'DANA DICAIRKAN'],
            'repaying'     => ['bg-purple text-white',  'SEDANG DICICIL'],
            'settled'      => ['bg-secondary',          'LUNAS'],
        ];
        [$badgeClass, $badgeLabel] = $badgeMap[$kasbon->status] ?? ['bg-secondary', strtoupper($kasbon->status)];
    @endphp

    {{-- Info pengajuan --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <div class="text-muted small mb-1">Nomor Referensi</div>
                    <div class="fw-bold fs-5">{{ $kasbon->ref_number }}</div>
                </div>
                <span class="badge {{ $badgeClass }} rounded-2 px-3 py-2">{{ $badgeLabel }}</span>
            </div>

            <div class="row g-3 small">
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Nama</div>
                    <div class="fw-medium">{{ $kasbon->nama_lengkap }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Departemen</div>
                    <div class="fw-medium">{{ $kasbon->department->name ?? '—' }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Jumlah Diminta</div>
                    <div class="fw-medium">Rp {{ number_format($kasbon->jumlah_diminta, 0, ',', '.') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Tenor</div>
                    <div class="fw-medium">{{ $kasbon->tenor_bulan }} Bulan</div>
                </div>
                <div class="col-12">
                    <div class="text-muted" style="font-size:.7rem;">Tanggal Pengajuan</div>
                    <div class="fw-medium">{{ $kasbon->submitted_at?->format('d M Y, H:i') ?? '—' }}</div>
                </div>

                @if ($kasbon->status === 'approved' || $kasbon->jumlah_disetujui)
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Jumlah Disetujui</div>
                    <div class="fw-semibold text-success">Rp {{ number_format($kasbon->jumlah_disetujui, 0, ',', '.') }}</div>
                </div>
                @endif
                @if ($kasbon->suku_bunga_persen !== null && $kasbon->jumlah_disetujui)
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Bunga</div>
                    <div class="fw-medium">{{ $kasbon->suku_bunga_persen }}% / bulan (flat)</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Biaya Admin</div>
                    <div class="fw-medium">Rp {{ number_format($kasbon->biaya_admin, 0, ',', '.') }} <span class="text-muted" style="font-size:.7rem;">(dibayar bulan ke-1)</span></div>
                </div>
                @endif

                @if ($kasbon->status === 'rejected' && $kasbon->catatan_admin)
                <div class="col-12">
                    <div class="text-muted" style="font-size:.7rem;">Alasan Penolakan</div>
                    <div class="fw-medium text-danger">{{ $kasbon->catatan_admin }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress cicilan (jika ada) --}}
    @if ($kasbon->installments->count() > 0)
    @php
        $totalCicilan = $kasbon->installments->count();
        $lunas        = $kasbon->installments->where('status', 'paid')->count();
        $persen       = $totalCicilan > 0 ? round(($lunas / $totalCicilan) * 100) : 0;
    @endphp
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-4">
            <div class="mb-3">
                <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Progress Cicilan</span>
            </div>

            <div class="d-flex justify-content-between small mb-1">
                <span>{{ $lunas }} dari {{ $totalCicilan }} cicilan lunas</span>
                <span class="fw-semibold">{{ $persen }}%</span>
            </div>
            <div class="progress mb-3" style="height:8px;">
                <div class="progress-bar bg-success" style="width:{{ $persen }}%"></div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-borderless small mb-0">
                    <thead>
                        <tr class="text-muted" style="font-size:.7rem;">
                            <th>Bulan ke-</th>
                            <th>Jatuh Tempo</th>
                            <th class="text-end">Pokok</th>
                            <th class="text-end">Bunga</th>
                            <th class="text-end">Admin</th>
                            <th class="text-end">Total</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kasbon->installments as $cicilan)
                        <tr>
                            <td>{{ $cicilan->bulan_ke }}</td>
                            <td style="white-space:nowrap;">{{ $cicilan->due_date->format('d M Y') }}</td>
                            <td class="text-end">Rp {{ number_format($cicilan->jumlah_pokok, 0, ',', '.') }}</td>
                            <td class="text-end text-warning">Rp {{ number_format($cicilan->jumlah_bunga, 0, ',', '.') }}</td>
                            <td class="text-end text-info">{{ $cicilan->jumlah_biaya_admin > 0 ? 'Rp '.number_format($cicilan->jumlah_biaya_admin, 0, ',', '.') : '—' }}</td>
                            <td class="text-end fw-medium">Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if ($cicilan->status === 'paid')
                                    <span class="badge bg-success rounded-2">Lunas</span>
                                @elseif ($cicilan->status === 'partial')
                                    <span class="badge bg-warning text-dark rounded-2">Sebagian</span>
                                @else
                                    <span class="badge bg-secondary rounded-2">Belum</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    @endif

    <div class="text-center mt-4">
        <a href="{{ route('kasbon.create') }}" class="btn btn-outline-primary btn-sm rounded-2">
            <i class="fas fa-plus me-1"></i>Ajukan Kasbon Baru
        </a>
    </div>
</div>
@endsection
