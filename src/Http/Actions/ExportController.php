<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Http\Actions;

use Exception;
use Igniter\Admin\Facades\Template;
use Igniter\Admin\Traits\ValidatesForm;
use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\FlashException;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Classes\ControllerAction;
use IgniterLabs\ImportExport\Http\Controllers\ImportExport;
use IgniterLabs\ImportExport\Models\ExportModel;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Traits\ImportExportHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Throwable;

class ExportController extends ControllerAction
{
    use ImportExportHelper;
    use ValidatesForm;

    public ?ExportModel $exportModel = null;

    /**
     * Export column configuration.
     */
    public ?array $exportColumns = null;

    /**
     * File name used for export output.
     */
    protected string $exportFileName = 'export.csv';

    /**
     * Reference to the toolbar widget objects.
     */
    protected ?Toolbar $exportToolbarWidget = null;

    /**
     * Reference to the widget used for standard export options.
     */
    protected ?Form $exportPrimaryFormWidget = null;

    /**
     * @var Form Reference to the widget used for custom export options.
     */
    protected $exportSecondaryFormWidget;

    protected array $requiredProperties = ['exportConfig'];

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
        $this->setConfig($controller->exportConfig, $this->requiredConfig);
    }

    public function export($context, $recordName = null): ?RedirectResponse
    {
        $this->loadRecordConfig($context, $recordName);

        if (($redirect = $this->checkPermissions()) instanceof RedirectResponse) {
            flash()->error(lang('igniter::admin.alert_user_restricted'));

            return $redirect;
        }

        $pageTitle = lang($this->getConfig('record[title]', 'igniterlabs.importexport::default.text_export_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initExportForms();

        return null;
    }

    public function download($context, $recordName = null, $exportName = null)
    {
        $this->loadRecordConfig('export', $recordName);

        if (($redirect = $this->checkPermissions()) instanceof RedirectResponse) {
            flash()->error(lang('igniter::admin.alert_user_restricted'));

            return $redirect;
        }

        try {
            // Validate the export name
            $this->getExportModel();

            /** @var History|null $history */
            $history = History::query()
                ->where('type', 'export')
                ->where('code', $recordName)
                ->where('uuid', str_before($exportName, '.csv'))
                ->where('status', 'completed')
                ->first();

            throw_unless($history, new FlashException(lang('igniterlabs.importexport::default.error_export_not_found')));

            $csvPath = $history->getCsvPath();

            throw_unless(File::exists($csvPath),
                new FlashException(lang('igniterlabs.importexport::default.error_file_not_found')),
            );

            return Response::download(
                $csvPath,
                sprintf('%s-%s.csv', str_after($recordName, 'importexport-'), date('Y-m-d_H-i-s')),
            );
        } catch (Throwable $ex) {
            flash()->error($ex->getMessage())->important();

            return $this->controller->refresh();
        }
    }

    public function renderExport(): string
    {
        if (!is_null($this->exportToolbarWidget)) {
            $import[] = $this->exportToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('export_container');

        return implode(PHP_EOL, $import);
    }

    public function onLoadExportForm()
    {
        throw_unless(strlen((string)$recordName = post('code')), new FlashException('You must choose a record type to export'));

        $this->loadRecordConfig('export', $recordName);

        throw_unless($this->getConfig('record'), new FlashException($recordName.' is not a registered export template'));

        if (($redirect = $this->checkPermissions()) instanceof RedirectResponse) {
            flash()->error(lang('igniter::admin.alert_user_restricted'));

            return $redirect;
        }

        return $this->controller->redirect('igniterlabs/importexport/import_export/export/'.$recordName);
    }

    public function export_onExport($context, $recordName)
    {
        $this->loadRecordConfig($context, $recordName);

        throw_if($this->checkPermissions(), new FlashException(lang('igniter::admin.alert_user_restricted')));

        $validated = request()->validate([
            'offset' => ['nullable', 'integer'],
            'limit' => ['nullable', 'integer'],
            'delimiter' => ['string'],
            'enclosure' => ['string'],
            'escape' => ['string'],
            'ExportSecondary' => ['nullable', 'array'],
            'export_columns' => ['required', 'array'],
            'export_columns.*' => ['required', 'string'],
        ]);

        /** @var History $history */
        $history = History::create([
            'user_id' => $this->controller->getUser()->getKey(),
            'type' => $context,
            'code' => $recordName,
            'status' => 'pending',
            'attempted_data' => $validated,
        ]);

        try {
            $exportModel = $this->getExportModel();

            if ($secondaryData = array_get($validated, 'ExportSecondary')) {
                $exportModel->fill($secondaryData);
            }

            $exportColumns = $this->processExportColumnsFromRequest($validated);
            $exportOptions = array_except($validated, ['ExportSecondary', 'export_columns', 'visible_columns']);

            $csvWriter = $exportModel->export($exportColumns, $exportOptions);

            $csvPath = $history->getCsvPath();
            if (!File::exists($directory = dirname((string)$csvPath))) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($csvPath, $csvWriter->toString());

            $history->markCompleted();

            flash()->success(lang('igniterlabs.importexport::default.alert_export_success'))->important();

            return $this->getRedirectUrl();
        } catch (Exception $ex) {
            $history->delete();

            throw $ex;
        }
    }

    public function getExportModel(): ExportModel
    {
        return $this->exportModel ??= new ($this->getModelForType('export'));
    }

    protected function initExportForms()
    {
        $exportModel = $this->getExportModel();

        $this->exportPrimaryFormWidget = $this->makePrimaryFormWidgetForType($exportModel, 'export');

        $this->exportSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($exportModel, 'export');

        $stepSectionField = $this->exportPrimaryFormWidget->getField('step_secondary');
        if (!$this->exportSecondaryFormWidget && $stepSectionField) {
            $stepSectionField->hidden = true;
        }

        $this->prepareExportVars();
    }

    protected function prepareExportVars()
    {
        $this->vars['recordTitle'] = $this->getConfig('record[title]', 'Unknown Title');
        $this->vars['recordDescription'] = $this->getConfig('record[description]', 'Unknown description');
        $this->vars['exportPrimaryFormWidget'] = $this->exportPrimaryFormWidget;
        $this->vars['exportSecondaryFormWidget'] = $this->exportSecondaryFormWidget;
        $this->vars['exportColumns'] = $this->getExportColumns();

        // Make these variables available to widgets
        $this->controller->vars += $this->vars;
    }

    protected function getExportColumns()
    {
        if (is_null($this->exportColumns)) {
            $configFile = $this->getConfig('record[configFile]');
            $columns = $this->makeListColumns($configFile);

            throw_unless($columns,
                new FlashException(lang('igniterlabs.importexport::default.error_empty_export_columns')),
            );

            $this->exportColumns = collect($columns)->map(fn($label): string => lang($label))->all();
        }

        return $this->exportColumns;
    }

    protected function processExportColumnsFromRequest(array $request): array
    {
        $definitions = $this->getExportColumns();

        return collect(array_get($request, 'export_columns', []))
            ->mapWithKeys(fn($exportColumn) => [$exportColumn => array_get($definitions, $exportColumn, '???')])
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
    public function exportFormExtendFieldsBefore($host) {}

    /**
     * Called after the form fields are defined.
     *
     * @param Form $host The hosting form widget
     *
     * @return void
     */
    public function exportFormExtendFields($host, $fields) {}
}
