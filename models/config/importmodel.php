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
                'importRecords' => [
                    'label' => 'lang:igniter.importexport::default.button_import_records',
                    'class' => 'btn btn-primary',
                    'data-request' => 'onImport',
                ],
            ],
        ],
        'fields' => [
//            'topbar' => [
//                'type' => 'partial',
//                'path' => '~/extensions/igniter/importexport/actions/importexportcontroller/topbar',
//            ],
            'step_primary' => [
                'label' => 'lang:igniter.importexport::default.text_tab_title_import_primary',
                'type' => 'section',
            ],
            'import_file' => [
                'label' => 'lang:igniter.importexport::default.label_import_file',
                'type' => 'partial',
                'path' => '~/extensions/igniter/importexport/actions/importexportcontroller/import_file_upload',
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
                'type' => 'repeater',
                'showAddButton' => FALSE,
                'showRemoveButton' => FALSE,
                'emptyMessage' => 'lang:igniter.importexport::default.text_no_import_file',
                'form' => [
                    'fields' => [
                        'import' => [
                            'label' => 'lang:igniter.importexport::default.label_import_ignore',
                            'type' => 'checkbox',
                            'options' => [],
                        ],
                        'file_column' => [
                            'label' => 'lang:igniter.importexport::default.label_db_columns',
                            'type' => 'text',
                        ],
                        'db_field' => [
                            'label' => 'lang:igniter.importexport::default.label_file_columns',
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ],
    ],
];