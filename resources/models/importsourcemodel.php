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
            'step_preview' => [
                'label' => 'lang:igniterlabs.importexport::default.text_tab_title_import_preview',
                'type' => 'section',
            ],
            'import_preview' => [
                'type' => 'partial',
                'cssClass' => 'mb-0',
                'path' => 'igniterlabs.importexport::import_preview',
            ],
        ],
    ],
];
