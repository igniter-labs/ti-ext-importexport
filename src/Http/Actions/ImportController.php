<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Http\Actions;

use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\ControllerAction;
use IgniterLabs\ImportExport\Classes\Sources\ImportSource;
use IgniterLabs\ImportExport\Http\Controllers\ImportExport;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Models\ImportModel;
use IgniterLabs\ImportExport\Traits\ImportExportHelper;
use Illuminate\Validation\Rule;
use League\Csv\Reader as CsvReader;
use Throwable;

class ImportController extends ControllerAction
{
    use ImportExportHelper;
    use ValidatesForm;

    public ?ImportModel $importModel = null;

    protected ?ImportSource $importSource = null;

    /**
     * Import column configuration.
     */
    public ?array $importColumns = null;

    /**
     * Reference to the toolbar widget objects.
     */
    protected ?Toolbar $importToolbarWidget = null;

    /**
     * Reference to the widget used for uploading import file.
     */
    protected ?Form $importPrimaryFormWidget = null;

    /**
     * Reference to the widget used for specifying import options.
     */
    protected ?Form $importSecondaryFormWidget = null;

    protected array $requiredProperties = ['importConfig'];

    protected array $requiredConfig = ['configFile'];

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

        try {
            $this->initImportForms();

            $this->prepareImportVars($history);
        } catch (FlashException $ex) {
            // The uploaded file can't be imported, discard it and return to the list with the reason
            File::delete($history->getCsvPath());
            $history->delete();
            flash()->error($ex->getMessage())->important();

            return $this->getRedirectUrl();
        }

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
                'import_file' => ['required', 'file'],
            ], [
                'code.required' => 'You must choose a record type to import',
                'import_file.required' => 'You must upload a file to import',
                'import_file.file' => 'You must upload a valid csv file',
            ]);

            $this->loadRecordConfig('import', $recordName = $validated['code']);

            throw_unless($this->getConfig('record'), new FlashException($recordName.' is not a registered import template'));

            if (!is_null($redirect = $this->checkPermissions())) {
                flash()->error(lang('igniter::admin.alert_user_restricted'));

                return $redirect;
            }

            $this->validateImportFileType();

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

        if ($this->getImportSource() instanceof ImportSource) {
            return $this->processSourceImport($recordName, $historyUuid);
        }

        $validated = request()->validate([
            'delimiter' => ['required', 'string'],
            'enclosure' => ['required', 'string'],
            'escape' => ['required', 'string'],
            'ImportSecondary' => ['nullable', 'array'],
            'match_columns' => ['nullable', 'array'],
            'match_columns.*' => ['nullable', 'string'],
            'import_columns' => ['nullable', 'array'],
            'import_columns.*' => ['nullable', 'string', Rule::in(array_keys($this->getImportColumns()))],
        ]);

        /** @var History|null $history */
        $history = History::query()->where('uuid', $historyUuid)->where('code', $recordName)->first();
        throw_unless($history, new FlashException(
            lang('igniterlabs.importexport::default.error_invalid_import_file'),
        ));

        $importModel = $this->getImportModel();

        $history->update(['status' => 'processing', 'attempted_data' => $validated]);

        if ($optionData = array_get($validated, 'ImportSecondary')) {
            $importModel->fill($optionData);
        }

        throw_unless(
            $importColumns = $this->processImportColumnsFromRequest($validated),
            new FlashException(lang('igniterlabs.importexport::default.error_empty_import_columns')),
        );

        $importOptions = array_except($validated, ['ImportSecondary', 'match_columns', 'import_columns']);
        $importModel->import($importColumns, $importOptions, $history->getCsvPath());

        $history->markCompleted([
            'error_message' => $this->buildImportResultMessage($importModel->getResultStats()),
        ]);

        File::delete($history->getCsvPath());

        return $this->getRedirectUrl();
    }

    protected function processSourceImport(string $recordName, string $historyUuid)
    {
        $validated = request()->validate([
            'ImportSecondary' => ['nullable', 'array'],
        ]);

        $history = $this->findImportHistory($recordName, $historyUuid);

        $importModel = $this->getImportModel();

        $history->update(['status' => 'processing', 'attempted_data' => $validated]);

        if ($optionData = array_get($validated, 'ImportSecondary')) {
            $importModel->fill($optionData);
        }

        $importModel->importFromSource($this->getImportSource(), $this->getImportSourceMethod(), $history->getCsvPath());

        $history->markCompleted([
            'error_message' => $this->buildImportResultMessage($importModel->getResultStats()),
        ]);

        File::delete($history->getCsvPath());

        return $this->getRedirectUrl();
    }

    protected function findImportHistory(string $recordName, string $historyUuid): History
    {
        /** @var History|null $history */
        $history = History::query()->where('uuid', $historyUuid)->where('code', $recordName)->first();

        return $history ?? throw new FlashException(lang('igniterlabs.importexport::default.error_invalid_import_file'));
    }

    public function getImportSource(): ?ImportSource
    {
        if (!$sourceClass = $this->getConfig('record[source]')) {
            return null;
        }

        return $this->importSource ??= resolve($sourceClass);
    }

    protected function getImportSourceMethod(): string
    {
        return (string)$this->getConfig('record[sourceMethod]', 'parseMenu');
    }

    protected function validateImportFileType(): void
    {
        $extensions = (($source = $this->getImportSource()) instanceof ImportSource)
            ? $source->acceptedExtensions($this->getImportSourceMethod())
            : ['csv', 'txt'];

        $extension = strtolower((string)request()->file('import_file')?->getClientOriginalExtension());

        throw_unless(in_array($extension, $extensions, true), new FlashException(
            $source instanceof ImportSource
                ? sprintf(lang('igniterlabs.importexport::default.error_invalid_file_type'), $source->label(), implode(', ', $extensions))
                : 'You must upload a valid csv file',
        ));
    }

    /**
     * Summarise parsed source rows so the admin can check the file before importing.
     */
    protected function buildImportPreview(array $rows): array
    {
        $rows = collect($rows);

        return array_filter([
            'records' => $rows->count(),
            'categories' => $rows->pluck('categories')->flatten()->filter()->unique()->count(),
            'option_groups' => $rows->pluck('option_groups')->flatten(1)->filter()->count(),
            'addresses' => $rows->pluck('addresses')->flatten(1)->filter()->count(),
            'samples' => $rows->take(5)
                ->map(fn(array $row): string => (string)($row['menu_name'] ?? $row['location_name'] ?? $row['email'] ?? $row['full_name'] ?? ''))
                ->filter()
                ->all(),
        ]);
    }

    public function getImportModel(): ImportModel
    {
        return $this->importModel ??= new ($this->getModelForType('import'));
    }

    protected function initImportForms()
    {
        $importModel = $this->getImportModel();

        $this->importPrimaryFormWidget = $this->makePrimaryFormWidgetForType(
            $importModel, 'import', $this->getImportSource() instanceof ImportSource ? 'igniterlabs.importexport::/models/importsourcemodel' : null,
        );

        $this->importSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($importModel, 'import');
    }

    protected function prepareImportVars(History $history)
    {
        $this->vars['recordTitle'] = $this->getConfig('record[title]', 'Unknown Title');
        $this->vars['recordDescription'] = $this->getConfig('record[description]', 'Unknown description');
        $this->vars['importPrimaryFormWidget'] = $this->importPrimaryFormWidget;
        $this->vars['importSecondaryFormWidget'] = $this->importSecondaryFormWidget;
        $this->vars['importFileUuid'] = $history->uuid;

        if (($source = $this->getImportSource()) instanceof ImportSource) {
            $this->vars['importSourceLabel'] = $source->label();
            $this->vars['importPreview'] = $this->buildImportPreview(
                $source->parse($this->getImportSourceMethod(), $history->getCsvPath()),
            );
        } else {
            $this->vars['importColumns'] = $importColumns = $this->getImportColumns();
            $this->vars['importFileColumns'] = $importFileColumns = $this->getImportFileColumns($history);
            $this->vars['importFileColumnMatches'] = $this->guessImportColumnMatches($importFileColumns, $importColumns);
        }

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

            $this->importColumns = collect($columns)->map(fn(string $label): string => lang($label))->all();
        }

        return $this->importColumns;
    }

    protected function getImportFileColumns(History $history): array
    {
        $reader = CsvReader::createFromPath($history->getCsvPath(), 'r');
        $reader->skipInputBOM();

        $firstRow = $reader->nth(0);
        $headers = array_map($this->normalizeColumnName(...), $firstRow);

        throw_if(
            $headers === [] || in_array('', $headers, true) || count(array_unique($headers)) !== count($headers),
            new FlashException(lang('igniterlabs.importexport::default.error_missing_csv_headers')),
        );

        return $firstRow;
    }

    /**
     * Pre-select a database column for each file column whose header matches
     * the column name or its label. Unmatched file columns are left unmapped.
     */
    protected function guessImportColumnMatches(array $fileColumns, array $importColumns): array
    {
        $lookup = [];
        foreach ($importColumns as $column => $label) {
            $lookup[$this->normalizeColumnName($label)] ??= $column;
            $lookup[$this->normalizeColumnName($column)] ??= $column;
        }

        $matches = [];
        foreach ($fileColumns as $index => $fileColumn) {
            $column = $lookup[$this->normalizeColumnName($fileColumn)] ?? null;
            $matches[$index] = $column && !in_array($column, $matches, true) ? $column : null;
        }

        return $matches;
    }

    protected function normalizeColumnName(mixed $name): string
    {
        $name = str_replace("\u{FEFF}", '', (string)$name);

        return strtolower((string)preg_replace('/[\s_\-]+/', '', $name));
    }

    protected function processImportColumnsFromRequest(array $request): array
    {
        $dbColumns = array_get($request, 'import_columns') ?? [];

        $columns = collect(array_get($request, 'match_columns') ?? [])
            ->map(fn($fileColumn, $index): array => [$dbColumns[$index] ?? null, $fileColumn])
            ->filter(fn(array $pair): bool => filled($pair[0]) && filled($pair[1]));

        $duplicates = $columns->pluck(0)->duplicates();
        throw_if($duplicates->isNotEmpty(), new FlashException(sprintf(
            lang('igniterlabs.importexport::default.error_duplicate_import_columns'),
            $duplicates->unique()->map(fn($column): string => $this->getImportColumns()[$column] ?? $column)->implode(', '),
        )));

        return $columns->all();
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
