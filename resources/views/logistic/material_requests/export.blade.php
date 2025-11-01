<table>
    <thead>
        <tr>
            <th>Project</th>
            <th>Material</th>
            <th>Requested Quantity</th>
            <th>Unit</th>
            <th>Processed Qty</th> <!-- Tambahkan kolom ini -->
            <th>Remaining Qty</th> <!-- Tambahkan kolom ini -->
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
                <td>{{ $req->inventory->name ?? '(no material)' }}</td>
                <td>{{ $req->qty }}</td>
                <td>{{ $req->inventory->unit ?? '(no unit)' }}</td>
                <td>{{ $req->processed_qty }}</td> <!-- Processed Qty -->
                <td>{{ $req->remaining_qty }}</td> <!-- Remaining Qty -->
                <td>{{ ucfirst($req->requested_by) }}</td>
                <td>{{ $req->created_at->format('d-m-Y, H:i') }}</td>
                <td>{{ ucfirst($req->status) }}</td>
                <td>{{ $req->remark ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
