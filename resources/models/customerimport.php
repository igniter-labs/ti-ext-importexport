<?php

declare(strict_types=1);

return [
    'columns' => [
        'email' => 'lang:igniterlabs.importexport::default.columns.email',
        'first_name' => 'lang:igniterlabs.importexport::default.columns.first_name',
        'last_name' => 'lang:igniterlabs.importexport::default.columns.last_name',
        'full_name' => 'lang:igniterlabs.importexport::default.columns.full_name',
        'telephone' => 'lang:igniterlabs.importexport::default.columns.telephone',
        'newsletter' => 'lang:igniterlabs.importexport::default.columns.newsletter',
        'status' => 'lang:igniterlabs.importexport::default.columns.status',
        'customer_group' => 'lang:igniterlabs.importexport::default.columns.customer_group',
        'address_1' => 'lang:igniterlabs.importexport::default.columns.address_1',
        'address_2' => 'lang:igniterlabs.importexport::default.columns.address_2',
        'city' => 'lang:igniterlabs.importexport::default.columns.city',
        'state' => 'lang:igniterlabs.importexport::default.columns.state',
        'postcode' => 'lang:igniterlabs.importexport::default.columns.postcode',
        'country' => 'lang:igniterlabs.importexport::default.columns.country',
    ],
    'fields' => [
        'update_existing' => [
            'label' => 'lang:igniterlabs.importexport::default.label_update_existing_customers',
            'type' => 'switch',
            'default' => true,
        ],
    ],
];
