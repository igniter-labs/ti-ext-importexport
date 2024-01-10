<?php

namespace IgniterLabs\ImportExport\Http\Actions;

use Igniter\Admin\Facades\Template;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\FlashException;
use Igniter\System\Classes\ControllerAction;
use IgniterLabs\ImportExport\Traits\ImportExportHelper;
use Illuminate\Database\Eloquent\MassAssignmentException;

class ExportController extends ControllerAction
{
    use ImportExportHelper;

    /**
     * @var \Igniter\Admin\Classes\AdminController Reference to the back end controller.
     */
    protected $controller;

    /**
     * @var Model Export model
     */
    public $exportModel;

    /**
     * @var array Export column configuration.
     */
    public $exportColumns;

    /**
     * @var string File name used for export output.
     */
    protected $exportFileName = 'export.csv';

    /**
     * @var \Igniter\Admin\Classes\BaseWidget Reference to the toolbar widget objects.
     */
    protected $exportToolbarWidget;

    /**
     * @var \Igniter\Admin\Widgets\Form Reference to the widget used for standard export options.
     */
    protected $exportPrimaryFormWidget;

    /**
     * @var \Igniter\Admin\Widgets\Form Reference to the widget used for custom export options.
     */
    protected $exportSecondaryFormWidget;

    protected $requiredProperties = ['exportConfig'];

    protected $requiredConfig = ['configFile'];

    /**
     * Behavior constructor
     * @param \Igniter\Admin\Classes\AdminController $controller
     */
    public function __construct($controller)
    {
        $classPath = strtolower(str_replace('\\', '/', get_called_class()));
        $controller->partialPath[] = '$/'.$classPath;

        parent::__construct($controller);

        // Build configuration
        $this->setConfig($controller->exportConfig, $this->requiredConfig);

        // Override config
        if ($exportFileName = $this->getConfig('fileName')) {
            $this->exportFileName = $exportFileName;
        }
    }

    public function export($context, $recordName = null)
    {
        if ($redirect = $this->checkPermissions()) {
            return $redirect;
        }

        $this->loadRecordConfig($context, $recordName);

        $pageTitle = lang($this->getConfig('record[title]', 'igniterlabs.importexport::default.text_export_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initExportForms();
    }

    public function download($context, $recordName = null, $exportName = null, $outputName = null)
    {
        $this->loadRecordConfig('export', $recordName);

        return $this->getExportModel()->download($exportName, $outputName);
    }

    public function renderExport()
    {
        if (!is_null($this->exportToolbarWidget)) {
            $import[] = $this->exportToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('export_container');

        return implode(PHP_EOL, $import);
    }

    public function export_onExport($context, $recordName)
    {
        $partials = [];

        try {
            $this->loadRecordConfig($context, $recordName);

            $model = $this->getExportModel();

            if ($secondaryData = post('ExportSecondary')) {
                $model->fill($secondaryData);
            }

            $columns = $this->processExportColumnsFromPost();
            $options = $this->getFormatOptionsFromPost();

            $reference = $model->export($columns, $options);
            $fileUrl = admin_url('igniterlabs/importexport/import_export/download/'.
                $recordName.'/'.$reference.'/'.$this->exportFileName
            );

            $this->vars['fileUrl'] = $fileUrl;
            $this->vars['returnUrl'] = $this->getRedirectUrl();

            flash()->success(
                'File export process completed! The browser will now redirect to the file download.'
            )->important();

            $partials['@#exportContainer'] = $this->importExportMakePartial('export_result');
        } catch (MassAssignmentException $ex) {
            throw new FlashException($ex->getMessage());
        }

        $partials['#notification'] = $this->makePartial('flash');

        return $partials;
    }

    /**
     * @return \IgniterLabs\ImportExport\Models\ExportModel
     */
    public function getExportModel()
    {
        return $this->getModelForType('export');
    }

    protected function initExportForms()
    {
        $model = $this->getExportModel();

        $this->exportPrimaryFormWidget = $this->makePrimaryFormWidgetForType($model, 'export');

        $this->exportSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($model, 'export');

        if (!$this->exportSecondaryFormWidget && $this->exportPrimaryFormWidget) {
            $stepSection = $this->exportPrimaryFormWidget->getField('step_secondary');
            $stepSection->hidden = true;
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
        if (!is_null($this->exportColumns)) {
            return $this->exportColumns;
        }

        $configFile = $this->getConfig('record[configFile]');
        $columns = $this->makeListColumns($configFile);

        throw_if(empty($columns),
            FlashException::error(lang('igniterlabs.importexport::default.error_empty_export_columns'))
        );

        return $this->exportColumns = $columns;
    }

    //
    //
    //

    /**
     * Called before the form fields are defined.
     *
     * @param \Igniter\Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function exportFormExtendFieldsBefore($host)
    {
    }

    /**
     * Called after the form fields are defined.
     *
     * @param \Igniter\Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function exportFormExtendFields($host, $fields)
    {
    }
}
