<table>
    <thead>
        <tr>
            <th>Project</th>
            <th>Job Order</th>
            <th>Material</th>
            <th>Requested Qty</th>
            <th>Unit</th>
            <th>Remaining Qty</th>
            <th>Processed Qty</th>
            <th>Requested By</th>
            <th>Requested At</th>
            <th>Status</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($requests as $req)
            <tr>
                <td>{{ $req->project->name ?? '(no project)' }}</td>
                <td>{{ $req->jobOrder->name ?? '-' }}</td>
                <td>{{ $req->inventory->name ?? '(no material)' }}</td>
                <td>{{ $req->qty }}</td>
                <td>{{ $req->inventory->unit ?? '(no unit)' }}</td>
                <td>{{ $req->remaining_qty }}</td>
                <td>{{ $req->processed_qty }}</td>
                <td>{{ ucfirst($req->requested_by) }}</td>
                <td>{{ $req->created_at->format('d-m-Y, H:i') }}</td>
                <td>{{ ucfirst($req->status) }}</td>
                <td>{{ $req->remark ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
