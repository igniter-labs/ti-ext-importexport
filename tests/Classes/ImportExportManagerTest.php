<?php

declare(strict_types=1);

use Igniter\System\Classes\ExtensionManager;
use IgniterLabs\ImportExport\Classes\ImportExportManager;

beforeEach(function(): void {
    $this->extensionManagerMock = Mockery::mock(ExtensionManager::class);
    app()->instance(ExtensionManager::class, $this->extensionManagerMock);
    $this->importExportManager = new ImportExportManager;
});

it('can get record config', function(): void {
    $type = 'import';
    $name = 'test-config';
    $mockData = [$type => [$name => 'config-value']];

    $importConfig = [
        'label' => 'Import Items',
        'description' => 'Test import items',
        'model' => TestImport::class,
        'configFile' => __DIR__.'/../_fixtures/testimport',
    ];
    $exportConfig = [
        'label' => 'Export Items',
        'description' => 'Test export items',
        'model' => TestExport::class,
        'configFile' => __DIR__.'/../_fixtures/testexport',
    ];

    $this->extensionManagerMock
        ->shouldReceive('getRegistrationMethodValues')
        ->with('registerImportExport')
        ->andReturn([
            'test.extension' => [
                'import' => [
                    'test-record' => $importConfig,
                ],
                'export' => [
                    'test-record' => $exportConfig,
                ],
                'invalid-type' => [
                    'test-record' => [],
                ],
            ],
        ]);

    expect($this->importExportManager->getRecordConfig('import', 'test-extension-test-record'))->toBe($importConfig)
        ->and($this->importExportManager->getRecordConfig('export', 'test-extension-test-record'))->toBe($exportConfig)
        ->and($this->importExportManager->listImportExportsForType('export'))->toBeArray()
        ->and($this->importExportManager->listImportExportsForType('invalid-type'))->toBe([]);
});
