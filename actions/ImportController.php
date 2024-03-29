<?php

namespace IgniterLabs\ImportExport\Actions;

use Admin\Facades\Template;
use Exception;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ApplicationException;
use IgniterLabs\ImportExport\Traits\ImportExportHelper;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\File;
use System\Classes\ControllerAction;

class ImportController extends ControllerAction
{
    use ImportExportHelper;

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

    protected $requiredProperties = ['importConfig'];

    protected $requiredConfig = ['configFile'];

    /**
     * Behavior constructor
     * @param \Admin\Classes\AdminController $controller
     */
    public function __construct($controller)
    {
        $classPath = strtolower(str_replace('\\', '/', get_called_class()));
        $controller->partialPath[] = '$/'.$classPath;

        parent::__construct($controller);

        // Build configuration
        $this->setConfig($controller->importConfig, $this->requiredConfig);
    }

    public function import($context, $recordName = null)
    {
        if ($redirect = $this->checkPermissions())
            return $redirect;

        $this->loadRecordConfig($context, $recordName);

        $pageTitle = lang($this->getConfig('record[title]', 'igniterlabs.importexport::default.text_import_title'));
        Template::setTitle($pageTitle);
        Template::setHeading($pageTitle);

        $this->initImportForms();
    }

    public function renderImport()
    {
        if (!is_null($this->importToolbarWidget)) {
            $import[] = $this->importToolbarWidget->render();
        }

        $import[] = $this->importExportMakePartial('import_container');

        return implode(PHP_EOL, $import);
    }

    public function import_onImport($context, $recordName)
    {
        $partials = [];

        try {
            $this->loadRecordConfig($context, $recordName);
            $model = $this->getImportModel();

            $matches = $this->getImportMatchColumns();

            if ($optionData = post('ImportSecondary')) {
                $model->fill($optionData);
            }

            $importOptions = $this->getFormatOptionsFromPost();

            $model->import($matches, $importOptions);

            File::delete($this->getImportFilePath());

            $this->vars['importResults'] = $model->getResultStats();
            $this->vars['returnUrl'] = $this->getRedirectUrl();

            $partials['#importContainer'] = $this->importExportMakePartial('import_result');
        }
        catch (MassAssignmentException $ex) {
            $this->controller->handleError(new ApplicationException(lang(
                'admin::lang.form.mass_assignment_failed',
                ['attribute' => $ex->getMessage()]
            )));
        }
        catch (Exception $ex) {
            $this->controller->handleError($ex);
        }

        return $partials;
    }

    public function import_onImportUploadFile($context, $recordName)
    {
        try {
            if (!request()->hasFile('import_file'))
                throw new ApplicationException('You must upload a file to import');

            $uploadedFile = request()->file('import_file');
            if (!$uploadedFile->isValid())
                throw new ApplicationException($uploadedFile->getErrorMessage());

            if (!in_array($uploadedFile->getMimeType(), ['csv', 'text/csv', 'text/plain']))
                throw new ApplicationException('you must upload a valida csv file');

            $this->loadRecordConfig($context, $recordName);

            File::put(
                $this->getImportFilePath(),
                File::get($uploadedFile->getRealPath())
            );
        }
        catch (Exception $ex) {
            flash()->error($ex->getMessage());
        }

        return $this->controller->redirectBack();
    }

    /**
     * @return \IgniterLabs\ImportExport\Models\ImportModel
     */
    public function getImportModel()
    {
        return $this->getModelForType('import');
    }

    protected function initImportForms()
    {
        $model = $this->getImportModel();

        $this->importPrimaryFormWidget = $this->makePrimaryFormWidgetForType($model, 'import');

        $this->importSecondaryFormWidget = $this->makeSecondaryFormWidgetForType($model, 'import');

        if (!$this->importSecondaryFormWidget && $this->importPrimaryFormWidget) {
            $stepSection = $this->importPrimaryFormWidget->getField('step_secondary');
            $stepSection->hidden = true;
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
            throw new ApplicationException(lang('igniterlabs.importexport::default.error_empty_import_columns'));

        return $this->importColumns = $columns;
    }

    protected function getImportFileColumns()
    {
        if (!$this->importFilePathExists())
            return;

        $path = $this->getImportFilePath();
        $reader = $this->createCsvReader($path);
        $firstRow = $reader->fetchOne(0);

        if (json_encode($firstRow) === false)
            throw new ApplicationException(lang('igniterlabs.importexport::default.encoding_not_supported'));

        return $firstRow;
    }

    protected function getImportFilePath()
    {
        return $this->getImportModel()->getImportFilePath();
    }

    protected function importFilePathExists()
    {
        return File::exists($path = $this->getImportFilePath())
            ? $path : null;
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
     * Called after the form fields are defined.
     *
     * @param \Admin\Widgets\Form $host The hosting form widget
     * @param $fields
     *
     * @return void
     */
    public function importFormExtendFields($host, $fields)
    {
    }
}
