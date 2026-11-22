<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Classes\Sources;

use Igniter\Flame\Exception\FlashException;
use IgniterLabs\ImportExport\Classes\Sources\FlipdishSource;
use IgniterLabs\ImportExport\Classes\Sources\GloriaFoodSource;
use IgniterLabs\ImportExport\Classes\Sources\ImportSource;
use IgniterLabs\ImportExport\Classes\Sources\JustEatSource;
use IgniterLabs\ImportExport\Classes\Sources\UberEatsSource;
use IgniterLabs\ImportExport\Classes\Sources\WooCommerceSource;

function platformFixture(string $name): string
{
    return __DIR__.'/../../_fixtures/platforms/'.$name;
}

function writeTempFixture(string $contents): string
{
    $path = tempnam(sys_get_temp_dir(), 'importexport');
    file_put_contents($path, $contents);

    return $path;
}

it('parses GloriaFood menu, clients and restaurant details', function(): void {
    $source = new GloriaFoodSource;

    $menu = $source->parse('parseMenu', platformFixture('gloriafood_menu.json'));
    expect($source->label())->toBe('GloriaFood')
        ->and($menu)->toHaveCount(3)
        ->and($menu[0])->toMatchArray([
            'menu_name' => 'Margherita',
            'menu_description' => 'Tomato & mozzarella',
            'menu_price' => 8.5,
            'categories' => ['Pizzas'],
            'menu_status' => true,
        ])
        ->and($menu[0]['option_groups'])->toBe([
            ['name' => 'Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Small', 'price' => 0.0], ['name' => 'Large', 'price' => 2.5]]],
            ['name' => 'Extra Toppings', 'min' => 0, 'max' => 2, 'values' => [['name' => 'Mushrooms', 'price' => 0.8], ['name' => 'Olives', 'price' => 0.9]]],
            ['name' => 'Crust', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Thin', 'price' => 0.0], ['name' => 'Stuffed', 'price' => 1.5]]],
        ])
        ->and($menu[1]['menu_status'])->toBeFalse()
        ->and($menu[2])->toMatchArray(['menu_name' => 'Cola', 'menu_price' => 1.5, 'option_groups' => []]);

    $customers = $source->parse('parseCustomers', platformFixture('gloriafood_clients.csv'));
    expect($customers)->toHaveCount(2)
        ->and($customers[0])->toMatchArray([
            'email' => 'amara@example.com',
            'first_name' => 'Amara',
            'last_name' => 'Okafor',
            'telephone' => '07700900001',
            'addresses' => [['address_1' => '12 Market Road', 'city' => 'Leeds', 'postcode' => 'LS1 4AB', 'country' => 'GB']],
        ])
        ->and($customers[1]['addresses'])->toBe([]);

    $locations = $source->parse('parseLocations', platformFixture('gloriafood_orders.json'));
    expect($locations)->toHaveCount(1)
        ->and($locations[0])->toMatchArray([
            'location_name' => 'Gloria Leeds',
            'location_postcode' => 'LS1 6HD',
            'location_country' => 'GB',
            'location_lat' => '53.7965',
        ]);

    expect($source->parse('parseLocations', writeTempFixture('{"restaurant_name": "Single", "restaurant_id": 1}')))->toHaveCount(1)
        ->and($source->parse('parseMenu', writeTempFixture('{"menu": {"categories": [{"items": [{"name": "Plain"}]}]}}'))[0])
        ->toMatchArray(['menu_name' => 'Plain', 'categories' => [], 'menu_price' => 0.0]);
});

it('parses WooCommerce products with variations and customers', function(): void {
    $source = new WooCommerceSource;

    $menu = $source->parse('parseMenu', platformFixture('woocommerce_products.csv'));
    expect($source->label())->toBe('WooCommerce')
        ->and(array_column($menu, 'menu_name'))->toBe(['Garlic Bread', 'Pepperoni Pizza', 'Old Lemonade', 'Water'])
        ->and($menu[0])->toMatchArray([
            'menu_description' => 'Crispy garlic bread',
            'menu_price' => 3.95,
            'categories' => ['Starters', 'Sides'],
            'menu_status' => true,
        ])
        ->and($menu[1]['menu_price'])->toBe(9.0)
        ->and($menu[1]['menu_description'])->toBe('Spicy pepperoni')
        ->and($menu[1]['option_groups'])->toBe([
            ['name' => 'Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Small', 'price' => 0.0], ['name' => 'Large', 'price' => 3.5]]],
            ['name' => 'Crust', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Thin', 'price' => 0.0], ['name' => 'Deep', 'price' => 0.0]]],
        ])
        ->and($menu[2]['menu_status'])->toBeFalse()
        ->and($menu[3])->toMatchArray(['menu_price' => 1.0, 'categories' => ['Drinks, Soft'], 'menu_status' => false]);

    $customers = $source->parse('parseCustomers', platformFixture('woocommerce_customers.csv'));
    expect($customers[0])->toMatchArray(['email' => 'chloe@example.com', 'full_name' => 'Chloe Martin', 'addresses' => []]);

    // A variable product without variation rows, and variations linked by parent SKU
    $products = writeTempFixture(implode("\n", [
        'ID,Type,SKU,Name,Published,Regular price,Parent,Attribute 1 name,Attribute 1 value(s)',
        '1,variable,TEA,Tea,1,2.00,,Size,"Cup, Pot"',
        '2,variation,,Tea - Cup,1,2.00,TEA,Size,Cup',
        '3,variation,,Tea - Pot,1,3.50,TEA,Size,',
        '4,variable,COF,Coffee,1,2.50,,,',
        '',
    ]));
    $menu = $source->parse('parseMenu', $products);
    expect($menu[0]['option_groups'])->toBe([['name' => 'Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Cup', 'price' => 0.0]]]])
        ->and($menu[1])->toMatchArray(['menu_price' => 2.5, 'option_groups' => []]);
});

it('parses Uber Eats menu and stores', function(): void {
    $source = new UberEatsSource;

    $menu = $source->parse('parseMenu', platformFixture('ubereats_menu.json'));
    expect($source->label())->toBe('Uber Eats')
        ->and($menu)->toHaveCount(3)
        ->and($menu[0])->toMatchArray([
            'menu_name' => 'Cheeseburger',
            'menu_description' => 'Beef, cheddar',
            'menu_price' => 10.99,
            'categories' => ['Burgers'],
            'menu_status' => true,
            'option_groups' => [['name' => 'Add-ons', 'min' => 0, 'max' => 2, 'values' => [['name' => 'Bacon', 'price' => 1.5], ['name' => 'Fried Egg', 'price' => 1.0]]]],
        ])
        ->and($menu[1])->toMatchArray(['menu_name' => 'Veggie Burger', 'menu_status' => false])
        ->and($menu[2])->toMatchArray(['menu_name' => 'Limonade', 'categories' => ['Boissons'], 'menu_price' => 3.0]);

    $locations = $source->parse('parseLocations', platformFixture('ubereats_stores.json'));
    expect($locations)->toHaveCount(2)
        ->and($locations[0])->toMatchArray([
            'location_name' => 'Uber Camden',
            'location_email' => 'camden@example.com',
            'location_telephone' => '+442000000001',
            'location_address_2' => 'Unit 2',
            'location_lat' => 51.539,
            'location_status' => true,
        ])
        ->and($locations[1]['location_status'])->toBeFalse();

    expect($source->parse('parseLocations', writeTempFixture('{"name": "Solo Store"}')))->toHaveCount(1)
        ->and($source->parse('parseMenu', writeTempFixture('{"categories": [{"title": "Plain", "entities": [{"id": "a"}]}], "items": [{"id": "a", "title": "Fries"}]}'))[0])
        ->toMatchArray(['menu_name' => 'Fries', 'categories' => ['Plain']])
        ->and($source->parse('parseMenu', writeTempFixture('{"categories": [{"entities": [{"id": "a"}]}], "items": [{"id": "a", "title": {"translations": {"de": ""}}}]}'))[0]['menu_name'])
        ->toBe('');
});

it('parses Just Eat menu and restaurants', function(): void {
    $source = new JustEatSource;

    $menu = $source->parse('parseMenu', platformFixture('justeat_menu.json'));
    expect($source->label())->toBe('Just Eat')
        ->and($menu)->toHaveCount(3)
        ->and($menu[0])->toMatchArray(['menu_name' => 'Chicken Tikka Masala', 'menu_price' => 9.95, 'categories' => ['Curries']])
        ->and(array_column($menu[0]['option_groups'], 'name'))->toBe(['Size', 'Spice Level', 'Extras'])
        ->and($menu[0]['option_groups'][2]['values'])->toBe([['name' => 'Poppadom', 'price' => 0.75], ['name' => 'Raita', 'price' => 1.2]])
        ->and($menu[1])->toMatchArray(['menu_name' => 'Lamb Bhuna', 'menu_price' => 10.5, 'menu_status' => false, 'option_groups' => []])
        ->and($menu[2])->toMatchArray(['menu_name' => 'Pilau Rice', 'menu_price' => 3.25, 'categories' => ['Sides']]);

    $locations = $source->parse('parseLocations', platformFixture('justeat_restaurants.json'));
    expect($locations)->toHaveCount(2)
        ->and($locations[0])->toMatchArray([
            'location_name' => 'Spice House',
            'description' => 'Award winning',
            'location_address_1' => '5 Curry Mile',
            'location_lat' => 53.452,
            'location_lng' => -2.2236,
        ])
        ->and($locations[1])->toMatchArray(['location_address_2' => 'Floor 1', 'location_lat' => 53.47, 'location_lng' => -2.24]);

    expect($source->parse('parseLocations', writeTempFixture('{"name": "Solo"}')))->toHaveCount(1)
        ->and($source->parse('parseMenu', writeTempFixture('{"categories": [{"name": "Top", "itemIds": ["x"]}], "items": [{"id": "x", "name": "Naan", "price": 2}]}'))[0])
        ->toMatchArray(['menu_name' => 'Naan', 'menu_price' => 2.0, 'categories' => ['Top']]);
});

it('parses Flipdish menu, stores and customers', function(): void {
    $source = new FlipdishSource;

    $menu = $source->parse('parseMenu', platformFixture('flipdish_menu.json'));
    expect($source->label())->toBe('Flipdish')
        ->and($menu)->toHaveCount(3)
        ->and($menu[0])->toMatchArray(['menu_name' => 'Falafel Wrap', 'menu_price' => 7.0, 'categories' => ['Wraps'], 'menu_status' => true])
        ->and($menu[0]['option_groups'])->toBe([
            ['name' => 'Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Regular', 'price' => 0.0], ['name' => 'Large', 'price' => 2.0]]],
            ['name' => 'Sauces', 'min' => 0, 'max' => 2, 'values' => [['name' => 'Garlic', 'price' => 0.5], ['name' => 'Chilli', 'price' => 0.5]]],
        ])
        ->and($menu[1]['menu_status'])->toBeFalse()
        ->and($menu[2]['menu_status'])->toBeFalse();

    $locations = $source->parse('parseLocations', platformFixture('flipdish_stores.json'));
    expect($locations[0])->toMatchArray([
        'location_name' => 'Flip Dublin',
        'location_country' => 'IE',
        'location_lat' => 53.3419,
        'location_status' => true,
    ])
        ->and($locations[1])->toMatchArray(['location_address_1' => 'Patrick St', 'location_country' => 'Ireland', 'location_lng' => -8.4756, 'location_status' => false]);

    expect($source->parse('parseLocations', writeTempFixture('{"Name": "Solo"}')))->toHaveCount(1);

    $customers = $source->parse('parseCustomers', platformFixture('flipdish_customers.csv'));
    expect($customers[0])->toMatchArray([
        'email' => 'ciara@example.com',
        'full_name' => 'Ciara Byrne',
        'telephone' => '+353870000001',
        'addresses' => [['address_1' => '4 Main Street', 'postcode' => 'D01 F5P2']],
    ]);
});

it('throws a friendly error for unreadable or unsupported files', function(): void {
    expect(fn(): array => (new UberEatsSource)->parse('parseMenu', writeTempFixture('{not json')))
        ->toThrow(FlashException::class, 'The Uber Eats export file could not be read')
        ->and(fn(): array => (new UberEatsSource)->parse('parseMenu', writeTempFixture('"just a string"')))
        ->toThrow(FlashException::class, 'does not contain a JSON object')
        ->and(fn(): array => (new FlipdishSource)->parse('parseCustomers', writeTempFixture('')))
        ->toThrow(FlashException::class, 'The CSV file is empty.')
        ->and(fn(): array => (new UberEatsSource)->parse('parseCustomers', platformFixture('flipdish_customers.csv')))
        ->toThrow(FlashException::class, 'Uber Eats does not support this import type.')
        ->and(fn(): array => (new UberEatsSource)->parseCustomers('file.csv'))
        ->toThrow(FlashException::class, 'Uber Eats does not support this import type.')
        ->and(fn(): array => (new WooCommerceSource)->parseLocations('file.json'))
        ->toThrow(FlashException::class)
        ->and(fn(): array => (new JustEatSource)->parseCustomers('file.csv'))
        ->toThrow(FlashException::class)
        ->and(fn(): array => (new class extends ImportSource
        {
            public function label(): string
            {
                return 'Test';
            }
        })->parseMenu('file.json'))->toThrow(FlashException::class);
});

it('reports accepted extensions and formats per parse method', function(): void {
    $source = new GloriaFoodSource;

    expect($source->acceptedExtensions('parseMenu'))->toBe(['json', 'txt'])
        ->and($source->acceptedExtensions('parseCustomers'))->toBe(['csv', 'txt'])
        ->and($source->acceptedExtensions('parseOrders'))->toBe([])
        ->and($source->format('parseMenu'))->toBe('json')
        ->and($source->format('parseOrders'))->toBeNull()
        ->and($source->supports('parseLocations'))->toBeTrue()
        ->and((new WooCommerceSource)->supports('parseLocations'))->toBeFalse();
});

it('skips blank csv rows, empty option groups and prefixes short option value names', function(): void {
    $customers = (new FlipdishSource)->parse('parseCustomers', writeTempFixture("Email,Name\na@example.com,Ann\n,\nb@example.com,Bob\n"));

    $menu = (new FlipdishSource)->parse('parseMenu', writeTempFixture(json_encode(['MenuSections' => [['Name' => 'Pizzas', 'MenuItems' => [[
        'Name' => 'Pizza',
        'Price' => 9,
        'MenuItemOptionSets' => [
            ['Name' => 'Size', 'MinSelectCount' => 1, 'MaxSelectCount' => 1, 'MenuItemOptionSetItems' => [['Name' => 'S', 'Price' => 0], ['Name' => 'L', 'Price' => 2]]],
            ['Name' => 'Empty', 'MenuItemOptionSetItems' => [['Name' => ' ', 'Price' => 1]]],
        ],
    ]]]]])));

    expect(array_column($customers, 'email'))->toBe(['a@example.com', 'b@example.com'])
        ->and($menu[0]['option_groups'])->toBe([
            ['name' => 'Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Size S', 'price' => 0.0], ['name' => 'Size L', 'price' => 2.0]]],
        ]);
});
