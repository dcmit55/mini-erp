<table>
    <thead>
        <tr>
            <th>Material</th>
            <th>Qty Returned</th>
            <th>Unit</th>
            <th>Project</th>
            <th>Returned By</th>
            <th>Returned At</th>
            <th>Remark</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($goodsIns as $goodsIn)
            <tr>
                <td>{{ $goodsIn->goodsOut->inventory->name ?? ($goodsIn->inventory->name ?? '(no material)') }}</td>
                <td>{{ $goodsIn->quantity }}</td>
                <td>{{ $goodsIn->goodsOut->inventory->unit ?? ($goodsIn->inventory->unit ?? '(no unit)') }}</td>
                <td>{{ $goodsIn->goodsOut->project->name ?? ($goodsIn->project->name ?? '(no project)') }}</td>
                <td>{{ ucfirst($goodsIn->returned_by) }}</td>
                <td>{{ $goodsIn->returned_at ? $goodsIn->returned_at->format('d-m-Y, H:i') : '-' }}</td>
                <td>{{ $goodsIn->remark ?? '-' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
