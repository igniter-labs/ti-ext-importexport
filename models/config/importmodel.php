<?php

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'importRecords' => [
                    'label' => 'lang:igniter.importexport::default.button_import_records',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onImport',
                    'data-progress-indicator' => 'igniter.importexport::default.text_processing',
                ],
            ],
        ],
        'fields' => [
            'step_primary' => [
                'label' => 'lang:igniter.importexport::default.text_tab_title_import_primary',
                'type' => 'section',
            ],
            'import_file' => [
                'label' => 'lang:igniter.importexport::default.label_import_file',
                'type' => 'partial',
                'path' => '$/igniter/importexport/actions/importexportcontroller/import_file_upload',
                'span' => 'left',
            ],
            'encoding' => [
                'label' => 'lang:igniter.importexport::default.label_encoding',
                'type' => 'select',
                'span' => 'right',
            ],
            'delimiter' => [
                'label' => 'lang:igniter.importexport::default.label_delimiter',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => ',',
                'comment' => 'lang:igniter.importexport::default.help_delimiter',
            ],
            'enclosure' => [
                'label' => 'lang:igniter.importexport::default.label_enclosure',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => '"',
                'comment' => 'lang:igniter.importexport::default.help_enclosure',
            ],
            'escape' => [
                'label' => 'lang:igniter.importexport::default.label_escape',
                'type' => 'text',
                'span' => 'left',
                'cssClass' => 'flex-width',
                'default' => '\\',
            ],
            'step_columns' => [
                'label' => 'lang:igniter.importexport::default.text_tab_title_import_columns',
                'type' => 'section',
            ],
            'import_columns' => [
                'label' => 'lang:igniter.importexport::default.label_import_columns',
                'type' => 'partial',
                'cssClass' => 'mb-0',
                'path' => '$/igniter/importexport/actions/importexportcontroller/import_columns',
                'emptyMessage' => 'lang:igniter.importexport::default.text_no_import_file',
            ],
        ],
    ],
];