<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Http\Controllers;

use Igniter\Cart\Models\Menu;
use Igniter\Flame\Support\Facades\File;
use Igniter\User\Models\User;
use Igniter\User\Models\UserRole;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Models\MenuImport;
use Illuminate\Http\UploadedFile;

it('loads import records form page', function(): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);

    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]))
        ->assertOk();

    File::delete($history->getCsvPath());
});

it('throws exception when registered import type columns is missing', function(): void {
    app()->instance(ImportExportManager::class, $importExportManagerMock = mock(ImportExportManager::class));
    $importExportManagerMock->shouldReceive('getRecordConfig')
        ->once()
        ->with('import', 'type-with-missing-columns')
        ->andReturn([
            'label' => 'Custom Import',
            'description' => 'Custom import description',
            'model' => MenuImport::class,
            'configFile' => [],
        ]);

    $history = History::factory()->create([
        'code' => 'type-with-missing-columns',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/type-with-missing-columns/'.$history->uuid]))
        ->assertRedirectContains('igniterlabs/importexport/import_export');

    expect(flash()->messages()->first())->message->toBe(lang('igniterlabs.importexport::default.error_empty_import_columns'))
        ->and(History::query()->find($history->getKey()))->toBeNull()
        ->and($history->csvExists())->toBeFalse();
});

it('loads import page for csv file with headers that do not match column labels', function(): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/custom_headers_import_file.csv'));

    $response = actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]))
        ->assertOk()
        ->assertSee('Item Title')
        ->assertSee('Ignore Me')
        ->assertSee(lang('igniterlabs.importexport::default.text_do_not_import'));

    // Only the "menu_id" header matches a column, so it is the only pre-selected option
    expect(preg_match_all('/<option\s+value="(\w+)"\s+selected/', (string)$response->getContent(), $matches))->toBe(1)
        ->and($matches[1])->toBe(['menu_id']);

    File::delete($history->getCsvPath());
});

it('pre-selects database columns matching file headers by name or label', function(): void {
    app()->instance(ImportExportManager::class, $importExportManagerMock = mock(ImportExportManager::class));
    $importExportManagerMock->shouldReceive('getRecordConfig')
        ->with('import', 'type-with-custom-columns')
        ->andReturn([
            'label' => 'Custom Import',
            'model' => MenuImport::class,
            'configFile' => [
                'columns' => [
                    'menu_name' => 'Item Name',
                    'menu_price' => 'Price',
                    'menu_description' => 'Description',
                    'location_postcode' => 'Postcode',
                ],
            ],
        ]);

    $history = History::factory()->create(['code' => 'type-with-custom-columns']);
    File::put($history->getCsvPath(), " item name ,PRICE,menu-description,Description,Post Code\nPizza,10,Cheesy,Dup,x\n");

    $response = actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/type-with-custom-columns/'.$history->uuid]))
        ->assertOk();

    preg_match_all('/<option\s+value="(\w+)"\s+selected/', (string)$response->getContent(), $matches);
    expect($matches[1])->toBe(['menu_name', 'menu_price', 'menu_description', 'location_postcode']);

    File::delete($history->getCsvPath());
});

it('throws exception when import file has blank or duplicate headers', function(string $contents): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), $contents);

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]))
        ->assertRedirectContains('igniterlabs/importexport/import_export');

    expect(flash()->messages()->first())->message->toBe(lang('igniterlabs.importexport::default.error_missing_csv_headers'));
})->with([
    'blank header' => ["Name,,Price\nPizza,x,10\n"],
    'duplicate header' => ["Name,name ,Price\nPizza,Pie,10\n"],
    'empty file' => [''],
]);

it('redirects to import page after uploading valid import file', function(): void {
    History::query()->truncate();
    File::deleteDirectory(dirname((new History)->getCsvPath()));

    $file = UploadedFile::fake()->image('import_file.csv');
    $response = actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-menus',
            'import_file' => $file,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ]);

    $history = History::query()->where([
        ['type', 'import'],
        ['code', 'igniterlabs-importexport-menus'],
        ['status', 'pending'],
    ])->first();

    $response->assertJsonFragment([
        'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export/import/igniterlabs-importexport-menus/'.$history->uuid),
    ]);
});

it('flashes validation error when loading import form with incorrect code or invalid file', function(): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-menus',
            'import_file' => UploadedFile::fake()->image('import_file.jpg'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    expect(flash()->messages()->first())->message->toBe('You must upload a valid csv file');
    flash()->clear();

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'invalid-code',
            'import_file' => UploadedFile::fake()->image('import_file.csv'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    expect(flash()->messages()->first())->message->toBe('invalid-code is not a registered import template');
});

it('flashes validation error when loading import form with restricted access', function(): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1],
    ]);
    $user = User::factory()->for($userRole, 'role')->create();

    $this
        ->actingAs($user, 'igniter-admin')
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-menus',
            'import_file' => UploadedFile::fake()->image('import_file.csv'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
});

it('does not load import page when user has restricted access', function(): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1],
    ]);
    $user = User::factory()->for($userRole, 'role')->create();

    $this
        ->actingAs($user, 'igniter-admin')
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/valid-uuid']))
        ->assertRedirectContains('igniterlabs/importexport/import_export');

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
});

it('does not load import form when import history does not exists', function(): void {
    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/invalid-uuid']))
        ->assertRedirectContains('igniterlabs/importexport/import_export');
});

it('deletes imported file', function(): void {
    History::flushEventListeners();
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onDeleteImportFile',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    expect(History::query()->find($history->getKey()))->toBeNull();
});

it('processes import from uploaded import file', function(): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);

    $columns = [
        'menu_id' => 'ID',
        'menu_name' => 'Name',
        'menu_price' => 'Price',
        'menu_description' => 'Description',
        'minimum_qty' => 'Minimum Qty',
        'categories' => 'Category',
        'menu_status' => 'Status',
    ];

    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]), [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'ImportSecondary' => [
                'update_existing' => true,
            ],
            'import_columns' => array_keys($columns),
            'match_columns' => array_values($columns),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    $history->refresh();
    expect($history->error_message)->toContain('Created (1)', 'Updated (16)', 'Errors (1)')
        ->and($history->status)->toBe('completed');
});

it('imports csv file with custom headers mapped to database columns', function(): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/custom_headers_import_file.csv'));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]), [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'ImportSecondary' => ['update_existing' => true],
            'match_columns' => [0 => 'Item Title', 1 => 'Cost', 2 => 'Blurb', 3 => 'Section', 4 => 'Active', 5 => 'Ignore Me'],
            'import_columns' => [0 => 'menu_name', 1 => 'menu_price', 2 => 'menu_description', 3 => 'categories', 4 => 'menu_status', 5 => null],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    $menu = Menu::query()->firstWhere('menu_name', 'Garlic Bread');
    expect($history->refresh()->status)->toBe('completed')
        ->and($menu)->not->toBeNull()
        ->and((float)$menu->menu_price)->toBe(3.5)
        ->and($menu->menu_description)->toBe('Toasted with garlic butter')
        ->and($menu->categories->pluck('name')->all())->toEqualCanonicalizing(['Starters', 'Sides'])
        ->and(Menu::query()->where('menu_name', 'Chips')->exists())->toBeTrue();
});

it('rejects import when a database column is mapped more than once', function(): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/custom_headers_import_file.csv'));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]), [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'match_columns' => [0 => 'Item Title', 2 => 'Blurb'],
            'import_columns' => [0 => 'menu_name', 2 => 'menu_name'],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertSee(sprintf(lang('igniterlabs.importexport::default.error_duplicate_import_columns'), 'Name'));

    expect(Menu::query()->where('menu_name', 'Garlic Bread')->exists())->toBeFalse();
});

it('rejects import when no columns are mapped or an unknown column is mapped', function(array $columns, string $message): void {
    $history = History::factory()->create([
        'code' => 'igniterlabs-importexport-menus',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/custom_headers_import_file.csv'));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-menus/'.$history->uuid]), [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'match_columns' => [0 => 'Item Title'],
            'import_columns' => $columns,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertSee($message);
})->with([
    'nothing mapped' => [[0 => null], 'Please specify some columns to import.'],
    'unknown column' => [[0 => 'not_a_column'], 'The selected import_columns.0 is invalid.'],
]);

it('imports customers and locations from csv files with custom headers', function(string $code, string $fixture, array $mapping, string $expected): void {
    $history = History::factory()->create(['code' => $code]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/'.$fixture));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/'.$code.'/'.$history->uuid]), [
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'ImportSecondary' => ['update_existing' => true],
            'match_columns' => array_keys($mapping),
            'import_columns' => array_values($mapping),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ]);

    expect($history->refresh()->error_message)->toContain($expected);
})->with([
    'customers' => [
        'igniterlabs-importexport-customers',
        'custom_customers.csv',
        ['E-mail Address' => 'email', 'Name' => 'full_name', 'Phone' => 'telephone', 'Group' => 'customer_group', 'Street' => 'address_1', 'Town' => 'city', 'Zip' => 'postcode', 'Country' => 'country', 'Subscribed' => 'newsletter'],
        'Created (2)',
    ],
    'locations' => [
        'igniterlabs-importexport-locations',
        'custom_locations.csv',
        ['Store' => 'location_name', 'Email' => 'location_email', 'Phone' => 'location_telephone', 'Street' => 'location_address_1', 'City' => 'location_city', 'Post Code' => 'location_postcode', 'Country' => 'location_country', 'Lat' => 'location_lat', 'Lng' => 'location_lng', 'Open' => 'location_status'],
        'Created (2)',
    ],
]);

it('uploads a platform export file', function(): void {
    History::query()->truncate();

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-ubereats_menus',
            'import_file' => UploadedFile::fake()->createWithContent('menu.json', File::get(__DIR__.'/../../_fixtures/platforms/ubereats_menu.json')),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ])
        ->assertJsonFragment(['X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export/import/igniterlabs-importexport-ubereats_menus/'.History::query()->value('uuid'))]);

    $history = History::query()->firstWhere('code', 'igniterlabs-importexport-ubereats_menus');
    expect($history)->not->toBeNull()
        ->and($history->csvExists())->toBeTrue();

    $history->delete();
});

it('shows a preview of a platform export file', function(): void {
    $history = History::factory()->create(['code' => 'igniterlabs-importexport-ubereats_menus']);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/platforms/ubereats_menu.json'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-ubereats_menus/'.$history->uuid]))
        ->assertOk()
        ->assertSee(sprintf(lang('igniterlabs.importexport::default.text_import_preview'), 'Uber Eats'))
        ->assertSee('Cheeseburger, Veggie Burger, Limonade')
        ->assertSee(lang('igniterlabs.importexport::default.label_import_options'))
        ->assertDontSee(lang('igniterlabs.importexport::default.label_delimiter'));

    File::delete($history->getCsvPath());
});

it('rejects a platform export file with the wrong extension', function(string $code, string $filename, string $message): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => $code,
            'import_file' => UploadedFile::fake()->createWithContent($filename, 'content'),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ]);

    expect(flash()->messages()->first())->message->toBe($message);
})->with([
    'csv for json import' => ['igniterlabs-importexport-flipdish_menus', 'menu.csv', 'You must upload a Flipdish export file with one of these extensions: json, txt'],
    'json for csv import' => ['igniterlabs-importexport-woocommerce_menus', 'products.json', 'You must upload a WooCommerce export file with one of these extensions: csv, txt'],
    'json for regular import' => ['igniterlabs-importexport-menus', 'menu.json', 'You must upload a valid csv file'],
]);

it('shows an error when the platform export file cannot be read', function(): void {
    $history = History::factory()->create(['code' => 'igniterlabs-importexport-justeat_menus']);
    File::put($history->getCsvPath(), '{broken');

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-justeat_menus/'.$history->uuid]))
        ->assertRedirectContains('igniterlabs/importexport/import_export');

    expect(flash()->messages()->first()->message)->toStartWith('The Just Eat export file could not be read')
        ->and(History::query()->find($history->getKey()))->toBeNull();
});

it('imports records from platform export files', function(string $code, string $fixture, array $options, string $expected): void {
    $history = History::factory()->create(['code' => $code]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/platforms/'.$fixture));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/'.$code.'/'.$history->uuid]), [
            'ImportSecondary' => $options,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    $history->refresh();
    expect($history->status)->toBe('completed')
        ->and($history->error_message)->toContain($expected)
        ->and($history->csvExists())->toBeFalse();
})->with([
    'gloriafood menu' => ['igniterlabs-importexport-gloriafood_menus', 'gloriafood_menu.json', ['update_existing' => true, 'import_options' => true], 'Errors (0)'],
    'flipdish customers' => ['igniterlabs-importexport-flipdish_customers', 'flipdish_customers.csv', ['update_existing' => true], 'Created (1)'],
    'uber eats locations' => ['igniterlabs-importexport-ubereats_locations', 'ubereats_stores.json', [], 'Created (2)'],
]);

it('does not import platform export when history is missing', function(): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-ubereats_menus/missing-uuid']), [], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onImport',
        ])
        ->assertSee(lang('igniterlabs.importexport::default.error_invalid_import_file'));
});

it('shows only relevant counts in the preview of a customer export file', function(): void {
    $history = History::factory()->create(['code' => 'igniterlabs-importexport-gloriafood_customers']);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/platforms/gloriafood_clients.csv'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/igniterlabs-importexport-gloriafood_customers/'.$history->uuid]))
        ->assertOk()
        ->assertSee(lang('igniterlabs.importexport::default.preview.addresses'))
        ->assertDontSee(lang('igniterlabs.importexport::default.preview.categories'));

    File::delete($history->getCsvPath());
});

it('restricts customer and location imports to users with the matching permission', function(string $code): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1, 'Admin.Menus' => 1],
    ]);
    $user = User::factory()->for($userRole, 'role')->create();

    $this
        ->actingAs($user, 'igniter-admin')
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => $code,
            'import_file' => UploadedFile::fake()->createWithContent('file.csv', "Email\na@example.com\n"),
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadImportForm',
        ]);

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
})->with([
    'customers' => ['igniterlabs-importexport-customers'],
    'locations' => ['igniterlabs-importexport-locations'],
    'platform locations' => ['igniterlabs-importexport-flipdish_locations'],
]);
