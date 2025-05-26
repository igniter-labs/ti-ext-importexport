<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Http\Controllers;

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
        ->assertSee(lang('igniterlabs.importexport::default.error_empty_import_columns'));
});

it('throws exception when import file has missing headers', function(): void {
    app()->instance(ImportExportManager::class, $importExportManagerMock = mock(ImportExportManager::class));
    $importExportManagerMock->shouldReceive('getRecordConfig')
        ->once()
        ->with('import', 'type-with-missing-headers')
        ->andReturn([
            'label' => 'Custom Import',
            'description' => 'Custom import description',
            'model' => MenuImport::class,
            'configFile' => [
                'columns' => [
                    'column1' => 'Column',
                ],
            ],
        ]);

    $history = History::factory()->create([
        'code' => 'type-with-missing-headers',
    ]);
    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'import/type-with-missing-headers/'.$history->uuid]))
        ->assertSee(lang('igniterlabs.importexport::default.error_missing_csv_headers'));
});

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
