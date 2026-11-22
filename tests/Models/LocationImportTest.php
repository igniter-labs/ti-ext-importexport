<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\Local\Models\Location;
use Igniter\System\Models\Country;
use IgniterLabs\ImportExport\Models\LocationImport;

it('creates locations resolving countries and coordinates', function(): void {
    $defaultLocationId = Location::getDefaultKey();
    $country = Country::query()->where('iso_code_2', 'GB')->first();
    $country?->update(['country_name' => 'United Kingdom']);

    $import = new LocationImport(['update_existing' => true]);
    $import->importData([
        [
            'location_name' => 'Canary Wharf',
            'location_email' => 'cw@example.com',
            'location_address_1' => '10 Bank St',
            'location_city' => 'London',
            'location_postcode' => 'E14 5JP',
            'location_country' => 'united kingdom',
            'location_lat' => '51.5049',
            'location_lng' => '-0.0195',
            'location_status' => 'yes',
        ],
        [
            'location_name' => 'Shoreditch',
            'location_country' => 'ZZ',
            'location_status' => 'no',
        ],
    ]);

    $canaryWharf = Location::query()->firstWhere('location_name', 'Canary Wharf');
    $shoreditch = Location::query()->firstWhere('location_name', 'Shoreditch');

    expect($import->getResultStats())->toMatchArray(['created' => 2, 'errorCount' => 0])
        ->and($canaryWharf->location_country_id)->toBe($country?->getKey())
        ->and($canaryWharf->location_lat)->toBe(51.5049)
        ->and($canaryWharf->is_auto_lat_lng)->toBe(0)
        ->and($canaryWharf->location_status)->toBeTrue()
        ->and($canaryWharf->is_default)->toBeFalse()
        ->and($shoreditch->location_email)->toBe('')
        ->and($shoreditch->location_status)->toBeFalse()
        ->and($shoreditch->location_country_id)->toBe(Country::getDefaultKey())
        ->and(Location::getDefaultKey())->toBe($defaultLocationId);
});

it('updates existing location matched by name', function(): void {
    $location = Location::factory()->create(['location_name' => 'Old Town', 'location_telephone' => '111']);

    $import = new LocationImport(['update_existing' => true]);
    $import->importData([[
        'location_name' => 'Old Town',
        'location_telephone' => '222',
    ]]);

    $location->refresh();
    expect($import->getResultStats())->toMatchArray(['updated' => 1])
        ->and($location->location_telephone)->toBe('222');
});

it('logs errors for invalid rows and existing locations when not updating', function(): void {
    Location::factory()->create(['location_name' => 'Old Town']);

    $import = new LocationImport(['update_existing' => false]);
    $import->importData([
        ['location_name' => 'Old Town'],
        ['location_name' => ''],
        ['location_name' => 'Bad Coordinates', 'location_lat' => '200'],
    ]);

    $stats = $import->getResultStats();
    expect($stats['errorCount'])->toBe(3)
        ->and($stats['errors'][0])->toBe(sprintf(lang('igniterlabs.importexport::default.error_location_exists'), 'Old Town'));
});
