<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Traits;

use Igniter\Admin\Widgets\Form;
use Igniter\Admin\Widgets\Toolbar;
use Igniter\Flame\Exception\FlashException;
use Igniter\User\Facades\AdminAuth;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Models\ExportModel;
use IgniterLabs\ImportExport\Models\ImportModel;
use Illuminate\Http\RedirectResponse;

trait ImportExportHelper
{
    public function importExportMakePartial($partial, $params = [])
    {
        return $this->controller->makePartial($partial, $params + $this->vars, false);
    }

    protected function getModelForType($type): ImportModel|ExportModel
    {
        if (!$modelClass = $this->getConfig('record[model]')) {
            throw new FlashException(sprintf(lang('igniterlabs.importexport::default.error_missing_model'), $type));
        }

        return new $modelClass;
    }

    protected function makeListColumns($configFile): array
    {
        return $this->loadConfig($configFile, [], 'columns') ?? [];
    }

    protected function checkPermissions(): ?RedirectResponse
    {
        $permissions = (array)$this->getConfig('record[permissions]');

        return $permissions && !AdminAuth::getUser()->hasPermission($permissions)
            ? $this->controller->redirect('igniterlabs/importexport/import_export')
            : null;
    }

    protected function getRedirectUrl()
    {
        $redirect = $this->getConfig('redirect');

        return $redirect ? $this->controller->redirect($redirect) : $this->controller->refresh();
    }

    protected function loadRecordConfig($type, $recordName)
    {
        $config = $this->getConfig();
        $config['record'] = resolve(ImportExportManager::class)->getRecordConfig($type, $recordName);

        $this->setConfig($config);
    }

    protected function makePrimaryFormWidgetForType($model, string $type)
    {
        $configFile = $this->getConfig('configFile');
        $modelConfig = $this->loadConfig($configFile, ['form'], 'form');
        $widgetConfig = array_except($modelConfig, 'toolbar');
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'PrimaryForm';
        $widgetConfig['cssClass'] = $type.'-primary-form';

        $widget = $this->makeWidget(Form::class, $widgetConfig);

        $widget->bindEvent('form.extendFieldsBefore', function() use ($type, $widget): void {
            $this->controller->{$type.'FormExtendFieldsBefore'}($widget);
        });

        $widget->bindEvent('form.extendFields', function($fields) use ($type, $widget): void {
            $this->controller->{$type.'FormExtendFields'}($widget, $fields);
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

    protected function makeSecondaryFormWidgetForType($model, string $type)
    {
        if (
            (!$configFile = $this->getConfig('record[configFile]')) ||
            (!$fields = $this->loadConfig($configFile, [], 'fields'))
        ) {
            return null;
        }

        $widgetConfig['fields'] = $fields;
        $widgetConfig['model'] = $model;
        $widgetConfig['alias'] = $type.'SecondaryForm';
        $widgetConfig['arrayName'] = ucfirst($type).'Secondary';
        $widgetConfig['cssClass'] = $type.'-secondary-form';

        $widget = $this->makeWidget(Form::class, $widgetConfig);

        $widget->bindToController();

        return $widget;
    }
}
