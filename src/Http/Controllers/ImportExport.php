<?php

namespace IgniterLabs\ImportExport\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\ApplicationException;
use IgniterLabs\ImportExport\Classes\ImportExportManager;

class ImportExport extends AdminController
{
    public $implement = [
        \IgniterLabs\ImportExport\Controllers\Actions\ImportController::class,
        \IgniterLabs\ImportExport\Controllers\Actions\ExportController::class,
    ];

    public $importConfig = [
        'title' => 'Import Records',
        'configFile' => '$/igniterlabs/importexport/models/config/importmodel',
        'redirect' => 'igniterlabs/importexport/importexport/import',
    ];

    public $exportConfig = [
        'title' => 'Export Records',
        'configFile' => '$/igniterlabs/importexport/models/config/exportmodel',
        'redirect' => 'igniterlabs/importexport/importexport/export',
    ];

    protected $requiredPermissions = 'IgniterLabs.ImportExport.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('importexport', 'tools');
    }

    public function index()
    {
        $pageTitle = lang('igniterlabs.importexport::default.text_index_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);
    }

    public function index_onLoadPopup()
    {
        $context = post('context');
        if (!in_array($context, ['import', 'export'])) {
            throw new ApplicationException('Invalid type specified');
        }

        $this->vars['context'] = $context;
        $this->vars['importExports'] = resolve(ImportExportManager::class)->listImportExportsForType($context);

        return ['#importExportModalContent' => $this->makePartial('new_import_export_popup')];
    }

    public function index_onLoadForm()
    {
        $context = post('context');
        if (!in_array($context, ['import', 'export'])) {
            throw new ApplicationException('Invalid type specified');
        }

        if (!strlen($code = post('code'))) {
            throw new ApplicationException('You must choose a type to import');
        }

        if (!$config = resolve(ImportExportManager::class)->getRecordConfig($context, $code)) {
            throw new ApplicationException($code.' is not a registered import/export template');
        }

        return $this->redirect('igniterlabs/importexport/importexport/'.$context.'/'.$code);
    }
}
