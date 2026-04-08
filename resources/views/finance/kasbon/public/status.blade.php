@extends('layouts.app')

@section('content')
<div class="container py-4" style="max-width: 720px;">

    {{-- Header --}}
    <div class="text-center mb-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
             style="width:56px;height:56px;background:rgba(13,110,253,0.1);">
            <i class="fas fa-search-dollar text-primary fs-4"></i>
        </div>
        <h5 class="fw-semibold mb-1">Cek Status Kasbon</h5>
        <p class="text-muted small mb-0">Masukkan nomor referensi yang diterima saat pengajuan</p>
    </div>

    {{-- Search form --}}
    <form method="GET" action="{{ route('kasbon.status') }}" class="mb-4">
        <div class="input-group shadow-sm rounded-3 overflow-hidden" style="border:1.5px solid #dee2e6;">
            <span class="input-group-text bg-white border-0 ps-3">
                <i class="fas fa-hashtag text-muted"></i>
            </span>
            <input type="text" name="ref" class="form-control border-0 shadow-none"
                   placeholder="KSB-YYYYMMDD-XXXX" value="{{ $ref }}"
                   style="font-family:monospace;" required>
            <button type="submit" class="btn btn-primary px-4 border-0 rounded-0">
                <i class="fas fa-search me-1"></i>Cek
            </button>
        </div>
    </form>

    @if ($ref && !$kasbon)
    <div class="alert border-0 rounded-3 small d-flex align-items-center gap-2"
         style="background:rgba(220,53,69,0.08);color:#842029;">
        <i class="fas fa-exclamation-circle fs-5"></i>
        <div>Nomor referensi <strong>{{ $ref }}</strong> tidak ditemukan. Periksa kembali nomor yang Anda masukkan.</div>
    </div>
    @endif

    @if ($kasbon)
    @php
        $statusMap = [
            'pending'      => ['bg-warning text-dark',  'MENUNGGU',        'fas fa-clock',              'Pengajuan diterima, menunggu review Finance.'],
            'under_review' => ['bg-info text-dark',     'SEDANG DIREVIEW', 'fas fa-magnifying-glass',   'Sedang ditinjau oleh tim Finance.'],
            'approved'     => ['bg-success text-white', 'DISETUJUI',       'fas fa-check-circle',       'Pengajuan disetujui. Dana akan segera dicairkan.'],
            'rejected'     => ['bg-danger text-white',  'DITOLAK',         'fas fa-times-circle',       'Pengajuan ditolak. Lihat catatan Finance di bawah.'],
            'disbursed'    => ['bg-primary text-white', 'DANA DICAIRKAN',  'fas fa-money-bill-wave',    'Dana telah dicairkan. Cicilan mulai berjalan.'],
            'repaying'     => ['bg-purple text-white',  'SEDANG DICICIL',  'fas fa-calendar-check',     'Cicilan sedang berjalan.'],
            'settled'      => ['bg-secondary text-white','LUNAS',          'fas fa-check-double',       'Semua cicilan telah dilunasi.'],
        ];
        [$badgeClass, $badgeLabel, $badgeIcon, $statusDesc] = $statusMap[$kasbon->status] ?? ['bg-secondary text-white', strtoupper($kasbon->status), 'fas fa-circle', ''];

        $totalCicilan = $kasbon->installments->count();
        $lunas        = $kasbon->installments->where('status', 'paid')->count();
        $persen       = $totalCicilan > 0 ? round(($lunas / $totalCicilan) * 100) : 0;
        $sisaTagihan  = $kasbon->installments->where('status', '!=', 'paid')->sum('jumlah_cicilan');
    @endphp

    {{-- Status Banner --}}
    <div class="card border-0 rounded-3 mb-3 shadow-sm overflow-hidden">
        <div class="p-4 d-flex align-items-center gap-3" style="background:linear-gradient(135deg,#1e3a5f,#2d5f9e);">
            <div class="d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                 style="width:48px;height:48px;background:rgba(255,255,255,0.15);">
                <i class="{{ $badgeIcon }} text-white fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="text-white-50 small mb-1">{{ $kasbon->ref_number }}</div>
                <div class="text-white fw-semibold">{{ $kasbon->nama_lengkap }}</div>
                <div class="text-white-50 small">{{ $kasbon->department->name ?? '—' }}</div>
            </div>
            <span class="badge {{ $badgeClass }} rounded-2 px-3 py-2 fw-semibold">{{ $badgeLabel }}</span>
        </div>
        @if($statusDesc)
        <div class="px-4 py-2 small" style="background:rgba(13,110,253,0.06);color:#1e3a5f;">
            <i class="fas fa-info-circle me-1"></i>{{ $statusDesc }}
        </div>
        @endif
    </div>

    {{-- Detail Pengajuan --}}
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4">
            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Detail Pengajuan</span>
        </div>
        <div class="card-body px-4 py-3">
            <div class="row g-3 small">
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Jumlah Diminta</div>
                    <div class="fw-semibold">Rp {{ number_format($kasbon->jumlah_diminta, 0, ',', '.') }}</div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Tenor</div>
                    <div class="fw-semibold">{{ $kasbon->tenor_bulan }} Bulan</div>
                </div>
                @if($kasbon->jumlah_disetujui)
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Jumlah Disetujui</div>
                    <div class="fw-semibold text-success">Rp {{ number_format($kasbon->jumlah_disetujui, 0, ',', '.') }}</div>
                </div>
                @endif
                @if($kasbon->suku_bunga_persen !== null && $kasbon->jumlah_disetujui)
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Bunga</div>
                    <div class="fw-semibold">{{ $kasbon->suku_bunga_persen }}%/bulan <span class="text-muted fw-normal">(flat)</span></div>
                </div>
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Biaya Admin</div>
                    <div class="fw-semibold">Rp {{ number_format($kasbon->biaya_admin, 0, ',', '.') }} <span class="text-muted fw-normal">(bln ke-1)</span></div>
                </div>
                @endif
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Tanggal Pengajuan</div>
                    <div class="fw-semibold">{{ $kasbon->submitted_at?->format('d M Y') ?? '—' }}</div>
                </div>
                @if($kasbon->disbursed_at)
                <div class="col-6">
                    <div class="text-muted" style="font-size:.7rem;">Tanggal Cair</div>
                    <div class="fw-semibold">{{ $kasbon->disbursed_at->format('d M Y') }}</div>
                </div>
                @endif
                @if($kasbon->status === 'rejected' && $kasbon->catatan_admin)
                <div class="col-12">
                    <div class="text-muted mb-1" style="font-size:.7rem;">Alasan Penolakan</div>
                    <div class="rounded-2 p-2 text-danger small" style="background:rgba(220,53,69,0.07);border:1px solid rgba(220,53,69,0.2);">
                        {{ $kasbon->catatan_admin }}
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Progress & Cicilan --}}
    @if($totalCicilan > 0)
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-header bg-transparent border-0 pt-3 pb-0 px-4 d-flex justify-content-between align-items-center">
            <span class="fw-semibold small text-uppercase text-muted" style="letter-spacing:.05em;">Jadwal Cicilan</span>
            <span class="small text-muted">{{ $lunas }}/{{ $totalCicilan }} lunas</span>
        </div>
        <div class="card-body px-4 py-3">

            {{-- Progress bar --}}
            <div class="d-flex justify-content-between small mb-1">
                <span class="text-muted">Progress Pelunasan</span>
                <span class="fw-semibold {{ $persen == 100 ? 'text-success' : '' }}">{{ $persen }}%</span>
            </div>
            <div class="progress mb-1 rounded-pill" style="height:8px;">
                <div class="progress-bar bg-success rounded-pill" style="width:{{ $persen }}%"></div>
            </div>
            @if($sisaTagihan > 0)
            <div class="text-end small text-danger mb-3">
                Sisa tagihan: <strong>Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</strong>
            </div>
            @else
            <div class="text-end small text-success mb-3 fw-semibold">
                <i class="fas fa-check-circle me-1"></i>Semua cicilan lunas
            </div>
            @endif

            {{-- Info cara bayar --}}
            <div class="rounded-2 px-3 py-2 mb-3 small" style="background:rgba(13,110,253,0.06);border:1px solid rgba(13,110,253,0.15);">
                <i class="fas fa-info-circle text-primary me-1"></i>
                Pokok cicilan <strong>dipotong dari gaji</strong>.
                Bunga &amp; biaya admin dibayar <strong>tunai ke Finance</strong> sebelum jatuh tempo.
            </div>

            {{-- Tabel cicilan --}}
            <div class="table-responsive">
                <table class="table table-sm table-borderless mb-0" style="font-size:.82rem;">
                    <thead>
                        <tr style="font-size:.7rem;" class="text-muted">
                            <th class="pb-1">Bln</th>
                            <th class="pb-1">Jatuh Tempo</th>
                            <th class="text-end pb-1">Pokok</th>
                            <th class="text-end pb-1">Bunga</th>
                            <th class="text-end pb-1">Admin</th>
                            <th class="text-end pb-1">Total</th>
                            <th class="text-center pb-1">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($kasbon->installments as $cicilan)
                        @php
                            $isOverdue = $cicilan->status !== 'paid' && $cicilan->due_date->isPast();
                        @endphp
                        <tr class="{{ $isOverdue ? 'text-danger' : '' }}">
                            <td class="fw-medium">{{ $cicilan->bulan_ke }}</td>
                            <td style="white-space:nowrap;">
                                {{ $cicilan->due_date->format('d M Y') }}
                                @if($isOverdue)
                                    <span class="badge bg-danger rounded-1 ms-1" style="font-size:.6rem;">Lewat</span>
                                @endif
                            </td>
                            <td class="text-end">Rp {{ number_format($cicilan->jumlah_pokok, 0, ',', '.') }}</td>
                            <td class="text-end {{ $cicilan->status !== 'paid' ? 'text-warning' : 'text-muted' }}">
                                Rp {{ number_format($cicilan->jumlah_bunga, 0, ',', '.') }}
                            </td>
                            <td class="text-end {{ $cicilan->status !== 'paid' ? 'text-info' : 'text-muted' }}">
                                {{ $cicilan->jumlah_biaya_admin > 0 ? 'Rp '.number_format($cicilan->jumlah_biaya_admin, 0, ',', '.') : '—' }}
                            </td>
                            <td class="text-end fw-semibold">Rp {{ number_format($cicilan->jumlah_cicilan, 0, ',', '.') }}</td>
                            <td class="text-center">
                                @if($cicilan->status === 'paid')
                                    <span class="badge bg-success rounded-2" style="font-size:.65rem;">Lunas</span>
                                @elseif($cicilan->status === 'partial')
                                    <span class="badge bg-warning text-dark rounded-2" style="font-size:.65rem;">Sebagian</span>
                                @elseif($isOverdue)
                                    <span class="badge bg-danger rounded-2" style="font-size:.65rem;">Telat</span>
                                @else
                                    <span class="badge bg-secondary rounded-2" style="font-size:.65rem;">Belum</span>
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

    {{-- Footer link --}}
    <div class="text-center mt-4">
        <a href="{{ route('kasbon.create') }}" class="btn btn-outline-primary btn-sm rounded-2 px-4">
            <i class="fas fa-plus me-1"></i>Ajukan Kasbon Baru
        </a>
    </div>

</div>
@endsection
