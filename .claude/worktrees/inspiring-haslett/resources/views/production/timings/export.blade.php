<!-- filepath: d:\Inventorypart2\resources\views\timings\export.blade.php -->
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Project</th>
            <th>Step</th>
            <th>Parts</th>
            <th>Employee</th>
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
                <td>{{ $timing->tanggal ?? '-' }}</td>
                <td>{{ $timing->project ? $timing->project->name : '-' }}</td>
                <td>{{ $timing->step ?? '-' }}</td>
                <td>{{ $timing->parts ?? '-' }}</td>
                <td>{{ $timing->employee ? $timing->employee->name : '-' }}</td>
                <td>{{ $timing->start_time ?? '-' }}</td>
                <td>{{ $timing->end_time ?? '-' }}</td>
                <td>{{ $timing->output_qty ?? 0 }}</td>
                <td>{{ $timing->status ?? '-' }}</td>
                <td>{{ strip_tags($timing->remarks ?? '-') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
