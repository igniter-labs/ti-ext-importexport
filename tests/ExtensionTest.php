<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests;

use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Extension;
use IgniterLabs\ImportExport\Models\MenuExport;
use IgniterLabs\ImportExport\Models\MenuImport;

it('registers the extension', function(): void {
    (new Extension(app()))->register();

    expect(app()->bound(ImportExportManager::class))->toBeTrue();
});

it('registers import & export record types', function(): void {
    $definitions = (new Extension(app()))->registerImportExport();

    expect($definitions['import']['menus'])->toHaveKey('model', MenuImport::class)
        ->and($definitions['import']['menus'])->toHaveKey('configFile', 'igniterlabs.importexport::/models/menuimport')
        ->and($definitions['import']['menus'])->toHaveKey('permissions', ['Admin.Menus'])
        ->and($definitions['export']['menus'])->toHaveKey('model', MenuExport::class)
        ->and($definitions['export']['menus'])->toHaveKey('configFile', 'igniterlabs.importexport::/models/menuexport')
        ->and($definitions['export']['menus'])->toHaveKey('permissions', ['Admin.Menus']);
});

it('registers permissions', function(): void {
    $extension = new Extension(app());
    $permissions = $extension->registerPermissions();

    expect($permissions)
        ->toHaveKey('IgniterLabs.ImportExport.Manage', [
            'description' => 'Access import/export tool',
            'group' => 'igniter::system.permissions.name',
        ]);
});

it('registers admin navigation menu', function(): void {
    $extension = new Extension(app());
    $navMenus = $extension->registerNavigation();

    expect($navMenus['tools']['child'])
        ->toHaveKey('importexport', [
            'priority' => 200,
            'class' => 'importexport',
            'href' => admin_url('igniterlabs/importexport/import_export'),
            'title' => 'Import/Export',
            'permission' => 'IgniterLabs.ImportExport.Manage',
        ]);
});
