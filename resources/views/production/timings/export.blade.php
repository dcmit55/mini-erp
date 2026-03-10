<!-- filepath: d:\Inventorypart2\resources\views\timings\export.blade.php -->
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Job Order</th>
            <th>Project</th>
            <th>Department</th>
            <th>Step</th>
            <th>Parts</th>
            <th>Employee</th>
            <th>Start</th>
            <th>End</th>
            <th>Duration</th>
            <th>Value</th>
            <th>Type</th>
            <th>Status</th>
            <th>Approval</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($timings as $timing)
            <tr>
                <td>{{ $timing->tanggal ?? '-' }}</td>
                <td>{{ $timing->jobOrder ? $timing->jobOrder->name : '-' }}</td>
                <td>{{ $timing->project ? $timing->project->name : '-' }}</td>
                <td>{{ $timing->project && $timing->project->departments ? $timing->project->departments->pluck('name')->implode(', ') : '-' }}
                </td>
                <td>{{ $timing->step ?? '-' }}</td>
                <td>{{ $timing->parts ?? '-' }}</td>
                <td>{{ $timing->employee ? $timing->employee->name : '-' }}</td>
                <td>{{ $timing->start_time ?? '-' }}</td>
                <td>{{ $timing->end_time ?? '-' }}</td>
                <td>{{ $timing->duration_formatted ?? '-' }}</td>
                <td>{{ $timing->measurement_value ?? 0 }}</td>
                <td>{{ $timing->measurement_type ?? '-' }}</td>
                <td>{{ $timing->status ?? '-' }}</td>
                <td>{{ $timing->approval_status ?? '-' }}</td>
                <td>{{ strip_tags($timing->remarks ?? '-') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
