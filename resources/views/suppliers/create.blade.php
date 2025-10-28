@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-plus-circle text-primary fs-4 me-3"></i>
                    <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Create Supplier</h2>
                </div>
                <hr>

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('suppliers.store') }}">
                    @csrf

                    <div class="row">
                        <!-- Supplier Code -->
                        <div class="col-lg-6 mb-3">
                            <label for="supplier_code" class="form-label">Supplier Code</label>
                            <div class="input-group">
                                <span class="input-group-text">SUP</span>
                                <input type="text" class="form-control" id="supplier_code" name="supplier_code"
                                    value="{{ old('supplier_code') }}" placeholder="001" maxlength="10">
                            </div>
                            <small class="text-muted">Enter numbers only, SUP prefix will be added automatically</small>
                            @error('supplier_code')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Name -->
                        <div class="col-lg-6 mb-3">
                            <label for="name" class="form-label">Supplier Name <span
                                    class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ old('name') }}" placeholder="Enter supplier name" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Contact Person -->
                        <div class="col-lg-6 mb-3">
                            <label for="contact_person" class="form-label">Contact Person</label>
                            <input type="tel" class="form-control" id="contact_person" name="contact_person"
                                value="{{ old('contact_person') }}" placeholder="e.g., +62812345678" maxlength="20">
                            @error('contact_person')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Location -->
                        <div class="col-12 col-md-6 mb-3">
                            <label for="location_id" class="form-label"> Location <span class="text-danger">*</span>
                            </label>
                            <select name="location_id" id="location_id" class="form-select select2 w-100">
                                <option value="">Select Location</option>
                                @foreach ($locations as $location)
                                    <option value="{{ $location->id }}"
                                        {{ old('location_id') == $location->id ? 'selected' : '' }}>
                                        {{ $location->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('location_id')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>


                    <!-- Address -->
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3" placeholder="Enter complete address">{{ old('address') }}</textarea>
                        @error('address')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <div class="row">
                        <!-- Referral Link -->
                        <div class="col-lg-8 mb-3">
                            <label for="referral_link" class="form-label">Referral Link</label>
                            <input type="url" class="form-control" id="referral_link" name="referral_link"
                                value="{{ old('referral_link') }}" placeholder="https://example.com/ref/abc123">
                            <small class="text-muted">Must be a valid URL, example: https://example.com/ref/abc123</small>
                            @error('referral_link')
                                <small class="text-danger d-block">{{ $message }}</small>
                            @enderror
                        </div>

                        <!-- Lead Time Days -->
                        <div class="col-lg-4 mb-3">
                            <label for="lead_time_days" class="form-label">Lead Time <span
                                    class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="lead_time_days" name="lead_time_days"
                                    value="{{ old('lead_time_days') }}" placeholder="For Example 8" required
                                    maxlength="10">
                                <span class="input-group-text">days</span>
                            </div>
                            @error('lead_time_days')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Status -->
                        <div class="col-lg-6 mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="">Select Status</option>
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive
                                </option>
                                <option value="blacklisted" {{ old('status') == 'blacklisted' ? 'selected' : '' }}>
                                    Blacklisted</option>
                            </select>
                            @error('status')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <!-- Remark -->
                    <div class="mb-4">
                        <label for="remark" class="form-label">Remark</label>
                        <textarea class="form-control" id="remark" name="remark" rows="3"
                            placeholder="Additional notes or remarks (optional)">{{ old('remark') }}</textarea>
                        @error('remark')
                            <small class="text-danger">{{ $message }}</small>
                        @enderror
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="supplier-submit-btn">
                            <span class="spinner-border spinner-border-sm me-2 d-none" role="status"
                                aria-hidden="true"></span>Save
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Select2
            $('.select2').select2({
                theme: 'bootstrap-5',
                allowClear: true,
                placeholder: function() {
                    return $(this).data('placeholder');
                }
            });

            // URL validation feedback
            const referralLinkInput = document.getElementById('referral_link');
            referralLinkInput.addEventListener('input', function() {
                const url = this.value;
                if (url && !isValidUrl(url)) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.remove('is-invalid');
                }
            });

            function isValidUrl(string) {
                try {
                    new URL(string);
                    return true;
                } catch (_) {
                    return false;
                }
            }

            // Auto dismiss alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(function(alert) {
                    const alertInstance = new bootstrap.Alert(alert);
                    alertInstance.close();
                });
            }, 10000);

            const form = document.querySelector('form[action*="suppliers"]');
            const submitBtn = document.getElementById('supplier-submit-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.querySelector('i').classList.add('d-none');
                    submitBtn.childNodes[submitBtn.childNodes.length - 1].textContent = ' Saving...';
                });
            }
        });
    </script>
@endpush
