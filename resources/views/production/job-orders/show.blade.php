@extends('layouts.app')

@section('title', 'Detail Job Order')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detail Job Order</h5>
                        <div>
                            <a href="{{ route('production.job-orders.index') }}" class="btn btn-secondary btn-sm">
                                <i class="fas fa-list"></i> Daftar
                            </a>
                            <a href="{{ route('production.job-orders.create') }}" class="btn btn-primary btn-sm">
                                <i class="fas fa-plus"></i> Baru
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Job Order Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">ID Job Order:</th>
                                    <td>
                                        <span class="badge bg-primary fs-6">{{ $jobOrder->id }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Nama:</th>
                                    <td><strong>{{ $jobOrder->name }}</strong></td>
                                </tr>
                                <tr>
                                    <th>Project:</th>
                                    <td>
                                        <a href="{{ route('projects.show', $jobOrder->project_id) }}">
                                            {{ $jobOrder->project->name }}
                                        </a>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Department:</th>
                                    <td>{{ $jobOrder->department->name }}</td>
                                </tr>
                                <tr>
                                    <th>Deskripsi:</th>
                                    <td>{{ $jobOrder->description ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col-md-6">
                            <table class="table table-sm">
                                <tr>
                                    <th width="40%">Tanggal Mulai:</th>
                                    <td>
                                        {{ $jobOrder->start_date ? $jobOrder->start_date->format('d/m/Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Tanggal Selesai:</th>
                                    <td>
                                        {{ $jobOrder->end_date ? $jobOrder->end_date->format('d/m/Y') : '-' }}
                                    </td>
                                </tr>
                                <tr>
                                    <th>Durasi:</th>
                                    <td>
                                        @if($jobOrder->start_date && $jobOrder->end_date)
                                            {{ $jobOrder->getDurationDays() }} hari
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Ditugaskan Kepada:</th>
                                    <td>
                                        @if($jobOrder->assignee)
                                            {{ $jobOrder->assignee->name }}
                                            @if($jobOrder->assignee->email)
                                                <br><small class="text-muted">{{ $jobOrder->assignee->email }}</small>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Dibuat Oleh:</th>
                                    <td>
                                        {{ $jobOrder->creator->name ?? '-' }}
                                        <br>
                                        <small class="text-muted">
                                            {{ $jobOrder->created_at->format('d/m/Y H:i') }}
                                        </small>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Notes -->
                    @if($jobOrder->notes)
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Catatan</h6>
                        <div class="alert alert-light">
                            {{ $jobOrder->notes }}
                        </div>
                    </div>
                    @endif
                    
                    <!-- Related Purchases -->
                    <div class="mb-4">
                        <h6 class="border-bottom pb-2">Purchase Terkait</h6>
                        @if($jobOrder->projectPurchases && $jobOrder->projectPurchases->count() > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>PO Number</th>
                                            <th>Supplier</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Tanggal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($jobOrder->projectPurchases as $purchase)
                                        <tr>
                                            <td>
                                                <a href="{{ route('purchases.show', $purchase->id) }}">
                                                    {{ $purchase->po_number }}
                                                </a>
                                            </td>
                                            <td>{{ $purchase->supplier->name ?? '-' }}</td>
                                            <td>Rp {{ number_format($purchase->invoice_total, 0, ',', '.') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $purchase->status == 'completed' ? 'success' : 'warning' }}">
                                                    {{ $purchase->status }}
                                                </span>
                                            </td>
                                            <td>{{ $purchase->tanggal->format('d/m/Y') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Belum ada purchase untuk job order ini.
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="card-footer">
                    <small class="text-muted">
                        Terakhir diupdate: {{ $jobOrder->updated_at->format('d/m/Y H:i') }}
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Actions -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Aksi</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="#" class="btn btn-outline-primary">
                            <i class="fas fa-edit"></i> Edit Job Order
                        </a>
                        <a href="{{ route('purchases.create', ['job_order_id' => $jobOrder->id]) }}" 
                           class="btn btn-success">
                            <i class="fas fa-shopping-cart"></i> Buat Purchase Baru
                        </a>
                        <a href="{{ route('material-requests.create', ['job_order_id' => $jobOrder->id]) }}" 
                           class="btn btn-info">
                            <i class="fas fa-clipboard-list"></i> Buat Material Request
                        </a>
                        <button class="btn btn-outline-danger" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i> Hapus Job Order
                        </button>
                    </div>
                    
                    <!-- Delete Form -->
                    <form id="deleteForm" action="{{ route('production.job-orders.destroy', $jobOrder->id) }}" 
                          method="POST" class="d-none">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Statistik</h6>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="display-6">{{ $jobOrder->projectPurchases->count() }}</div>
                            <small class="text-muted">Total Purchase</small>
                        </div>
                        <div class="col-6">
                            <div class="display-6">
                                Rp {{ number_format($jobOrder->projectPurchases->sum('invoice_total'), 0, ',', '.') }}
                            </div>
                            <small class="text-muted">Total Nilai</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function confirmDelete() {
        if (confirm('Apakah Anda yakin ingin menghapus job order ini?')) {
            document.getElementById('deleteForm').submit();
        }
    }
</script>
@endpush