@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Process Goods In</h2>
                <hr>
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                <form method="POST" action="{{ route('goods_in.store') }}">
                    @csrf
                    <input type="hidden" name="goods_out_id" value="{{ $goodsOut->id }}">
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label>Material</label>
                            <input type="text" class="form-control" value="{{ $goodsOut->inventory->name }}" disabled>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Remaining Quantity to Goods In</label>
                            <div class="input-group">
                                <input type="number" class="form-control" value="{{ $goodsOut->remaining_quantity }}"
                                    id="remaining-qty" readonly disabled>
                                <span class="input-group-text unit-label">{{ $goodsOut->inventory->unit }}</span>
                            </div>
                            <div class="form-text">
                                Goods Out Quantity: {{ $goodsOut->quantity }} {{ $goodsOut->inventory->unit }}
                            </div>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Quantity Returned <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" name="quantity"
                                    class="form-control @error('quantity') is-invalid @enderror" step="any"
                                    max="{{ $goodsOut->remaining_quantity }}" value="{{ old('quantity') }}" required
                                    oninvalid="this.setCustomValidity('Quantity Returned must not exceed Remaining Quantity to Goods In.')"
                                    oninput="this.setCustomValidity('')">
                                <span class="input-group-text unit-label">{{ $goodsOut->inventory->unit }}</span>
                            </div>
                            @error('quantity')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>From Project</label>
                            <input type="text" class="form-control"
                                value="{{ $goodsOut->project->name ?? 'No Project' }}" disabled>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned At <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="returned_at" class="form-control"
                                value="{{ old('returned_at', \Carbon\Carbon::now()->format('Y-m-d\TH:i')) }}" required>
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label>Returned/In By</label>
                            <input type="text" class="form-control" value="{{ auth()->user()->username }}" disabled>
                            @if (auth()->user()->department)
                                <div class="form-text">
                                    Department: {{ auth()->user()->department->name }}
                                </div>
                            @endif
                        </div>
                        <div class="col-lg-12 mb-3">
                            <label>Remark</label>
                            <textarea name="remark" class="form-control" rows="3">{{ old('remark') }}</textarea>
                        </div>
                    </div>
                    <a href="{{ route('goods_in.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success" id="goodsin-submit-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Submit
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form[action="{{ route('goods_in.store') }}"]');
            const submitBtn = document.getElementById('goodsin-submit-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Submitting...';
                });
            }

            // Jika pakai AJAX, aktifkan kembali tombol di error handler:
            // submitBtn.disabled = false;
            // spinner.classList.add('d-none');
            // submitBtn.childNodes[2].textContent = ' Submit';
        });
    </script>
@endpush
