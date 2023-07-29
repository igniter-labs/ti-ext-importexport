<?php

namespace IgniterLabs\ImportExport;

use Igniter\System\Classes\BaseExtension;
use IgniterLabs\ImportExport\Classes\ImportExportManager;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public function register()
    {
        $this->app->singleton(ImportExportManager::class);
    }

    public function registerPermissions()
    {
        return [
            'IgniterLabs.ImportExport.Manage' => [
                'description' => 'Access import/export tool',
                'group' => 'advanced',
            ],
        ];
    }

    public function registerNavigation()
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
