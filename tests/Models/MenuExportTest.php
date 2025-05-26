<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use Igniter\Cart\Models\Category;
use IgniterLabs\ImportExport\Models\MenuExport;

it('returns empty array when no menu categories are defined', function(): void {
    $menuExport = new MenuExport;
    $result = $menuExport->exportData([]);

    expect($result)->toBeArray();
});

it('returns array with menu categories when defined', function(): void {
    $menuExport = mock(MenuExport::class)->makePartial();
    $menuExport->shouldReceive('extendableGet')->with('menu_categories')->andReturn(collect([['name' => 'Category 1']]));

    $result = $menuExport->getCategoriesAttribute();

    expect($result)->toBeString()
        ->and($result)->toBe('Category 1');
});

it('returns encoded array of menu categories', function(): void {
    $menuExport = mock(MenuExport::class)->makePartial();
    $menuExport->shouldReceive('extendableGet')->with('menu_categories')->andReturn(collect([
        ['name' => 'Category 1'],
        ['name' => 'Category 2'],
    ]));

    $result = $menuExport->getCategoriesAttribute();

    expect($result)->toBeString()
        ->and($result)->toBe('Category 1|Category 2');
});

it('configure menu export model correctly', function(): void {
    $menuExport = new MenuExport;

    expect($menuExport->getTable())->toBe('menus')
        ->and($menuExport->getKeyName())->toBe('menu_id')
        ->and($menuExport->relation)->toBe([
            'belongsToMany' => [
                'menu_categories' => [Category::class, 'table' => 'menu_categories', 'foreignKey' => 'menu_id'],
            ],
        ])
        ->and($menuExport->getAppends())->toBe([
            'categories',
        ]);
});
