@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center gap-2 mb-3">
                    <!-- Header -->
                    <h2 class="mb-2 mb-lg-0 flex-shrink-0" style="font-size:1.5rem;"> Timing
                        {{ $employee->name }}
                    </h2>
                </div>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Project</th>
                            <th>Department</th>
                            <th>Step</th>
                            <th>Parts</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Qty</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($timings as $timing)
                            <tr>
                                <td>{{ $timing->tanggal }}</td>
                                <td>{{ $timing->project->name ?? '-' }}</td>
                                <td>
                                    {{ $timing->project && $timing->project->department ? $timing->project->department->name : '-' }}
                                </td>
                                <td>{{ $timing->step }}</td>
                                <td>{{ $timing->parts }}</td>
                                <td>{{ $timing->start_time }}</td>
                                <td>{{ $timing->end_time }}</td>
                                <td>{{ $timing->output_qty }}</td>
                                <td>
                                    @php
                                        $color = [
                                            'pending' => 'danger',
                                            'complete' => 'success',
                                            'on progress' => 'warning',
                                        ][$timing->status];
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst($timing->status) }}</span>
                                </td>
                                <td>{{ $timing->remarks }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                {{ $timings->links() }}
            </div>
        </div>
    </div>
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {
            $('.table').DataTable({
                pageLength: 25,
                lengthMenu: [10, 25, 50, 100],
                order: [
                    []
                ],
                language: {
                    emptyTable: "No timing data found",
                    zeroRecords: "No timing data found"
                }
            });
        });
    </script>
@endpush
