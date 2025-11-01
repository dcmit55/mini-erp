@forelse($timings as $timing)
    <tr>
        <td>{{ $timing->tanggal }}</td>
        <td>{{ $timing->project->name ?? '-' }}</td>
        <td>{{ $timing->project->department->name }}</td>
        <td>{{ $timing->step }}</td>
        <td>{{ $timing->parts }}</td>
        <td>{{ $timing->employee->name ?? '-' }}</td>
        <td>{{ \Carbon\Carbon::parse($timing->start_time)->format('H:i') }}</td>
        <td>{{ \Carbon\Carbon::parse($timing->end_time)->format('H:i') }}</td>
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
        <td>
            @if (auth()->user()->canModifyData())
                <a href="{{ route('timings.edit', $timing->id) }}" class="btn btn-sm btn-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>
            @endif
            @if (auth()->user()->isSuperAdmin())
                <form action="{{ route('timings.destroy', $timing->id) }}" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" title="Delete"
                        onclick="return confirm('Are you sure you want to delete this timing data?')">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            @endif
        </td>
    </tr>
@empty
    <tr class="no-data-row">
        <td colspan="12" class="text-center text-muted py-4">
            No timing data found.
        </td>
    </tr>
@endforelse
