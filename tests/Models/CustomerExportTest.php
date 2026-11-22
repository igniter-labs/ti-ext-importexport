<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\System\Models\Country;
use Igniter\User\Models\Customer;
use Igniter\User\Models\CustomerGroup;
use IgniterLabs\ImportExport\Models\CustomerExport;
use IgniterLabs\ImportExport\Models\CustomerImport;

function customerExportColumns(): array
{
    return collect((require __DIR__.'/../../resources/models/customerexport.php')['columns'])
        ->map(fn(string $label): string => lang($label))
        ->all();
}

it('exports customers with their default or first address', function(): void {
    Customer::query()->delete();
    $group = CustomerGroup::factory()->create(['group_name' => 'Regulars']);
    $country = Country::query()->where('iso_code_2', 'GB')->first();

    $withDefault = Customer::factory()->create([
        'email' => 'default@example.com', 'first_name' => 'Dee', 'last_name' => 'Fault',
        'telephone' => '0101', 'newsletter' => true, 'status' => true, 'customer_group_id' => $group->getKey(),
    ]);
    $withDefault->addresses()->create(['address_1' => 'Other St', 'country_id' => $country?->getKey()]);
    $default = $withDefault->addresses()->create(['address_1' => '1 Main St', 'city' => 'Leeds', 'postcode' => 'LS1', 'country_id' => $country?->getKey()]);
    $withDefault->updateQuietly(['address_id' => $default->getKey()]);

    $withAddress = Customer::factory()->create(['email' => 'first@example.com', 'status' => false, 'newsletter' => false]);
    $withAddress->updateQuietly(['address_id' => null]);
    $withAddress->addresses()->create(['address_1' => '9 First Rd', 'country_id' => $country?->getKey()]);

    $noAddress = Customer::factory()->create(['email' => 'none@example.com']);
    $noAddress->updateQuietly(['address_id' => null]);

    $rows = (new CustomerExport)->exportData(array_keys(customerExportColumns()));

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBe([
            'email' => 'default@example.com',
            'first_name' => 'Dee',
            'last_name' => 'Fault',
            'full_name' => 'Dee Fault',
            'telephone' => '0101',
            'newsletter' => 1,
            'status' => 1,
            'customer_group' => 'Regulars',
            'address_1' => '1 Main St',
            'address_2' => null,
            'city' => 'Leeds',
            'state' => null,
            'postcode' => 'LS1',
            'country' => 'GB',
        ])
        ->and($rows[1])->toMatchArray(['email' => 'first@example.com', 'status' => 0, 'newsletter' => 0, 'address_1' => '9 First Rd'])
        ->and($rows[2])->toMatchArray(['email' => 'none@example.com', 'address_1' => null, 'country' => null])
        ->and((new CustomerExport)->exportData([], ['offset' => 1, 'limit' => 1]))->toHaveCount(1)
        ->and((new CustomerExport)->exportData([], ['offset' => 1, 'limit' => 1])[0]['email'])->toBe('first@example.com');
});

it('exports a customer csv that imports back without remapping', function(): void {
    Customer::query()->delete();
    $customer = Customer::factory()->create(['email' => 'round@example.com', 'first_name' => 'Round', 'last_name' => 'Trip', 'telephone' => '0202']);
    $customer->addresses()->create(['address_1' => '5 Loop Lane', 'postcode' => 'RT1', 'country_id' => Country::getDefaultKey()]);

    $columns = customerExportColumns();
    $path = tempnam(sys_get_temp_dir(), 'export');
    file_put_contents($path, (new CustomerExport)->export($columns, [])->toString());

    $customer->addresses()->delete();
    $customer->delete();

    $import = new CustomerImport(['update_existing' => true]);
    $import->import(collect($columns)->map(fn(string $label, string $column): array => [$column, $label])->values()->all(), [], $path);

    $imported = Customer::query()->with('addresses')->firstWhere('email', 'round@example.com');
    expect($import->getResultStats())->toMatchArray(['created' => 1, 'errorCount' => 0])
        ->and($imported->first_name)->toBe('Round')
        ->and($imported->last_name)->toBe('Trip')
        ->and($imported->telephone)->toBe('0202')
        ->and($imported->addresses->pluck('address_1')->all())->toBe(['5 Loop Lane']);
});
