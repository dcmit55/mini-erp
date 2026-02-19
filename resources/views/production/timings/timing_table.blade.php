@forelse($timings as $timing)
    <tr>
        {{-- Date --}}
        <td>{{ $timing->tanggal ? \Carbon\Carbon::parse($timing->tanggal)->format('d M Y') : '-' }}</td>

        {{-- Project --}}
        <td>{{ $timing->project->name ?? '-' }}</td>

        {{-- Job Order --}}
        <td>{{ $timing->jobOrder->name ?? ($timing->job_order_id ?? '-') }}</td>

        {{-- Department --}}
        <td>
            @if ($timing->employee && $timing->employee->department)
                <span class="badge bg-secondary">{{ $timing->employee->department->name }}</span>
            @else
                <span class="text-muted">-</span>
            @endif
        </td>

        {{-- Step --}}
        <td>{{ $timing->step }}</td>

        {{-- Parts --}}
        <td>{{ $timing->parts }}</td>

        {{-- Employee --}}
        <td>{{ $timing->employee->name ?? '-' }}</td>

        {{-- Start --}}
        <td>{{ $timing->start_time ? \Carbon\Carbon::parse($timing->start_time)->format('H:i') : '-' }}</td>

        {{-- End --}}
        <td>{{ $timing->end_time ? \Carbon\Carbon::parse($timing->end_time)->format('H:i') : '-' }}</td>

        {{-- Duration Hours --}}
        <td>
            @if ($timing->duration_hours)
                {{ $timing->duration_readable }}
            @else
                -
            @endif
        </td>

        {{-- Measurement Value --}}
        <td>{{ $timing->measurement_value ? number_format($timing->measurement_value, 2) : '-' }}</td>

        {{-- Measurement Type --}}
        <td>
            @if ($timing->measurement_type)
                <span class="badge bg-info">{{ strtoupper($timing->measurement_type) }}</span>
            @else
                -
            @endif
        </td>

        {{-- Status --}}
        <td>
            @php
                $color =
                    [
                        'pending' => 'danger',
                        'complete' => 'success',
                        'on progress' => 'warning',
                    ][$timing->status] ?? 'secondary';
            @endphp
            <span class="badge bg-{{ $color }}">{{ ucfirst($timing->status) }}</span>
        </td>

        {{-- Remarks --}}
        <td>{{ $timing->remarks ?? '-' }}</td>

        {{-- Action --}}
        <td class="text-center">
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
        <td colspan="15" class="text-center text-muted py-4">
            <i class="bi bi-inbox" style="font-size: 2rem;"></i>
            <p class="mt-2 mb-0">No timing data found.</p>
        </td>
    </tr>
@endforelse
