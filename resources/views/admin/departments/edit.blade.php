@extends('layouts.app')

@section('content')
    <div class="container mt-4">
        <div class="card shadow rounded">
            <div class="card-header">
                <h2 class="mb-0 flex-shrink-0" style="font-size:1.3rem;">Edit Department</h2>
            </div>
            <div class="card-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {!! session('error') !!}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Whoops!</strong> There were some problems with your input.
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('departments.update', $department->id) }}">
                    @csrf
                    @method('PUT')
                    <div class="row mb-2">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $department->name) }}" required>
                            @error('name')
                                <small class="text-danger">{{ $message }}</small>
                            @enderror
                        </div>
                    </div>

                    <a href="{{ route('departments.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="department-update-btn">
                        <span class="spinner-border spinner-border-sm me-1 d-none" role="status" aria-hidden="true"></span>
                        Update
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent multiple submit & show spinner
            const form = document.querySelector(
                'form[action="{{ route('departments.update', $department->id) }}"]');
            const submitBtn = document.getElementById('department-update-btn');
            const spinner = submitBtn ? submitBtn.querySelector('.spinner-border') : null;

            if (form && submitBtn && spinner) {
                form.addEventListener('submit', function() {
                    submitBtn.disabled = true;
                    spinner.classList.remove('d-none');
                    submitBtn.childNodes[2].textContent = ' Updating...';
                });
            }
        });
    </script>
@endpush
