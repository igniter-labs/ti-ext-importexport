<?php

declare(strict_types=1);

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'importRecords' => [
                    'label' => 'lang:igniterlabs.importexport::default.button_import_records',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onImport',
                    'data-progress-indicator' => 'igniterlabs.importexport::default.text_processing',
                ],
                'cancelImport' => [
                    'label' => 'lang:igniterlabs.importexport::default.button_cancel_import',
                    'class' => 'btn btn-default',
                    'data-request' => 'onDeleteImportFile',
                    'data-progress-indicator' => 'igniterlabs.importexport::default.text_processing',
                ],
            ],
        ],
        'fields' => [
            'step_primary' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_import_primary',
                'type' => 'section',
            ],
            'encoding' => [
                'label' => 'lang:igniterlabs.importexport::default.label_encoding',
                'type' => 'select',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => 'utf-8',
                'disabled' => true,
            ],
            'delimiter' => [
                'label' => 'lang:igniterlabs.importexport::default.label_delimiter',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => ',',
                'comment' => 'lang:igniterlabs.importexport::default.help_delimiter',
            ],
            'enclosure' => [
                'label' => 'lang:igniterlabs.importexport::default.label_enclosure',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => '"',
                'comment' => 'lang:igniterlabs.importexport::default.help_enclosure',
            ],
            'escape' => [
                'label' => 'lang:igniterlabs.importexport::default.label_escape',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => '\\',
            ],
            'step_columns' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_import_columns',
                'type' => 'section',
            ],
            'import_columns' => [
                'label' => 'lang:igniterlabs.importexport::default.label_import_columns',
                'type' => 'partial',
                'cssClass' => 'mb-0',
                'path' => 'igniterlabs.importexport::import_columns',
                'emptyMessage' => 'lang:igniterlabs.importexport::default.text_no_import_file',
            ],
        ],
    ],
];
