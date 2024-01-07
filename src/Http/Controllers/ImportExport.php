<?php

namespace IgniterLabs\ImportExport\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\FlashException;
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
        throw_if(!in_array($context, ['import', 'export']), FlashException::error('Invalid type specified'));

        $this->vars['context'] = $context;
        $this->vars['importExports'] = resolve(ImportExportManager::class)->listImportExportsForType($context);

        return ['#importExportModalContent' => $this->makePartial('new_import_export_popup')];
    }

    public function index_onLoadForm()
    {
        $context = post('context');
        throw_unless(in_array($context, ['import', 'export']), FlashException::error('Invalid type specified'));

        throw_unless(strlen($code = post('code')), FlashException::error('You must choose a type to import'));

        throw_unless(resolve(ImportExportManager::class)->getRecordConfig($context, $code),
            FlashException::error($code.' is not a registered import/export template')
        );

        return $this->redirect('igniterlabs/importexport/importexport/'.$context.'/'.$code);
    }
}
