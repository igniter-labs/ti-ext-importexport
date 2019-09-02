<?php

namespace Igniter\ImportExport\Actions;

use Admin;
use Admin\Widgets\Toolbar;
use AdminAuth;
use ApplicationException;
use Exception;
use Igniter\Flame\Database\Model;
use Igniter\ImportExport\Classes\ImportExportManager;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Redirect;
use System\Classes\ControllerAction;
use Template;

class ImportExportController extends ControllerAction
{
    /**
     * @var \Admin\Classes\AdminController Reference to the back end controller.
     */
    protected $controller;

    /**
     * @var Model Import model
     */
    public $importModel;

    /**
     * @var array Import column configuration.
     */
    public $importColumns;

    /**
     * @var \Admin\Classes\BaseWidget Reference to the toolbar widget objects.
     */
    protected $importToolbarWidget;

    /**
     * @var \Admin\Widgets\Form Reference to the widget used for uploading import file.
     */
    protected $importPrimaryFormWidget;

    /**
     * @var \Admin\Widgets\Form Reference to the widget used for specifying import options.
     */
    protected $importSecondaryFormWidget;

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
     * @var \Admin\Classes\BaseWidget Reference to the toolbar widget objects.
     */
    protected $exportToolbarWidget;

    /**
     * @var \Admin\Widgets\Form Reference to the widget used for standard export options.
     */
    protected $exportPrimaryFormWidget;

    /**
     * @var \Admin\Widgets\Form Reference to the widget used for custom export options.
     */
    protected $exportSecondaryFormWidget;

    protected $requiredProperties = ['importExportConfig'];

//    protected $requiredConfig = ['model', 'configFile'];
    protected $requiredConfig = [];

    /**
     * Behavior constructor
     * @param \Admin\Classes\AdminController $controller
     */
    public function __construct($controller)
    {
        $classPath = strtolower(str_replace('\\', '/', get_called_class()));
        $controller->partialPath[] = '~/extensions/'.$classPath;

        parent::__construct($controller);

        // Build configuration
        $this->setConfig($controller->importExportConfig, $this->requiredConfig);

        // Override config
        if ($exportFileName = $this->getConfig('export[fileName]'))
            $this->exportFileName = $exportFileName;
    }

    //
    // Controller actions
    //

    public function import($context, $recordName = null)
    {
        if ($redirect = $this->checkPermissionsForType('import'))
            return $redirect;

        $this->loadRecordConfig('import', $recordName);

        $pageTitle = lang($this->getConfig('record[title]', 'igniter.importexport::default.text_import_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initImportForms();
    }

    public function export($context, $recordName = null)
    {
        if ($redirect = $this->checkPermissionsForType('export'))
            return $redirect;

        $this->loadRecordConfig('export', $recordName);

        $pageTitle = lang($this->getConfig('record[title]', 'igniter.importexport::default.text_export_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initExportForms();
    }

    public function download($context, $recordName = null, $exportName = null, $outputName = null)
    {
        $this->loadRecordConfig('export', $recordName);

//        $pageTitle = lang($this->getConfig('record[title]', 'igniter.importexport::default.text_export_title'));
//        Template::setTitle($pageTitle);
//        Template::setHeading($pageTitle);

        return $this->getExportModel()->download($exportName, $outputName);
    }

    //
    // Imports
    //

    public function onImport()
    {
        dd('dd');
    }

    public function onImportUploadFile()
    {
        
        dd('dd');
    }

    public function renderImport()
    {
        if (!is_null($this->importToolbarWidget)) {
            $import[] = $this->importToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('import_container');

        return implode(PHP_EOL, $import);
    }

    public function getImportModel()
    {
        return $this->getModelForType('import');
    }

    protected function initImportForms()
    {
        if (!$this->getConfig('import'))
            return;

        $model = $this->getExportModel();

        $this->importPrimaryFormWidget = $this->makePrimaryFormWidgetForType($model, 'import');

        $this->importSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($model, 'import');

        if (!$this->importSecondaryFormWidget AND $this->importPrimaryFormWidget) {
            $stepSection = $this->importPrimaryFormWidget->getField('step_secondary');
            $stepSection->hidden = TRUE;
        }

        $this->prepareImportVars();
    }

    protected function prepareImportVars()
    {
        $this->vars['recordTitle'] = $this->getConfig('record[title]', 'Unknown Title');
        $this->vars['recordDescription'] = $this->getConfig('record[description]', 'Unknown description');
        $this->vars['importPrimaryFormWidget'] = $this->importPrimaryFormWidget;
        $this->vars['importSecondaryFormWidget'] = $this->importSecondaryFormWidget;
        $this->vars['importColumns'] = $this->getImportColumns();
        $this->vars['importFileColumns'] = $this->getImportFileColumns();

        // Make these variables available to widgets
        $this->controller->vars += $this->vars;
    }

    protected function getImportColumns()
    {
        if (!is_null($this->importColumns))
            return $this->importColumns;

        $configFile = $this->getConfig('record[configFile]');
        $columns = $this->makeListColumns($configFile);

        if (empty($columns))
            throw new ApplicationException(lang('igniter.importexport::default.error_empty_import_columns'));

        return $this->importColumns = $columns;
    }

    protected function getImportFileColumns()
    {
        return;
        if (!$path = $this->getImportFilePath())
            return;

        $reader = $this->createCsvReader($path);
        $firstRow = $reader->fetchOne(0);

        if (!post('first_row_titles')) {
            array_walk($firstRow, function (&$value, $key) {
                $value = 'Column #'.($key + 1);
            });
        }

        if (json_encode($firstRow) === FALSE)
            throw new ApplicationException(lang('igniter.importexport::default.encoding_not_supported'));

        return $firstRow;
    }

    protected function getImportFilePath()
    {
        return $this->getImportModel()
                    ->getImportFilePath($this->importPrimaryFormWidget->getSessionKey());
    }

    //
    // Exports
    //

    public function onExport($context, $recordName)
    {
        $partials = [];

        try {
            $this->loadRecordConfig('export', $recordName);

            $model = $this->getExportModel();

            if ($secondaryData = post('Secondary')) {
                $model->fill($secondaryData);
            }

            $columns = $this->processExportColumnsFromPost();
            $options = $this->getFormatOptionsFromPost();

            $reference = $model->export($columns, $options);
            $fileUrl = admin_url('igniter/importexport/importexport/download/'.
                $recordName.'/'.$reference.'/'.$this->exportFileName
            );

            $this->vars['fileUrl'] = $fileUrl;
            $this->vars['returnUrl'] = $this->getRedirectUrlForType('export');

            flash()->success(
                'File export process completed! The browser will now redirect to the file download.'
            )->important();

            $partials['@#exportContainer'] = $this->importExportMakePartial('export_result');
        }
        catch (MassAssignmentException $ex) {
            $this->controller->handleError(new ApplicationException(lang(
                'igniter.importexport::default.error_mass_assignment', $ex->getMessage()
            )));
        }
        catch (Exception $ex) {
            $this->controller->handleError($ex);
        }

        $partials['#notification'] = $this->makePartial('flash');

        return $partials;
    }

    public function renderExport()
    {
        if (!is_null($this->exportToolbarWidget)) {
            $import[] = $this->exportToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('export_container');

        return implode(PHP_EOL, $import);
    }

    public function getExportModel()
    {
        return $this->getModelForType('export');
    }

    protected function initExportForms()
    {
        if (!$this->getConfig('export'))
            return null;

        $model = $this->getExportModel();

        $this->exportPrimaryFormWidget = $this->makePrimaryFormWidgetForType($model, 'export');

        $this->exportSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($model, 'export');

        if (!$this->exportSecondaryFormWidget AND $this->exportPrimaryFormWidget) {
            $stepSection = $this->exportPrimaryFormWidget->getField('step_secondary');
            $stepSection->hidden = TRUE;
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
        if (!is_null($this->exportColumns))
            return $this->exportColumns;

        $configFile = $this->getConfig('record[configFile]');
        $columns = $this->makeListColumns($configFile);

        if (empty($columns))
            throw new ApplicationException(lang('igniter.importexport::default.error_empty_export_columns'));

        return $this->exportColumns = $columns;
    }

    //
    // ListController
    //

    protected function useListExportMode()
    {
        if (!$useList = $this->getConfig('export[useList]'))
            return FALSE;

        if (!$this->controller->isClassExtendedWith('Admin\Actions\ListController'))
            throw new ApplicationException(lang('igniter.importexport::default.error_missing_use_list'));

        $alias = is_array($useList) ? array_get($useList, 'alias') : $useList;

        $this->outputExportFromList($alias);
    }

    protected function outputExportFromList($alias, array $options = [])
    {
        dd('dddd');
    }

    //
    // Helpers
    //

    public function importExportMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('import_export_'.$partial, $params + $this->vars, FALSE);
        if (!$contents) {
            $contents = $this->makePartial($partial, $params);
        }

        return $contents;
    }

    protected function getModelForType($type)
    {
        if (!is_null($this->{$type.'Model'}))
            return $this->{$type.'Model'};

        $modelClass = $this->getConfig('record[model]');
        if (!$modelClass)
            throw new ApplicationException(sprintf(lang('igniter.importexport::default.error_missing_model'), $type));

        return $this->{$type.'Model'} = new $modelClass;
    }

    protected function makeListColumns($configFile)
    {
        $columns = $this->loadConfig($configFile, [], 'columns');
        if (!is_array($columns))
            return null;

        $result = [];
        foreach ($columns as $attribute => $column) {
            if (is_array($column)) {
                $result[$attribute] = array_get($column, 'label', $attribute);
            }
            else {
                $result[$attribute] = $column ?: $attribute;
            }
        }

        return $result;
    }

    protected function checkPermissionsForType($type)
    {
        $permissions = $this->getConfig($type.'[permissions]');

        if ($permissions AND !AdminAuth::getUser()->hasPermission((array)$permissions)) {
            return Redirect::back(302, [], Admin::url('dashboard'));
        }
    }

    protected function getRedirectUrlForType($type)
    {
        $redirect = $this->getConfig($type.'[redirect]');

        if (!is_null($redirect)) {
            return $redirect ? Admin::url($redirect) : 'javascript:;';
        }

        return $this->controller->refresh();
    }

    protected function makePrimaryFormWidgetForType($model, $type)
    {
        $configFile = $this->getConfig($type.'[configFile]');
        $modelConfig = $this->loadConfig($configFile, ['form'], 'form');
        $widgetConfig = array_except($modelConfig, 'toolbar');
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'PrimaryForm';
        $widgetConfig['cssClass'] = $type.'-primary-form';

        $widget = $this->makeWidget('Admin\Widgets\Form', $widgetConfig);

        $widget->bindEvent('form.extendFieldsBefore', function () use ($type, $widget) {
            $this->controller->{$type.'FormExtendFieldsBefore'}($widget);
        });

        $widget->bindEvent('form.extendFields', function ($fields) use ($type, $widget) {
            $this->controller->{$type.'FormExtendFields'}($widget, $fields);
        });

        $widget->bindEvent('form.beforeRefresh', function ($holder) {
            $holder->data = [];
        });

        $widget->bindToController();

        if (isset($modelConfig['toolbar']) AND isset($this->controller->widgets['toolbar'])) {
            $toolbarWidget = $this->controller->widgets['toolbar'];
            if ($toolbarWidget instanceof Toolbar) {
                $toolbarWidget->addButtons(array_get($modelConfig['toolbar'], 'buttons', []));
            }

            $this->{$type.'ToolbarWidget'} = $toolbarWidget;
        }

        return $widget;
    }

    protected function loadRecordConfig($type, $recordName)
    {
        $config = $this->getConfig();
        $config['record'] = ImportExportManager::instance()->getRecordConfig($type, $recordName);

        $this->setConfig($config);
    }

    protected function makeSecondaryFormWidgetForType($model, $type)
    {
        if (
            (!$configFile = $this->getConfig('record[configFile]')) OR
            (!$widgetConfig = $this->loadConfig($configFile, [], 'fields'))
        ) return null;

        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'SecondaryForm';
        $widgetConfig['arrayName'] = ucfirst($type).'Secondary';
        $widgetConfig['cssClass'] = $type.'-secondary-form';

        $widget = $this->makeWidget('Admin\Widgets\Form', $widgetConfig);

        $widget->bindToController();

        return $widget;
    }

    protected function processExportColumnsFromPost()
    {
        $result = [];
        $definitions = $this->getExportColumns();

        $columns = post('export_columns', []);
        foreach ($columns as $column) {
            $result[$column] = array_get($definitions, $column, '???');
        }

        return $result;
    }

    protected function getFormatOptionsFromPost()
    {
//        $presetMode = post('format_preset');

        $options = [
//            'delimiter' => null,
//            'enclosure' => null,
//            'escape' => null,
//            'encoding' => null
        ];

//        if ($presetMode == 'custom') {
        $options['delimiter'] = post('delimiter');
        $options['enclosure'] = post('enclosure');
        $options['escape'] = post('escape');
        $options['encoding'] = post('encoding');

//        }

        return $options;
    }

    //
    //
    //

    /**
     * Called before the form fields are defined.
     *
     * @param \Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function importFormExtendFieldsBefore($host)
    {
    }

    /**
     * Called before the form fields are defined.
     *
     * @param \Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function exportFormExtendFieldsBefore($host)
    {
    }

    /**
     * Called after the form fields are defined.
     *
     * @param \Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function importFormExtendFields($host, $fields)
    {
    }

    /**
     * Called after the form fields are defined.
     *
     * @param \Admin\Widgets\Form $host The hosting form widget
     *
     * @return void
     */
    public function exportFormExtendFields($host, $fields)
    {
    }
}