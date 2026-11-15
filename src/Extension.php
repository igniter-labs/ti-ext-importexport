<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Classes\Sources\FlipdishSource;
use IgniterLabs\ImportExport\Classes\Sources\GloriaFoodSource;
use IgniterLabs\ImportExport\Classes\Sources\JustEatSource;
use IgniterLabs\ImportExport\Classes\Sources\UberEatsSource;
use IgniterLabs\ImportExport\Classes\Sources\WooCommerceSource;
use IgniterLabs\ImportExport\Models\CustomerExport;
use IgniterLabs\ImportExport\Models\CustomerImport;
use IgniterLabs\ImportExport\Models\LocationExport;
use IgniterLabs\ImportExport\Models\LocationImport;
use IgniterLabs\ImportExport\Models\MenuExport;
use IgniterLabs\ImportExport\Models\MenuImport;
use IgniterLabs\ImportExport\Models\PlatformMenuImport;
use Override;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public array $singletons = [
        ImportExportManager::class,
    ];

    public function registerImportExport(): array
    {
        return [
            'import' => [
                'menus' => [
                    'label' => 'Import Menu Items',
                    'model' => MenuImport::class,
                    'configFile' => 'igniterlabs.importexport::/models/menuimport',
                    'permissions' => ['Admin.Menus'],
                ],
                'customers' => [
                    'label' => 'Import Customers',
                    'model' => CustomerImport::class,
                    'configFile' => 'igniterlabs.importexport::/models/customerimport',
                    'permissions' => ['Admin.Customers'],
                ],
                'locations' => [
                    'label' => 'Import Locations',
                    'model' => LocationImport::class,
                    'configFile' => 'igniterlabs.importexport::/models/locationimport',
                    'permissions' => ['Admin.Locations'],
                ],
            ] + $this->registerPlatformImports(),
            'export' => [
                'menus' => [
                    'label' => 'Export Menu Items',
                    'model' => MenuExport::class,
                    'configFile' => 'igniterlabs.importexport::/models/menuexport',
                    'permissions' => ['Admin.Menus'],
                ],
                'customers' => [
                    'label' => 'Export Customers',
                    'model' => CustomerExport::class,
                    'configFile' => 'igniterlabs.importexport::/models/customerexport',
                    'permissions' => ['Admin.Customers'],
                ],
                'locations' => [
                    'label' => 'Export Locations',
                    'model' => LocationExport::class,
                    'configFile' => 'igniterlabs.importexport::/models/locationexport',
                    'permissions' => ['Admin.Locations'],
                ],
            ],
        ];
    }

    /**
     * Import types for moving menus, customers and locations from other ordering platforms.
     */
    protected function registerPlatformImports(): array
    {
        $sources = [
            'gloriafood' => GloriaFoodSource::class,
            'woocommerce' => WooCommerceSource::class,
            'ubereats' => UberEatsSource::class,
            'justeat' => JustEatSource::class,
            'flipdish' => FlipdishSource::class,
        ];

        $entities = [
            'parseMenu' => ['menus', 'Menu Items', PlatformMenuImport::class, 'platformmenuimport', ['Admin.Menus']],
            'parseCustomers' => ['customers', 'Customers', CustomerImport::class, 'platformcustomerimport', ['Admin.Customers']],
            'parseLocations' => ['locations', 'Locations', LocationImport::class, 'platformlocationimport', ['Admin.Locations']],
        ];

        $imports = [];
        foreach ($sources as $code => $sourceClass) {
            $source = new $sourceClass;
            foreach ($entities as $method => [$entity, $label, $model, $configFile, $permissions]) {
                if (!$source->supports($method)) {
                    continue;
                }

                $imports[$code.'_'.$entity] = [
                    'label' => sprintf('Import %s from %s (%s)', $label, $source->label(), strtoupper((string)$source->format($method))),
                    'model' => $model,
                    'configFile' => 'igniterlabs.importexport::/models/'.$configFile,
                    'permissions' => $permissions,
                    'source' => $sourceClass,
                    'sourceMethod' => $method,
                ];
            }
        }

        return $imports;
    }

    #[Override]
    public function registerMailTemplates(): array
    {
        return [
            'igniterlabs.importexport::mail.import_completed' => [
                'label' => 'lang:igniterlabs.importexport::default.text_mail_import_completed',
            ],
            'igniterlabs.importexport::mail.import_failed' => [
                'label' => 'lang:igniterlabs.importexport::default.text_mail_import_failed',
            ],
        ];
    }

    #[Override]
    public function registerPermissions(): array
    {
        return [
            'IgniterLabs.ImportExport.Manage' => [
                'description' => 'Access import/export tool',
                'group' => 'igniter::system.permissions.name',
            ],
        ];
    }

    #[Override]
    public function registerNavigation(): array
    {
        return [
            'tools' => [
                'child' => [
                    'importexport' => [
                        'priority' => 200,
                        'class' => 'importexport',
                        'href' => admin_url('igniterlabs/importexport/import_export'),
                        'title' => 'Import/Export',
                        'permission' => 'IgniterLabs.ImportExport.Manage',
                    ],
                ],
            ],
        ];
    }
}
