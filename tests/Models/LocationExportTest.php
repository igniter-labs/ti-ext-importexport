<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\Local\Models\Location;
use Igniter\System\Models\Country;
use IgniterLabs\ImportExport\Models\LocationExport;
use IgniterLabs\ImportExport\Models\LocationImport;

function locationExportColumns(): array
{
    return collect((require __DIR__.'/../../resources/models/locationexport.php')['columns'])
        ->map(fn(string $label): string => lang($label))
        ->all();
}

it('exports locations with country codes and coordinates', function(): void {
    $country = Country::query()->where('iso_code_2', 'GB')->first();
    $location = Location::factory()->create([
        'location_name' => 'Export Me',
        'location_email' => 'export@example.com',
        'location_address_1' => '1 Export Rd',
        'location_country_id' => $country?->getKey(),
        'location_lat' => 51.5,
        'location_lng' => -0.1,
        'location_status' => false,
    ]);

    $rows = collect((new LocationExport)->exportData(array_keys(locationExportColumns())));
    $row = $rows->firstWhere('location_name', 'Export Me');

    expect(array_keys($row))->toBe(array_keys(locationExportColumns()))
        ->and($row)->toMatchArray([
            'location_email' => 'export@example.com',
            'location_address_1' => '1 Export Rd',
            'location_country' => 'GB',
            'location_lat' => 51.5,
            'location_lng' => -0.1,
            'location_status' => 0,
        ])
        ->and((new LocationExport)->exportData([], ['offset' => $rows->count() - 1, 'limit' => 1]))->toHaveCount(1)
        ->and($location->exists)->toBeTrue();
});

it('exports a location csv that imports back without remapping', function(): void {
    Location::factory()->create([
        'location_name' => 'Round Trip Kitchen',
        'location_email' => 'rt@example.com',
        'location_city' => 'York',
        'location_lat' => 53.96,
        'location_lng' => -1.08,
    ]);

    $columns = locationExportColumns();
    $path = tempnam(sys_get_temp_dir(), 'export');
    file_put_contents($path, (new LocationExport)->export($columns, [])->toString());

    Location::query()->where('location_name', 'Round Trip Kitchen')->delete();

    $import = new LocationImport(['update_existing' => true]);
    $import->import(collect($columns)->map(fn(string $label, string $column): array => [$column, $label])->values()->all(), [], $path);

    $imported = Location::query()->firstWhere('location_name', 'Round Trip Kitchen');
    expect($import->getResultStats()['errorCount'])->toBe(0)
        ->and($imported)->not->toBeNull()
        ->and($imported->location_email)->toBe('rt@example.com')
        ->and($imported->location_city)->toBe('York')
        ->and($imported->location_lat)->toBe(53.96);
});
