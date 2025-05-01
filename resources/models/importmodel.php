<?php

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
            ],
        ],
        'fields' => [
            'step_primary' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_import_primary',
                'type' => 'section',
            ],
            'import_file' => [
                'label' => 'lang:igniterlabs.importexport::default.label_import_file',
                'type' => 'partial',
                'path' => 'igniterlabs.importexport::import_file_upload',
                'span' => 'left',
            ],
            'encoding' => [
                'label' => 'lang:igniterlabs.importexport::default.label_encoding',
                'type' => 'select',
                'span' => 'right',
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
            'first_row_titles' => [
                'label' => 'lang:igniterlabs.importexport::default.label_include_headers',
                'type' => 'switch',
                'cssClass' => 'flex-width',
                'comment' => 'lang:igniterlabs.importexport::default.help_include_headers',
                'span' => 'left',
                'default' => 0,
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
