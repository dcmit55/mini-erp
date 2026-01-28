@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <h3 class="mb-4 text-primary">{{ $project->name }}</h3>
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="w-50">Total Project Cost <span class="text-muted">(material)</span></th>
                                <td>
                                    <span class="fw-bold text-success">
                                        Rp {{ number_format($grandTotal, 0, ',', '.') }}
                                    </span>
                                </td>
                            </tr>
                            <tr>

                            </tr>
                        </tbody>

                            </td>
                        </tr>
                        <tr>
                            <th>Total Project Day Count</th>
                            <td>
                                <span class="fw-bold">{{ $dayCount ?? '-' }} Days</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Manpower Envolved</th>
                            <td>
                                <span class="fw-bold">{{ $manpowerCount }} People</span>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                    @if(!empty($partOutputs) && count($partOutputs))
                        <hr>
                        <h5 class="mt-4 mb-3 text-secondary">Total Output per Part</h5>
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Part Name</th>
                                    <th class="text-end">Total Output</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($partOutputs as $part)
                                    <tr>
                                        <td>{{ $part['name'] }}</td>
                                        <td class="text-end fw-bold">{{ $part['qty'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="alert alert-info mt-4 mb-0">
                            <i class="bi bi-info-circle"></i> Project ini tidak memiliki part.
                        </div>
                    @endif
                    <a href="{{ route('final_project_summary.index') }}" class="btn btn-outline-secondary mt-3">
                        &larr; Back to Final Project Summary
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
