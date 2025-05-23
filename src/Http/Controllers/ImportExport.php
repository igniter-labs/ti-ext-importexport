<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Http\Controllers;

use Igniter\Admin\Classes\AdminController;
use Igniter\Admin\Facades\AdminMenu;
use Igniter\Admin\Facades\Template;
use Igniter\Flame\Exception\FlashException;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Http\Actions\ExportController;
use IgniterLabs\ImportExport\Http\Actions\ImportController;
use IgniterLabs\ImportExport\Models\History;

class ImportExport extends AdminController
{
    public array $implement = [
        ImportController::class,
        ExportController::class,
    ];

    public $importConfig = [
        'title' => 'Import Records',
        'configFile' => 'importmodel',
        'redirect' => 'igniterlabs/importexport/import_export',
    ];

    public $exportConfig = [
        'title' => 'Export Records',
        'configFile' => 'exportmodel',
        'redirect' => 'igniterlabs/importexport/import_export',
        'back' => 'igniterlabs/importexport/import_export',
    ];

    protected null|string|array $requiredPermissions = 'IgniterLabs.ImportExport.Manage';

    public function __construct()
    {
        parent::__construct();

        AdminMenu::setContext('importexport', 'tools');
    }

    public function index(): void
    {
        $pageTitle = lang('igniterlabs.importexport::default.text_index_title');
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->vars['history'] = History::query()
            ->orderBy('updated_at', 'desc')
            ->paginate(25, ['*'], 'page', input('page'));
    }

    public function index_onLoadPopup(): array
    {
        $context = post('context');
        throw_if(!in_array($context, ['import', 'export']), new FlashException('Invalid type specified'));

        $this->vars['context'] = $context;
        $this->vars['importExports'] = resolve(ImportExportManager::class)->listImportExportsForType($context);

        return [
            '#importExportModalContent' => $this->makePartial($context === 'export' ? 'new_export_popup' : 'new_import_popup'),
        ];
    }
}
