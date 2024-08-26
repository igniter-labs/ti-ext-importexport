<?php

namespace IgniterLabs\ImportExport;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\ImportExport\Classes\ImportExportManager;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public array $singletons = [
        ImportExportManager::class,
    ];

    public function registerPermissions(): array
    {
        return [
            'IgniterLabs.ImportExport.Manage' => [
                'description' => 'Access import/export tool',
                'group' => 'igniter::system.permissions.name',
            ],
        ];
    }

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
