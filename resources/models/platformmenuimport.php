<?php

declare(strict_types=1);

return [
    'fields' => [
        'update_existing' => [
            'label' => 'lang:igniterlabs.importexport::default.label_update_existing_menus',
            'type' => 'switch',
            'default' => true,
        ],
        'import_options' => [
            'label' => 'lang:igniterlabs.importexport::default.label_import_options',
            'type' => 'switch',
            'default' => true,
        ],
        'assign_locations' => [
            'label' => 'lang:igniterlabs.importexport::default.label_assign_locations',
            'type' => 'selectlist',
            'comment' => 'lang:igniterlabs.importexport::default.help_assign_locations',
        ],
    ],
];
