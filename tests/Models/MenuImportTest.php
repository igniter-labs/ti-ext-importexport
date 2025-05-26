<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Exception;
use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use IgniterLabs\ImportExport\Models\MenuImport;

it('imports data and creates new menu item', function(): void {
    $menuImport = mock(MenuImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $menuImport->shouldReceive('findDuplicateMenuItem')->andReturn(null);
    $menuImport->shouldReceive('getCategoryIdsForMenuItem')->andReturn([1, 2]);
    $menuImport->shouldReceive('logCreated')->once();

    $data = [
        'menu_name' => 'New Menu Item',
        'categories' => 'Category 1|Category 2',
    ];

    $menuImport->importData([$data]);

    $menuItem = Menu::where('menu_name', 'New Menu Item')->first();
    expect($menuItem)->not->toBeNull()
        ->and($menuItem->categories->pluck('category_id')->toArray())->toBe([1, 2]);
});

it('imports data and updates existing menu item', function(): void {
    Menu::factory()->create(['menu_name' => 'Existing Menu Item']);
    $existingMenu = Menu::factory()->create();
    $existingCategory = Category::factory()->create();
    $menuImport = mock(MenuImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $menuImport->shouldReceive('extendableGet')->with('update_existing')->andReturn(true);
    $menuImport->shouldReceive('logUpdated')->times(3);

    $data = [
        [
            'menu_name' => '', // Test skips empty menu name
        ],
        [
            'menu_name' => 'Existing Menu Item',
        ],
        [
            'menu_id' => $existingMenu->getKey(),
            'menu_name' => 'Existing Menu Item 2',
            'categories' => $existingCategory->name.'|'.$existingCategory->name,
        ],
    ];

    $menuImport->importData($data);

    $menuItem = Menu::where('menu_name', 'Existing Menu Item 2')->first();
    expect($menuItem)->not->toBeNull()
        ->and($menuItem->categories->pluck('category_id')->toArray())->toContain($existingCategory->getKey());
});

it('logs error when exception is thrown during import', function(): void {
    $menuImport = mock(MenuImport::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $menuImport->shouldReceive('extendableGet')->with('update_existing')->andReturn(true);
    $menuImport->shouldReceive('findDuplicateMenuItem')->andThrow(new Exception('Test Exception'));
    $menuImport->shouldReceive('logError')->with(0, 'Test Exception')->once();

    $data = [
        'menu_name' => 'Menu Item',
    ];

    $menuImport->importData([0 => $data]);
});
