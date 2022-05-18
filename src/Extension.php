<?php

namespace IgniterLabs\ImportExport;

use Igniter\System\Classes\BaseExtension;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public function registerPermissions()
    {
        return [
            'IgniterLabs.ImportExport.Manage' => [
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
                        'href' => admin_url('igniterlabs/importexport/importexport'),
                        'title' => 'Import/Export',
                        'permission' => 'IgniterLabs.ImportExport.Manage',
                    ],
                ],
            ],
        ];
    }
}
