<?php

return [
    'columns' => [
        'menu_id' => 'lang:admin::lang.column_id',
        'menu_name' => 'lang:admin::lang.label_name',
        'menu_price' => 'lang:igniter.cart::default.menus.label_price',
        'menu_description' => 'lang:admin::lang.label_description',
        'minimum_qty' => 'lang:igniter.cart::default.menus.label_minimum_qty',
        'categories' => 'lang:igniter.cart::default.menus.label_category',
        'menu_status' => 'lang:admin::lang.label_status',
    ],
    'fields' => [
        'update_existing' => [
            'label' => 'Update existing menu items',
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
