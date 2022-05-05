<?php

namespace IgniterLabs\ImportExport\Traits;

use Admin\Facades\Admin;
use Admin\Facades\AdminAuth;
use Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\ApplicationException;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use Illuminate\Support\Facades\Redirect;
use League\Csv\Reader as CsvReader;

trait ImportExportHelper
{
    public function importExportMakePartial($partial, $params = [])
    {
        $contents = $this->controller->makePartial('import_export_'.$partial, $params + $this->vars, false);
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
            throw new ApplicationException(sprintf(lang('igniterlabs.importexport::default.error_missing_model'), $type));

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

    protected function checkPermissions()
    {
        $permissions = (array)$this->getConfig('record[permissions]');

        if ($permissions && !AdminAuth::getUser()->hasPermission($permissions)) {
            return Redirect::back(302, [], Admin::url('dashboard'));
        }
    }

    protected function getRedirectUrl()
    {
        $redirect = $this->getConfig('redirect');

        if (!is_null($redirect)) {
            return $redirect ? Admin::url($redirect) : 'javascript:;';
        }

        return $this->controller->refresh();
    }

    protected function makePrimaryFormWidgetForType($model, $type)
    {
        $configFile = $this->getConfig('configFile');
        $modelConfig = $this->loadConfig($configFile, ['form'], 'form');
        $widgetConfig = array_except($modelConfig, 'toolbar');
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'PrimaryForm';
        $widgetConfig['cssClass'] = $type.'-primary-form';

        $widget = $this->makeWidget(\Admin\Widgets\Form::class, $widgetConfig);

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

        if (isset($modelConfig['toolbar']) && isset($this->controller->widgets['toolbar'])) {
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
            (!$configFile = $this->getConfig('record[configFile]')) ||
            (!$fields = $this->loadConfig($configFile, [], 'fields'))
        ) return null;

        $widgetConfig['fields'] = $fields;
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'SecondaryForm';
        $widgetConfig['arrayName'] = ucfirst($type).'Secondary';
        $widgetConfig['cssClass'] = $type.'-secondary-form';

        $widget = $this->makeWidget(\Admin\Widgets\Form::class, $widgetConfig);

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
        $options['delimiter'] = post('delimiter');
        $options['enclosure'] = post('enclosure');
        $options['escape'] = post('escape');
        $options['encoding'] = post('encoding');

        return $options;
    }

    protected function createCsvReader($path)
    {
        $reader = CsvReader::createFromPath($path, 'r');
        $options = $this->getFormatOptionsFromPost();

        if ($options['delimiter'] !== null) {
            $reader->setDelimiter($options['delimiter']);
        }

        if ($options['enclosure'] !== null) {
            $reader->setEnclosure($options['enclosure']);
        }

        if ($options['escape'] !== null) {
            $reader->setEscape($options['escape']);
        }

        if (!is_null($options['encoding']) && $reader->isActiveStreamFilter()) {
            $reader->appendStreamFilter(sprintf(
                '%s%s:%s',
                'igniter.csv.transcode.',
                strtolower($options['encoding']),
                'utf-8'
            ));
        }

        return $reader;
    }

    protected function getImportMatchColumns()
    {
        $matches = post('match_columns', []);
        $columns = array_filter(post('import_columns', []));
        if (!$matches || !$columns)
            throw new ApplicationException('Please select columns to import');

        $result = [];
        foreach ($matches as $index => $column) {
            $result[$index] = array_get($columns, $index, $column);
        }

        return $result;
    }
}
