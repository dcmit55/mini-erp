<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Department Timing Configurations
    |--------------------------------------------------------------------------
    |
    | Define department-specific input fields untuk timing form.
    | Setiap department bisa punya input fields yang berbeda sesuai kebutuhan.
    | Data akan disimpan di column 'department_specific_data' (JSON).
    |
    */

    'mascot' => [
        'name' => 'Mascot',
        'common_steps' => ['Cutting', 'Sewing', 'Gluing', 'Assembling', 'Finishing'],
        'common_parts' => ['Head', 'Body', 'Arms', 'Legs', 'Accessories'],
        'specific_fields' => [
            [
                'name' => 'foam_type',
                'label' => 'Foam Type',
                'type' => 'select',
                'options' => ['EVA', 'PE', 'Polyurethane', 'Memory Foam'],
                'required' => false,
            ],
            [
                'name' => 'foam_density',
                'label' => 'Foam Density',
                'type' => 'select',
                'options' => ['Low', 'Medium', 'High', 'Extra High'],
                'required' => false,
            ],
            [
                'name' => 'foam_thickness',
                'label' => 'Foam Thickness (mm)',
                'type' => 'number',
                'min' => 1,
                'max' => 100,
                'required' => false,
            ],
            [
                'name' => 'fur_type',
                'label' => 'Fur Type',
                'type' => 'select',
                'options' => ['Short Pile', 'Long Pile', 'Shaggy', 'Plush'],
                'required' => false,
            ],
        ],
        'qty_label' => 'Pieces Completed',
        'qty_type' => 'count',
        'qty_unit' => 'pieces',
        'measurement_type' => 'quantity',
        'qty_step' => 1,
        'qty_min' => 0,
    ],

    'animatronics' => [
        'name' => 'Animatronics',
        'common_steps' => ['Design', 'Fabrication', 'Electronics', 'Programming', 'Assembly', 'Testing'],
        'common_parts' => ['Head Mechanism', 'Eye Mechanism', 'Jaw Mechanism', 'Body Frame', 'Control System'],
        'specific_fields' => [
            [
                'name' => 'motor_type',
                'label' => 'Motor Type',
                'type' => 'select',
                'options' => ['Servo', 'Stepper', 'DC Motor', 'Linear Actuator'],
                'required' => false,
            ],
            [
                'name' => 'voltage',
                'label' => 'Voltage (V)',
                'type' => 'select',
                'options' => ['5V', '12V', '24V', '48V'],
                'required' => false,
            ],
            [
                'name' => 'current_consumption',
                'label' => 'Current Consumption (A)',
                'type' => 'number',
                'step' => 0.1,
                'required' => false,
            ],
            [
                'name' => 'control_system',
                'label' => 'Control System',
                'type' => 'select',
                'options' => ['Arduino', 'PLC', 'Custom PCB', 'Raspberry Pi'],
                'required' => false,
            ],
            [
                'name' => 'programming_language',
                'label' => 'Programming Language',
                'type' => 'select',
                'options' => ['C++', 'Python', 'Ladder Logic', 'JavaScript'],
                'required' => false,
            ],
            [
                'name' => 'previous_progress',
                'label' => 'Previous Progress (%)',
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'step' => 0.1,
                'required' => false,
                'readonly' => true,
                'auto_fill' => true, // Auto-filled from last timing
            ],
            [
                'name' => 'current_progress',
                'label' => 'Current Total Progress (%)',
                'type' => 'number',
                'min' => 0,
                'max' => 100,
                'step' => 0.1,
                'required' => false,
                'readonly' => true,
                'auto_calculate' => true, // Auto-calculated: previous + added
            ],
        ],
        'qty_label' => 'Progress Added Today (%)',
        'qty_type' => 'percentage',
        'qty_unit' => '%',
        'measurement_type' => 'progress',
        'qty_step' => 0.1,
        'qty_min' => 0,
        'qty_max' => 100,
        'is_cumulative' => true,
        'track_previous_value' => true,
    ],

    'costume' => [
        'name' => 'Costume',
        'common_steps' => ['Pattern Making', 'Cutting', 'Sewing', 'Embellishment', 'Fitting', 'Finishing'],
        'common_parts' => ['Jacket', 'Pants', 'Shirt', 'Dress', 'Accessories', 'Hat'],
        'specific_fields' => [
            [
                'name' => 'fabric_type',
                'label' => 'Fabric Type',
                'type' => 'select',
                'options' => ['Cotton', 'Polyester', 'Silk', 'Velvet', 'Spandex', 'Leather'],
                'required' => false,
            ],
            [
                'name' => 'fabric_color',
                'label' => 'Fabric Color',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'pattern_type',
                'label' => 'Pattern Type',
                'type' => 'select',
                'options' => ['Standard', 'Custom', 'Modified', 'Original Design'],
                'required' => false,
            ],
            [
                'name' => 'stitch_type',
                'label' => 'Stitch Type',
                'type' => 'select',
                'options' => ['Straight', 'Zigzag', 'Overlock', 'Blind Hem', 'Decorative'],
                'required' => false,
            ],
            [
                'name' => 'embellishment',
                'label' => 'Embellishment',
                'type' => 'text',
                'placeholder' => 'e.g., Sequins, Beads, Embroidery',
                'required' => false,
            ],
        ],
        'qty_label' => 'Pieces Completed',
        'qty_type' => 'count',
        'qty_unit' => 'pieces',
        'measurement_type' => 'quantity',
        'qty_step' => 1,
        'qty_min' => 0,
    ],

    'welding' => [
        'name' => 'Welding',
        'common_steps' => ['Preparation', 'Tack Welding', 'Full Welding', 'Grinding', 'Inspection'],
        'common_parts' => ['Frame', 'Support', 'Joint', 'Panel', 'Structure'],
        'specific_fields' => [
            [
                'name' => 'welding_method',
                'label' => 'Welding Method',
                'type' => 'select',
                'options' => ['MIG', 'TIG', 'Stick', 'Flux-Core', 'Spot Welding'],
                'required' => false,
            ],
            [
                'name' => 'material',
                'label' => 'Material',
                'type' => 'select',
                'options' => ['Mild Steel', 'Stainless Steel', 'Aluminum', 'Cast Iron'],
                'required' => false,
            ],
            [
                'name' => 'material_thickness',
                'label' => 'Material Thickness (mm)',
                'type' => 'number',
                'step' => 0.5,
                'required' => false,
            ],
            [
                'name' => 'electrode_size',
                'label' => 'Electrode Size',
                'type' => 'text',
                'required' => false,
            ],
            [
                'name' => 'gas_type',
                'label' => 'Shielding Gas',
                'type' => 'select',
                'options' => ['Argon', 'CO2', 'Argon/CO2 Mix', 'Helium', 'None'],
                'required' => false,
            ],
        ],
        'qty_label' => 'Joints Completed',
        'qty_type' => 'count',
        'qty_unit' => 'joints',
        'measurement_type' => 'quantity',
        'qty_step' => 1,
        'qty_min' => 0,
    ],

    'painting' => [
        'name' => 'Painting & Finishing',
        'common_steps' => ['Surface Prep', 'Priming', 'Base Coat', 'Detail Paint', 'Clear Coat', 'Drying'],
        'common_parts' => ['Body', 'Head', 'Accessories', 'Props', 'Details'],
        'specific_fields' => [
            [
                'name' => 'paint_type',
                'label' => 'Paint Type',
                'type' => 'select',
                'options' => ['Acrylic', 'Enamel', 'Lacquer', 'Spray Paint', 'Airbrush'],
                'required' => false,
            ],
            [
                'name' => 'paint_finish',
                'label' => 'Paint Finish',
                'type' => 'select',
                'options' => ['Matte', 'Satin', 'Semi-Gloss', 'Gloss', 'Metallic'],
                'required' => false,
            ],
            [
                'name' => 'color_code',
                'label' => 'Color Code',
                'type' => 'text',
                'placeholder' => 'e.g., RAL 9010, Pantone 123',
                'required' => false,
            ],
            [
                'name' => 'layers',
                'label' => 'Number of Layers',
                'type' => 'number',
                'min' => 1,
                'required' => false,
            ],
            [
                'name' => 'technique',
                'label' => 'Painting Technique',
                'type' => 'select',
                'options' => ['Brush', 'Roller', 'Spray Gun', 'Airbrush', 'Mixed'],
                'required' => false,
            ],
        ],
        'qty_label' => 'Area Painted (m²)',
        'qty_type' => 'measurement',
        'qty_unit' => 'm²',
        'measurement_type' => 'area',
        'qty_step' => 0.01,
        'qty_min' => 0,
    ],

    'electronics' => [
        'name' => 'Electronics',
        'common_steps' => ['PCB Design', 'Soldering', 'Assembly', 'Wiring', 'Testing', 'Programming'],
        'common_parts' => ['Control Board', 'Power Supply', 'Sensor Module', 'LED System', 'Wiring Harness'],
        'specific_fields' => [
            [
                'name' => 'pcb_type',
                'label' => 'PCB Type',
                'type' => 'select',
                'options' => ['Single-sided', 'Double-sided', 'Multi-layer', 'Flexible PCB'],
                'required' => false,
            ],
            [
                'name' => 'component_count',
                'label' => 'Component Count',
                'type' => 'number',
                'required' => false,
            ],
            [
                'name' => 'soldering_method',
                'label' => 'Soldering Method',
                'type' => 'select',
                'options' => ['Hand Soldering', 'Reflow', 'Wave Soldering', 'SMD'],
                'required' => false,
            ],
            [
                'name' => 'power_rating',
                'label' => 'Power Rating (W)',
                'type' => 'number',
                'required' => false,
            ],
            [
                'name' => 'microcontroller',
                'label' => 'Microcontroller',
                'type' => 'select',
                'options' => ['Arduino', 'ESP32', 'STM32', 'PIC', 'Raspberry Pi', 'Custom'],
                'required' => false,
            ],
        ],
        'qty_label' => 'Boards/Modules Completed',
        'qty_type' => 'count',
        'qty_unit' => 'units',
        'measurement_type' => 'quantity',
        'qty_step' => 1,
        'qty_min' => 0,
    ],

    // Default configuration untuk department yang belum didefinisikan
    'default' => [
        'name' => 'General Production',
        'common_steps' => ['Preparation', 'Processing', 'Assembly', 'Finishing', 'Quality Check'],
        'common_parts' => ['Component A', 'Component B', 'Component C'],
        'specific_fields' => [
            [
                'name' => 'notes',
                'label' => 'Additional Notes',
                'type' => 'textarea',
                'required' => false,
            ],
        ],
        'qty_label' => 'Quantity Completed',
        'qty_type' => 'count',
        'qty_unit' => 'units',
        'measurement_type' => 'quantity',
        'qty_step' => 1,
        'qty_min' => 0,
    ],
];
