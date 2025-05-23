<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Http\Actions;

use Igniter\Admin\Classes\BaseWidget;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\ControllerAction;
use IgniterLabs\ImportExport\Http\Controllers\ImportExport;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Models\ImportModel;
use IgniterLabs\ImportExport\Traits\ImportExportHelper;
use League\Csv\Reader as CsvReader;
use Throwable;

class ImportController extends ControllerAction
{
    use ImportExportHelper;
    use ValidatesForm;

    /**
     * @var Model Import model
     */
    public $importModel;

    /**
     * @var array Import column configuration.
     */
    public $importColumns;

    /**
     * @var BaseWidget Reference to the toolbar widget objects.
     */
    protected $importToolbarWidget;

    /**
     * @var Form Reference to the widget used for uploading import file.
     */
    protected $importPrimaryFormWidget;

    /**
     * @var Form Reference to the widget used for specifying import options.
     */
    protected $importSecondaryFormWidget;

    protected array $requiredProperties = ['importConfig'];

    protected $requiredConfig = ['configFile'];

    /**
     * Behavior constructor
     * @param ImportExport $controller
     */
    public function __construct($controller)
    {
        $classPath = strtolower(str_replace('\\', '/', static::class));
        $controller->partialPath[] = '$/'.$classPath;

        parent::__construct($controller);

        // Build configuration
        $this->setConfig($controller->importConfig, $this->requiredConfig);
    }

    public function import(string $context, string $recordName, string $historyUuid)
    {
        $this->loadRecordConfig($context, $recordName);

        if (!is_null($redirect = $this->checkPermissions())) {
            flash()->error(lang('igniter::admin.alert_user_restricted'));

            return $redirect;
        }

        /** @var History|null $history */
        $history = History::query()->where('uuid', $historyUuid)->where('code', $recordName)->first();
        if (!$history || !$history->csvExists()) {
            flash()->error(lang('igniterlabs.importexport::default.error_invalid_import_file'));

            return $this->getRedirectUrl();
        }

        $pageTitle = lang($this->getConfig('record[title]', 'igniterlabs.importexport::default.text_import_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initImportForms();

        $this->prepareImportVars($history);

        return null;
    }

    public function renderImport(): string
    {
        if (!is_null($this->importToolbarWidget)) {
            $import[] = $this->importToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('import_container');

        return implode(PHP_EOL, $import);
    }

    public function onLoadImportForm()
    {
        try {
            $validated = request()->validate([
                'code' => ['required', 'string'],
                'import_file' => ['required', 'file', 'mimes:csv,txt'],
            ], [
                'code.required' => 'You must choose a record type to import',
                'import_file.required' => 'You must upload a file to import',
                'import_file.file' => 'You must upload a valid csv file',
                'import_file.mimes' => 'You must upload a valid csv file',
            ]);

            $this->loadRecordConfig('import', $recordName = $validated['code']);

            throw_unless($this->getConfig('record'), new FlashException($recordName.' is not a registered import template'));

            if (!is_null($redirect = $this->checkPermissions())) {
                flash()->error(lang('igniter::admin.alert_user_restricted'));

                return $redirect;
            }

            /** @var History $history */
            $history = History::create([
                'user_id' => $this->controller->getUser()->getKey(),
                'type' => 'import',
                'code' => $recordName,
                'status' => 'pending',
            ]);

            $csvPath = $history->getCsvPath();
            if (!File::exists($directory = dirname((string)$csvPath))) {
                File::makeDirectory($directory, 0755, true);
            }

            $uploadedFile = request()->file('import_file');
            File::put($csvPath, File::get($uploadedFile->getRealPath()));

            return $this->controller->redirect('igniterlabs/importexport/import_export/import/'.$recordName.'/'.$history->uuid);
        } catch (Throwable $ex) {
            flash()->error($ex->getMessage())->important();

            return $this->getRedirectUrl();
        }
    }

    public function import_onDeleteImportFile($context, $recordName, $historyUuid)
    {
        throw_unless(
            $history = History::query()->where('uuid', $historyUuid)->where('code', $recordName)->first(),
            new FlashException(lang('igniterlabs.importexport::default.error_invalid_import_file')),
        );

        $history->delete();

        return $this->getRedirectUrl();
    }

    public function import_onImport($context, $recordName, $historyUuid)
    {
        $this->loadRecordConfig($context, $recordName);

        throw_if($this->checkPermissions(), new FlashException(lang('igniter::admin.alert_user_restricted')));

        $validated = request()->validate([
            'delimiter' => ['required', 'string'],
            'enclosure' => ['required', 'string'],
            'escape' => ['required', 'string'],
            'ImportSecondary' => ['nullable', 'array'],
            'match_columns' => ['required', 'array'],
            'match_columns.*' => ['required', 'string'],
            'import_columns' => ['required', 'array'],
            'import_columns.*' => ['required', 'string'],
        ]);

        throw_unless(
            /** @var History|null $history */
            $history = History::query()->where('uuid', $historyUuid)->where('code', $recordName)->first(),
            new FlashException(lang('igniterlabs.importexport::default.error_invalid_import_file')),
        );

        $model = $this->getImportModel();

        $history->update(['status' => 'processing', 'attempted_data' => $validated]);

        if ($optionData = array_get($validated, 'ImportSecondary')) {
            $model->fill($optionData);
        }

        throw_unless(
            $importColumns = $this->processImportColumnsFromRequest($validated),
            new FlashException(lang('igniterlabs.importexport::default.error_empty_import_columns')),
        );

        $importOptions = array_except($validated, ['ImportSecondary', 'match_columns', 'import_columns']);
        $model->import($importColumns, $importOptions, $history->getCsvPath());

        $history->markCompleted([
            'error_message' => $this->buildImportResultMessage($model->getResultStats()),
        ]);

        File::delete($history->getCsvPath());

        return $this->getRedirectUrl();
    }

    /**
     * @return ImportModel
     */
    public function getImportModel()
    {
        return $this->importModel ??= new ($this->getModelForType('import'));
    }

    protected function initImportForms()
    {
        $model = $this->getImportModel();

        $this->importPrimaryFormWidget = $this->makePrimaryFormWidgetForType($model, 'import');

        $this->importSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($model, 'import');
    }

    protected function prepareImportVars(History $history)
    {
        $this->vars['recordTitle'] = $this->getConfig('record[title]', 'Unknown Title');
        $this->vars['recordDescription'] = $this->getConfig('record[description]', 'Unknown description');
        $this->vars['importPrimaryFormWidget'] = $this->importPrimaryFormWidget;
        $this->vars['importSecondaryFormWidget'] = $this->importSecondaryFormWidget;
        $this->vars['importColumns'] = $importColumns = $this->getImportColumns();
        $this->vars['importFileUuid'] = $history->uuid;
        $this->vars['importFileColumns'] = $this->getImportFileColumns($history, $importColumns);

        // Make these variables available to widgets
        $this->controller->vars += $this->vars;
    }

    protected function getImportColumns()
    {
        if (is_null($this->importColumns)) {
            $configFile = $this->getConfig('record[configFile]');
            $columns = $this->makeListColumns($configFile);

            throw_unless($columns,
                new FlashException(lang('igniterlabs.importexport::default.error_empty_import_columns')),
            );

            $this->importColumns = collect($columns)->map(fn($label): string => lang($label))->all();
        }

        return $this->importColumns;
    }

    protected function getImportFileColumns(History $history, array $importColumns): array
    {
        $reader = CsvReader::createFromPath($history->getCsvPath(), 'r');
        $firstRow = $reader->nth(0);

        return array_diff($firstRow, array_values($importColumns)) === []
            ? $firstRow
            : throw new FlashException(lang('igniterlabs.importexport::default.error_missing_csv_headers'));
    }

    protected function processImportColumnsFromRequest(array $request): array
    {
        $definitions = $this->getImportColumns();
        $dbColumns = array_filter(array_get($request, 'import_columns', []));

        return collect(array_get($request, 'match_columns', []))
            ->filter(fn($fileColumn): bool => in_array($fileColumn, $definitions))
            ->mapWithKeys(fn($fileColumn, $index) => [$index => [array_get($dbColumns, $index), $fileColumn]])
            ->filter()
            ->all();
    }

    //
    //
    //
    /**
     * Called before the form fields are defined.
     *
     * @param Form $host The hosting form widget
     *
     * @return void
     */
    public function importFormExtendFieldsBefore($host) {}

    /**
     * Called after the form fields are defined.
     *
     * @param Form $host The hosting form widget
     *
     * @return void
     */
    public function importFormExtendFields($host, $fields) {}

    protected function buildImportResultMessage(array $importResults): string
    {
        $importResults = (object)$importResults;

        $resultMessage = implode(PHP_EOL, array_filter([
            sprintf(lang('igniterlabs.importexport::default.text_import_created'), $importResults->created),
            sprintf(lang('igniterlabs.importexport::default.text_import_updated'), $importResults->updated),
            sprintf(lang('igniterlabs.importexport::default.text_import_errors'), $importResults->errorCount),
        ]));

        if ($importResults->errorCount) {
            $resultMessage .= PHP_EOL;
            $resultMessage .= collect($importResults->errors)->map(fn($message, $row): string => sprintf(lang('igniterlabs.importexport::default.text_import_row'), $row, $message))->implode(PHP_EOL);
        }

        return $resultMessage;
    }
}
