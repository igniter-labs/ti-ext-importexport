<?php

declare(strict_types=1);

return [
    'columns' => [
        'location_name' => 'lang:igniterlabs.importexport::default.columns.location_name',
        'location_email' => 'lang:igniterlabs.importexport::default.columns.email',
        'location_telephone' => 'lang:igniterlabs.importexport::default.columns.telephone',
        'description' => 'lang:igniterlabs.importexport::default.columns.description',
        'location_address_1' => 'lang:igniterlabs.importexport::default.columns.address_1',
        'location_address_2' => 'lang:igniterlabs.importexport::default.columns.address_2',
        'location_city' => 'lang:igniterlabs.importexport::default.columns.city',
        'location_state' => 'lang:igniterlabs.importexport::default.columns.state',
        'location_postcode' => 'lang:igniterlabs.importexport::default.columns.postcode',
        'location_country' => 'lang:igniterlabs.importexport::default.columns.country',
        'location_lat' => 'lang:igniterlabs.importexport::default.columns.latitude',
        'location_lng' => 'lang:igniterlabs.importexport::default.columns.longitude',
        'location_status' => 'lang:igniterlabs.importexport::default.columns.status',
    ],
    'fields' => [
        'update_existing' => [
            'label' => 'lang:igniterlabs.importexport::default.label_update_existing_locations',
            'type' => 'switch',
            'default' => true,
        ],
    ],
];
