<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\System\Models\Country;
use Igniter\User\Models\Customer;
use Igniter\User\Models\CustomerGroup;
use IgniterLabs\ImportExport\Models\CustomerImport;
use Illuminate\Support\Facades\Mail;

beforeEach(function(): void {
    Mail::fake();
});

it('creates customers, splits full names and adds addresses without sending emails', function(): void {
    $group = CustomerGroup::factory()->create(['group_name' => 'VIP']);
    $country = Country::query()->where('iso_code_2', 'GB')->first();

    $import = new CustomerImport(['update_existing' => true]);
    $import->importData([
        [
            'email' => 'Jane@Example.com',
            'full_name' => 'Jane van Doe',
            'telephone' => '0123456789',
            'newsletter' => 'yes',
            'customer_group' => 'VIP',
            'address_1' => '1 High Street',
            'city' => 'London',
            'postcode' => 'E1 6AN',
            'country' => 'gb',
        ],
        [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Smith',
            'status' => 'inactive',
            'customer_group' => 'Missing group',
        ],
    ]);

    $jane = Customer::query()->firstWhere('email', 'jane@example.com');
    $john = Customer::query()->firstWhere('email', 'john@example.com');

    expect($import->getResultStats())->toMatchArray(['created' => 2, 'updated' => 0, 'errorCount' => 0])
        ->and($jane->first_name)->toBe('Jane')
        ->and($jane->last_name)->toBe('van Doe')
        ->and($jane->newsletter)->toBeTrue()
        ->and($jane->status)->toBeTrue()
        ->and($jane->password)->toBeNull()
        ->and($jane->customer_group_id)->toBe($group->getKey())
        ->and($jane->addresses)->toHaveCount(1)
        ->and($jane->addresses->first()->country_id)->toBe($country?->getKey())
        ->and($john->status)->toBeFalse()
        ->and($john->customer_group_id)->toBe(CustomerGroup::getDefaultKey())
        ->and($john->addresses)->toHaveCount(0);

    Mail::assertNothingSent();
    Mail::assertNothingQueued();
});

it('updates existing customers without duplicating addresses', function(): void {
    $customer = Customer::factory()->create(['email' => 'jane@example.com', 'first_name' => 'Old', 'last_name' => 'Name']);
    $customer->addresses()->create(['address_1' => '1 High Street', 'postcode' => 'E1 6AN', 'country_id' => 1]);

    $import = new CustomerImport(['update_existing' => true]);
    $import->importData([[
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'addresses' => [
            ['address_1' => '1 High Street', 'postcode' => 'E1 6AN'],
            ['address_1' => '2 Low Road', 'postcode' => null, 'country' => 'Nowhere'],
            ['address_1' => ''],
        ],
    ]]);

    $customer->refresh();
    expect($import->getResultStats())->toMatchArray(['created' => 0, 'updated' => 1])
        ->and($customer->first_name)->toBe('Jane')
        ->and($customer->last_name)->toBe('Name')
        ->and($customer->addresses)->toHaveCount(2)
        ->and($customer->addresses->firstWhere('address_1', '2 Low Road')->country_id)->toBe(Country::getDefaultKey());
});

it('logs errors for invalid rows and existing customers when not updating', function(): void {
    Customer::factory()->create(['email' => 'jane@example.com']);

    $import = new CustomerImport(['update_existing' => false]);
    $import->importData([
        ['email' => 'jane@example.com'],
        ['email' => '', 'full_name' => 'No Email'],
        ['email' => 'not-an-email'],
    ]);

    $stats = $import->getResultStats();
    expect($stats['errorCount'])->toBe(3)
        ->and($stats['errors'][0])->toBe(sprintf(lang('igniterlabs.importexport::default.error_customer_exists'), 'jane@example.com'))
        ->and($stats['errors'])->toHaveKeys([1, 2]);
});
