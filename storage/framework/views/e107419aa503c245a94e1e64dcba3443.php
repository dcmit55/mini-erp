

<?php
    // Mapping department ke warna badge
    $departmentColors = [
        'Animatronic' => 'bg-orangey',
        'marketing' => 'bg-success',
        'sales' => 'bg-info',
        'logistics' => 'bg-warning text-dark',
        'finance' => 'bg-danger',
        'hr' => 'bg-secondary',
        'it' => 'bg-dark',
        'mascot' => 'bg-purple',
        'r&d' => 'bg-teal',
        '' => 'bg-orange',
    ];

    $department = $department ?? '';
    $departmentLower = strtolower(trim($department));

    // Cari warna berdasarkan keyword
    $badgeClass = 'bg-secondary'; // Default
    foreach ($departmentColors as $keyword => $color) {
        if (str_contains($departmentLower, $keyword)) {
            $badgeClass = $color;
            break;
        }
    }
?>

<?php if(!empty($department)): ?>
    <span class="badge <?php echo e($badgeClass); ?> rounded-pill">
        <?php echo e($department); ?>

    </span>
<?php else: ?>
    <span class="text-muted">-</span>
<?php endif; ?>
<?php /**PATH D:\27JAN\resources\views/components/department-badge.blade.php ENDPATH**/ ?>