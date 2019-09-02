<?php

namespace Igniter\ImportExport\Controllers;

use Admin\Classes\AdminController;
use AdminMenu;
use Template;

class ImportExport extends AdminController
{
    public $implement = [
        'Igniter\ImportExport\Actions\ImportExportController',
    ];

    public $importExportConfig = [
        'import' => [
            'title' => 'Import Records',
            'model' => 'Igniter\ImportExport\Models\ImportModel',
            'configFile' => '~/extensions/igniter/importexport/models/config/importmodel',
            'redirect' => 'igniter/importexport/importexport/import',
        ],
        'export' => [
            'title' => 'Export Records',
            'model' => 'Igniter\ImportExport\Models\ExportModel',
            'configFile' => '~/extensions/igniter/importexport/models/config/exportmodel',
            'redirect' => 'igniter/importexport/importexport/export',
        ],
    ];

    protected $requiredPermissions = 'Igniter.ImportExport';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('importexport', 'tools');
    }

    public function index()
    {
        $pageTitle = lang('igniter.importexport::default.text_index_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);
    }
}