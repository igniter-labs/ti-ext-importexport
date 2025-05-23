<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Http\Controllers;

use Igniter\Flame\Support\Facades\File;
use Igniter\User\Models\User;
use Igniter\User\Models\UserRole;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Models\MenuExport;

it('redirects to correct form when loading export records', function(): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-menus',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadExportForm',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export/export/igniterlabs-importexport-menus'),
        ]);
});

it('throws exception when loading export form with incorrect code', function(): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'invalid-code',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadExportForm',
        ])
        ->assertSee('invalid-code is not a registered export template');
});

it('flashes validation error when loading export form with restricted access', function(): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1],
    ]);
    $user = User::factory()->for($userRole, 'role')->create();

    $this
        ->actingAs($user, 'igniter-admin')
        ->post(route('igniterlabs.importexport.import_export'), [
            'code' => 'igniterlabs-importexport-menus',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadExportForm',
        ])
        ->assertJsonFragment([
            'X_IGNITER_REDIRECT' => admin_url('igniterlabs/importexport/import_export'),
        ]);

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
});

it('loads export records form page', function(): void {
    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'export/igniterlabs-importexport-menus']))
        ->assertOk();
});

it('does not load export records form page when user has restricted access', function(): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1],
    ]);

    $this
        ->actingAs(User::factory()->for($userRole, 'role')->create(), 'igniter-admin')
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'export/igniterlabs-importexport-menus']))
        ->assertRedirectContains('igniterlabs/importexport/import_export');

    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
});

it('throws exception when registered export type columns is missing', function(): void {
    app()->instance(ImportExportManager::class, $importExportManagerMock = mock(ImportExportManager::class));
    $importExportManagerMock->shouldReceive('getRecordConfig')
        ->once()
        ->with('export', 'type-with-missing-columns')
        ->andReturn([
            'label' => 'Custom Export',
            'description' => 'Custom export description',
            'model' => MenuExport::class,
            'configFile' => [],
        ]);

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'export/type-with-missing-columns']), [
            'offset' => '0',
            'limit' => '',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'export_columns' => [
                'menu_id',
                'menu_name',
                'menu_price',
                'menu_description',
                'minimum_qty',
                'categories',
                'menu_status',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onExport',
        ])
        ->assertSee(lang('igniterlabs.importexport::default.error_empty_export_columns'));
});

it('processes export', function(): void {
    File::deleteDirectory(dirname((new History)->getCsvPath()));

    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export', ['slug' => 'export/igniterlabs-importexport-menus']), [
            'offset' => '2',
            'limit' => '10',
            'delimiter' => ',',
            'enclosure' => '"',
            'escape' => '\\',
            'ExportSecondary' => [
                'skip' => '0',
            ],
            'export_columns' => [
                'menu_id',
                'menu_name',
                'menu_price',
                'menu_description',
                'minimum_qty',
                'categories',
                'menu_status',
            ],
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onExport',
        ]);

    $history = History::query()
        ->where('type', 'export')
        ->where('code', 'igniterlabs-importexport-menus')
        ->where('status', 'completed')
        ->first();

    expect($history)->not->toBeNull()
        ->and(flash()->messages()->first())->message->toBe(lang('igniterlabs.importexport::default.alert_export_success'));
});

it('throws exception when downloading export file with invalid uuid', function(): void {
    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'download/igniterlabs-importexport-menus/invalid-uuid']))
        ->assertRedirect();

    expect(flash()->messages()->first())->message->toBe(lang('igniterlabs.importexport::default.error_export_not_found'));
});

it('does not download exported file when user has restricted access', function(): void {
    $userRole = UserRole::factory()->create([
        'permissions' => ['IgniterLabs.ImportExport.Manage' => 1],
    ]);

    $this
        ->actingAs(User::factory()->for($userRole, 'role')->create(), 'igniter-admin')
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'download/igniterlabs-importexport-menus/history-uuid']));
    expect(flash()->messages()->first())->message->toBe(lang('igniter::admin.alert_user_restricted'));
});

it('downloads exported file', function(): void {
    $history = History::factory()->create([
        'type' => 'export',
        'code' => 'igniterlabs-importexport-menus',
        'status' => 'completed',
    ]);

    File::put($history->getCsvPath(), File::get(__DIR__.'/../../_fixtures/valid_import_file.csv'));

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'download/igniterlabs-importexport-menus/'.$history->uuid]))
        ->assertDownload();
});
