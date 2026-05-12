@php
    $map = [
        'pending'      => ['bg-warning text-dark', 'Pending'],
        'under_review' => ['bg-info text-dark',    'Under Review'],
        'approved'     => ['bg-success',           'Approved'],
        'rejected'     => ['bg-danger',            'Rejected'],
        'disbursed'    => ['bg-primary',           'Disbursed'],
        'repaying'     => ['bg-purple text-white', 'Repaying'],
        'settled'      => ['bg-secondary',         'Settled'],
    ];
    [$cls, $lbl] = $map[$status] ?? ['bg-secondary', ucfirst($status)];
@endphp
<span class="badge {{ $cls }} rounded-2">{{ $lbl }}</span>
