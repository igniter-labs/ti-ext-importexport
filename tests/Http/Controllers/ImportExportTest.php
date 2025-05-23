<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Http\Controllers;

use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Models\History;

it('loads index page', function(): void {
    History::factory()->count(5)->create();
    History::factory()->count(5)->create([
        'type' => 'export',
        'status' => 'completed',
    ]);

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export'))
        ->assertOk();
});

it('loads import/export popup with correct context', function(string $context): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'context' => $context,
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadPopup',
        ])
        ->assertSee(lang('igniterlabs.importexport::default.text_'.$context.'_title'));
})->with([
    'import' => 'import',
    'export' => 'export',
]);

it('throws exception when loading import/export popup with incorrect context', function(): void {
    actingAsSuperUser()
        ->post(route('igniterlabs.importexport.import_export'), [
            'context' => 'invalid',
        ], [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-IGNITER-REQUEST-HANDLER' => 'onLoadPopup',
        ])
        ->assertSee('Invalid type specified');
});

it('does not load export records form page when registered record is missing model', function(): void {
    app()->instance(ImportExportManager::class, $importExportManagerMock = mock(ImportExportManager::class));
    $importExportManagerMock->shouldReceive('getRecordConfig')
        ->once()
        ->with('export', 'type-with-missing-model')
        ->andReturn([
            'label' => 'Custom Export',
            'description' => 'Custom export description',
            'configFile' => [],
        ]);

    actingAsSuperUser()
        ->get(route('igniterlabs.importexport.import_export', ['slug' => 'export/type-with-missing-model']))
        ->assertSee(sprintf(lang('igniterlabs.importexport::default.error_missing_model'), 'export'));
});
