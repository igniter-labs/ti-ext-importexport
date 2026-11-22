<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests;

use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Classes\Sources\UberEatsSource;
use IgniterLabs\ImportExport\Extension;
use IgniterLabs\ImportExport\Models\CustomerExport;
use IgniterLabs\ImportExport\Models\CustomerImport;
use IgniterLabs\ImportExport\Models\LocationExport;
use IgniterLabs\ImportExport\Models\LocationImport;
use IgniterLabs\ImportExport\Models\MenuExport;
use IgniterLabs\ImportExport\Models\MenuImport;
use IgniterLabs\ImportExport\Models\PlatformMenuImport;

it('registers the extension', function(): void {
    (new Extension(app()))->register();

    expect(app()->bound(ImportExportManager::class))->toBeTrue();
});

it('registers import & export record types', function(): void {
    $definitions = (new Extension(app()))->registerImportExport();

    expect($definitions['import']['menus'])->toHaveKey('model', MenuImport::class)
        ->and($definitions['import']['menus'])->toHaveKey('configFile', 'igniterlabs.importexport::/models/menuimport')
        ->and($definitions['import']['menus'])->toHaveKey('permissions', ['Admin.Menus'])
        ->and($definitions['import']['customers'])->toHaveKey('model', CustomerImport::class)
        ->and($definitions['import']['customers'])->toHaveKey('permissions', ['Admin.Customers'])
        ->and($definitions['import']['locations'])->toHaveKey('model', LocationImport::class)
        ->and($definitions['import']['locations'])->toHaveKey('permissions', ['Admin.Locations'])
        ->and($definitions['export']['menus'])->toHaveKey('model', MenuExport::class)
        ->and($definitions['export']['menus'])->toHaveKey('configFile', 'igniterlabs.importexport::/models/menuexport')
        ->and($definitions['export']['menus'])->toHaveKey('permissions', ['Admin.Menus'])
        ->and($definitions['export']['customers'])->toHaveKey('model', CustomerExport::class)
        ->and($definitions['export']['customers'])->toHaveKey('permissions', ['Admin.Customers'])
        ->and($definitions['export']['locations'])->toHaveKey('model', LocationExport::class)
        ->and($definitions['export']['locations'])->toHaveKey('permissions', ['Admin.Locations']);
});

it('registers platform import types for each supported source', function(): void {
    $imports = (new Extension(app()))->registerImportExport()['import'];

    expect(array_keys($imports))->toContain(
        'gloriafood_menus', 'gloriafood_customers', 'gloriafood_locations',
        'woocommerce_menus', 'woocommerce_customers',
        'ubereats_menus', 'ubereats_locations',
        'justeat_menus', 'justeat_locations',
        'flipdish_menus', 'flipdish_customers', 'flipdish_locations',
    )
        ->and($imports)->not->toHaveKeys(['woocommerce_locations', 'ubereats_customers', 'justeat_customers'])
        ->and($imports['ubereats_menus'])->toMatchArray([
            'label' => 'Import Menu Items from Uber Eats (JSON)',
            'model' => PlatformMenuImport::class,
            'configFile' => 'igniterlabs.importexport::/models/platformmenuimport',
            'permissions' => ['Admin.Menus'],
            'source' => UberEatsSource::class,
            'sourceMethod' => 'parseMenu',
        ])
        ->and($imports['woocommerce_customers']['label'])->toBe('Import Customers from WooCommerce (CSV)')
        ->and($imports['flipdish_locations']['permissions'])->toBe(['Admin.Locations']);
});

it('registers import mail templates', function(): void {
    expect(array_keys((new Extension(app()))->registerMailTemplates()))->toBe([
        'igniterlabs.importexport::mail.import_completed',
        'igniterlabs.importexport::mail.import_failed',
    ]);
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
