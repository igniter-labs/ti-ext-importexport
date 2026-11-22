<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\MenuOption;
use Igniter\Local\Models\Location;
use IgniterLabs\ImportExport\Models\PlatformMenuImport;

function platformMenuRow(array $overrides = []): array
{
    return array_merge([
        'menu_name' => 'Falafel Wrap',
        'menu_description' => 'With hummus',
        'menu_price' => 7.0,
        'categories' => ['Wraps', 'Vegan'],
        'menu_status' => true,
        'option_groups' => [
            ['name' => 'Wrap Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Regular', 'price' => 0.0], ['name' => 'Large', 'price' => 2.0]]],
            ['name' => 'Wrap Sauces', 'min' => 0, 'max' => 2, 'values' => [['name' => 'Garlic', 'price' => 0.5], ['name' => 'Chilli', 'price' => 0.5]]],
        ],
    ], $overrides);
}

it('creates menu items with categories, option groups and locations', function(): void {
    $location = Location::factory()->create();

    $import = new PlatformMenuImport(['update_existing' => true, 'import_options' => true, 'assign_locations' => [$location->getKey()]]);
    $import->importData([platformMenuRow()]);

    $menu = Menu::query()->with(['categories', 'menu_options.menu_option_values', 'locations'])->firstWhere('menu_name', 'Falafel Wrap');

    expect($import->getResultStats())->toMatchArray(['created' => 3, 'errorCount' => 0])
        ->and((float)$menu->menu_price)->toBe(7.0)
        ->and($menu->menu_description)->toBe('With hummus')
        ->and($menu->categories->pluck('name')->all())->toEqualCanonicalizing(['Wraps', 'Vegan'])
        ->and($menu->locations->pluck('location_id')->all())->toContain($location->getKey())
        ->and($menu->menu_options)->toHaveCount(2);

    $size = $menu->menu_options->firstWhere('option_name', 'Wrap Size');
    $sauces = $menu->menu_options->firstWhere('option_name', 'Wrap Sauces');
    expect($size->is_required)->toBeTrue()
        ->and($size->display_type)->toBe('radio')
        ->and($size->min_selected)->toBe(1)
        ->and($size->max_selected)->toBe(1)
        ->and($size->menu_option_values)->toHaveCount(2)
        ->and($size->menu_option_values->pluck('override_price')->filter()->all())->toBe([])
        ->and($sauces->is_required)->toBeFalse()
        ->and($sauces->display_type)->toBe('checkbox')
        ->and($sauces->max_selected)->toBe(2);
});

it('reuses matching options, overrides differing prices and creates new options for different values', function(): void {
    $import = new PlatformMenuImport(['update_existing' => true, 'import_options' => true]);
    $import->importData([
        platformMenuRow(),
        platformMenuRow([
            'menu_name' => 'Halloumi Wrap',
            'option_groups' => [
                ['name' => 'Wrap Size', 'min' => 1, 'max' => 1, 'values' => [['name' => 'Large', 'price' => 3.0], ['name' => 'Regular', 'price' => 0.0]]],
                ['name' => 'Wrap Sauces', 'min' => 0, 'max' => 3, 'values' => [['name' => 'Garlic', 'price' => 0.5], ['name' => 'Mint', 'price' => 0.5], ['name' => 'Chilli', 'price' => 0.5]]],
            ],
        ]),
    ]);

    expect(MenuOption::query()->where('option_name', 'Wrap Size')->count())->toBe(1)
        ->and(MenuOption::query()->where('option_name', 'Wrap Sauces')->count())->toBe(2);

    $halloumi = Menu::query()->with('menu_options.menu_option_values.option_value')->firstWhere('menu_name', 'Halloumi Wrap');
    $large = $halloumi->menu_options->firstWhere('option_name', 'Wrap Size')
        ->menu_option_values->first(fn($value): bool => $value->option_value->name === 'Large');

    expect((float)$large->override_price)->toBe(3.0);

    // A second run in a new import reuses the existing option from the database and the original values stay untouched
    $nextImport = new PlatformMenuImport(['update_existing' => true, 'import_options' => true]);
    $nextImport->importData([platformMenuRow(['menu_name' => 'Chicken Wrap'])]);

    $sizeOption = MenuOption::query()->with('option_values')->firstWhere('option_name', 'Wrap Size');
    expect(MenuOption::query()->where('option_name', 'Wrap Size')->count())->toBe(1)
        ->and($sizeOption->option_values->pluck('name')->all())->toEqualCanonicalizing(['Regular', 'Large']);
});

it('updates existing menu item and skips options when disabled', function(): void {
    Menu::factory()->create(['menu_name' => 'Falafel Wrap', 'menu_price' => 1]);

    $import = new PlatformMenuImport(['update_existing' => true, 'import_options' => false]);
    $import->importData([platformMenuRow(['categories' => [], 'menu_status' => false])]);

    $menu = Menu::query()->firstWhere('menu_name', 'Falafel Wrap');
    expect($import->getResultStats())->toMatchArray(['updated' => 1, 'created' => 0])
        ->and((float)$menu->menu_price)->toBe(7.0)
        ->and((bool)$menu->menu_status)->toBeFalse()
        ->and($menu->menu_options()->count())->toBe(0);
});

it('creates duplicates when not updating existing and logs invalid rows', function(): void {
    Menu::factory()->create(['menu_name' => 'Falafel Wrap']);

    $import = new PlatformMenuImport(['update_existing' => false, 'import_options' => true]);
    $import->importData([
        platformMenuRow(['option_groups' => []]),
        ['menu_name' => ''],
        platformMenuRow(['menu_name' => 'Bad Option', 'option_groups' => [['name' => 'Size', 'min' => 0, 'max' => 1, 'values' => []]]]),
    ]);

    $stats = $import->getResultStats();
    expect(Menu::query()->where('menu_name', 'Falafel Wrap')->count())->toBe(2)
        ->and($stats['errorCount'])->toBe(2)
        ->and($stats['errors'])->toHaveKeys([1, 2]);
});

it('skips option values missing from a reused option', function(): void {
    $import = new PlatformMenuImport(['import_options' => true]);
    $menu = Menu::factory()->create();

    $group = ['name' => 'Odd Group', 'min' => 0, 'max' => 1, 'values' => [['name' => 'Kept', 'price' => 1.0]]];
    $method = new \ReflectionMethod($import, 'attachOptionGroup');
    $method->invoke($import, $menu, $group, 0);

    // Remove the value behind the cached option to simulate a value that no longer exists
    MenuOption::query()->firstWhere('option_name', 'Odd Group')->option_values()->delete();
    $method->invoke($import, $menu, $group, 0);

    expect($menu->menu_options()->first()->menu_option_values()->count())->toBe(1);
});
