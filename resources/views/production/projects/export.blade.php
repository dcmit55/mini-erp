<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Quantity</th>
            <th>Department</th>
            <th>Start Date</th>
            <th>Deadline</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($projects as $project)
            <tr>
                <td>{{ $project->name }}</td>
                <td>{{ $project->qty }}</td>
                <td>{{ $project->departments->pluck('name')->map(fn($name) => ucwords(str_replace('&', ' & ', $name)))->implode(', ') }}
                </td>
                <td>{{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->translatedFormat('d F Y') : '-' }}
                </td>
                <td>{{ $project->deadline ? \Carbon\Carbon::parse($project->deadline)->translatedFormat('d F Y') : '-' }}
                </td>
            </tr>
        @endforeach
    </tbody>
</table>
