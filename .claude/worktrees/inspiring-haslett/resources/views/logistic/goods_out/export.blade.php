<table>
    <thead>
        <tr>
            <th>Material</th>
            <th>Qty</th>
            <th>Unit</th>
            <th>For Project</th>
            <th>Requested By</th>
            <th>Proceed At</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($goodsOuts as $goodsOut)
            <tr>
                <td>{{ $goodsOut->inventory->name ?? '(no material)' }}</td>
                <td>{{ $goodsOut->quantity ?? 0 }}</td>
                <td>{{ $goodsOut->inventory->unit ?? '(no unit)' }}</td>
                <td>{{ $goodsOut->project->name ?? '(no project)' }}</td>
                <td>{{ ucfirst($goodsOut->requested_by) }}</td>
                <td>{{ $goodsOut->created_at->format('d-m-Y, H:i') }}</td>
                <td>{{ $goodsOut->remark ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
