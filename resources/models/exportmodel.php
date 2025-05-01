<?php

return [
    'form' => [
        'toolbar' => [
            'buttons' => [
                'exportRecords' => [
                    'label' => 'lang:igniterlabs.importexport::default.button_export_records',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onExport',
                    'data-progress-indicator' => 'igniterlabs.importexport::default.text_processing',
                ],
            ],
        ],
        'fields' => [
            'step_primary' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_export_primary',
                'type' => 'section',
            ],
            'offset' => [
                'label' => 'lang:igniterlabs.importexport::default.label_offset',
                'type' => 'number',
                'span' => 'left',
                'comment' => 'lang:igniterlabs.importexport::default.help_offset',
            ],
            'limit' => [
                'label' => 'lang:igniterlabs.importexport::default.label_limit',
                'type' => 'number',
                'span' => 'right',
                'comment' => 'lang:igniterlabs.importexport::default.help_limit',
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
                'span' => 'left',
                'default' => 1,
            ],
            'step_columns' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_export_columns',
                'type' => 'section',
            ],
            'export_columns' => [
                'label' => 'lang:igniterlabs.importexport::default.label_columns',
                'type' => 'partial',
                'path' => 'igniterlabs.importexport::export_columns',
            ],
            'step_secondary' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_export_secondary',
                'type' => 'section',
            ],
        ],
    ],
];
