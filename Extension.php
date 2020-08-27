<?php

namespace Igniter\ImportExport;

use System\Classes\BaseExtension;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public function registerPermissions()
    {
        return [
            'Igniter.ImportExport.Manage' => [
                'description' => 'Access import/export tool',
                'group' => 'module',
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
                        'href' => admin_url('igniter/importexport/importexport'),
                        'title' => 'Import/Export',
                        'permission' => 'Igniter.ImportExport.Manage',
                    ],
                ],
            ],
        ];
    }
}
