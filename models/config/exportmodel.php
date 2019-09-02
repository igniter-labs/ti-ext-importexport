<?php

return [
//    'list' => [
//        'filter' => [],
//        'toolbar' => [
//            'buttons' => [
//                'create' => [
//                    'label' => 'lang:admin::lang.button_new',
//                    'class' => 'btn btn-primary',
//                    'href' => 'igniter/importexport/{lower_plural_name}/create'
//                ],
//                'delete' => [
//                    'label' => 'lang:admin::lang.button_delete',
//                    'class' => 'btn btn-danger',
//                    'data-request-form' => '#list-form',
//                    'data-request' => 'onDelete',
//                    'data-request-data' => "_method:'DELETE'",
//                    'data-request-confirm' => 'lang:admin::lang.alert_warning_confirm'
//                ],
//            ],
//        ],
//        'columns' => [
//            'id' => [
//                'label' => 'ID'
//            ]
//        ],
//    ],
    'form' => [
        'toolbar' => [
            'buttons' => [
                'exportRecords' => [
                    'label' => 'lang:igniter.importexport::default.button_export_records',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onExport',
                ],
            ],
        ],
        'fields' => [
//            'topbar' => [
//                'type' => 'partial',
//                'path' => '~/extensions/igniter/importexport/actions/importexportcontroller/topbar',
//            ],
            'step_primary' => [
                'label' => 'lang:igniter.importexport::default.text_tab_title_export_primary',
                'type' => 'section',
            ],
            'offset' => [
                'label' => 'lang:igniter.importexport::default.label_offset',
                'type' => 'number',
                'span' => 'left',
                'comment' => 'lang:igniter.importexport::default.help_offset',
            ],
            'limit' => [
                'label' => 'lang:igniter.importexport::default.label_limit',
                'type' => 'number',
                'span' => 'right',
                'comment' => 'lang:igniter.importexport::default.help_limit',
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
                'label' => 'lang:igniter.importexport::default.text_tab_title_export_columns',
                'type' => 'section',
            ],
            'export_columns' => [
                'label' => 'lang:igniter.importexport::default.label_columns',
                'type' => 'partial',
                'path' => '~/extensions/igniter/importexport/actions/importexportcontroller/export_columns',
            ],
            'step_secondary' => [
                'label' => 'lang:igniter.importexport::default.text_tab_title_export_secondary',
                'type' => 'section',
            ],
        ],
    ],
];