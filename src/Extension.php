<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Models\MenuExport;
use IgniterLabs\ImportExport\Models\MenuImport;
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
            ],
            'export' => [
                'menus' => [
                    'label' => 'Export Menu Items',
                    'model' => MenuExport::class,
                    'configFile' => 'igniterlabs.importexport::/models/menuexport',
                    'permissions' => ['Admin.Menus'],
                ],
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
