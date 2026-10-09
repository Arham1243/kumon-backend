<?php

return [
    'full_crud_entities' => [
        'core.students',
        'core.attendance',
    ],

    'entity_actions' => [],

    'disabled_matrix_actions' => [],

    'matrix_sections' => [
        [
            'title' => 'Core',
            'entities' => [
                'core.students',
                'core.attendance',
            ],
            'child_indent' => false,
            'order' => 10,
        ],
    ],
];
