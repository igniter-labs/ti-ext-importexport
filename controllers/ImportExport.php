<?php

namespace Igniter\ImportExport\Controllers;

use Admin\Classes\AdminController;
use AdminMenu;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\ImportExport\Classes\ImportExportManager;
use Template;

class ImportExport extends AdminController
{
    public $implement = [
        'Igniter\ImportExport\Actions\ImportExportController',
    ];

    public $importExportConfig = [
        'import' => [
            'title' => 'Import Records',
            'configFile' => '~/extensions/igniter/importexport/models/config/importmodel',
            'redirect' => 'igniter/importexport/importexport/import',
        ],
        'export' => [
            'title' => 'Export Records',
            'configFile' => '~/extensions/igniter/importexport/models/config/exportmodel',
            'redirect' => 'igniter/importexport/importexport/export',
        ],
    ];

    protected $requiredPermissions = 'Igniter.ImportExport.Manage';

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

    public function index_onLoadPopup()
    {
        $context = post('context');
        if (!in_array($context, ['import', 'export']))
            throw new ApplicationException('Invalid type specified');

        $this->vars['context'] = $context;
        $this->vars['importExports'] = ImportExportManager::instance()->listImportExportsForType($context);

        return ['#importExportModalContent' => $this->makePartial('new_import_export_popup')];
    }

    public function index_onLoadForm()
    {
        $context = post('context');
        if (!in_array($context, ['import', 'export']))
            throw new ApplicationException('Invalid type specified');

        $code = post('code');
        if (!$config = ImportExportManager::instance()->getRecordConfig($context, $code))
            throw new ApplicationException($code.' is not a registered import/export template');

        return $this->redirect('igniter/importexport/importexport/'.$context.'/'.$code);
    }
}