<table>
    <thead>
        <tr>
            <th>Material</th>
            <th>Project</th>
            <th>Goods Out Qty</th>
            <th>Goods In Qty</th>
            <th>Used Qty</th>
            <th>Unit</th>
            <th>Updated At</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($usages as $usage)
            <tr>
                <td>{{ $usage['material'] }}</td>
                <td>{{ $usage['project'] }}</td>
                <td>{{ $usage['goods_out_qty'] }}</td>
                <td>{{ $usage['goods_in_qty'] }}</td>
                <td>{{ $usage['used_qty'] }}</td>
                <td>{{ $usage['unit'] }}</td>
                <td>{{ $usage['updated_at'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
