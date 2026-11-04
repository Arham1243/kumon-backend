<?php

return [
    'entity_actions' => [
        'reports.email-logs' => ['view'],
        'reports.audit-log' => ['view'],
        'reports.student-cards' => ['view'],
    ],

    'disabled_matrix_actions' => [
        'reports.email-logs' => ['create', 'edit', 'delete'],
        'reports.audit-log' => ['create', 'edit', 'delete'],
        'reports.student-cards' => ['create', 'edit', 'delete'],
    ],

    'matrix_sections' => [
        [
            'title' => 'Reports',
            'entities' => [
                'reports.email-logs',
                'reports.audit-log',
                'reports.student-cards',
            ],
            'child_indent' => true,
            'order' => 30,
            'preset_key' => 'reports',
        ],
    ],
];
