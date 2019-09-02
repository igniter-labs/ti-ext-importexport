<?php

namespace Igniter\ImportExport\Controllers;

use Admin\Classes\AdminController;
use AdminMenu;

class Menus extends AdminController
{
    public $implement = [
        'Igniter\ImportExport\Actions\ImportExportController',
    ];

    public $importExportConfig = [
        'import' => [
            'title' => 'Import Menus',
            'model' => 'Igniter\ImportExport\Models\Import',
            'configFile' => 'menuimport',
            'redirect' => 'igniter/importexport/menus',
        ],
        'export' => [
            'title' => 'Export Menus',
            'model' => 'Igniter\ImportExport\Models\Export',
            'configFile' => 'menuexport',
//            'useList' => 'list',
            'redirect' => 'igniter/importexport/menus',
        ],
    ];

    protected $requiredPermissions = 'Igniter.ImportExport';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('importexport', 'tools');
    }
}