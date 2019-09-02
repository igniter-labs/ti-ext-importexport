<?php namespace Igniter\ImportExport;

use Admin\Classes\AdminController;
use Admin\Controllers\Menus;
use System\Classes\BaseExtension;

/**
 * ImportExport Extension Information File
 */
class Extension extends BaseExtension
{
    public function register()
    {
//        $this->app->register(\Maatwebsite\Excel\ExcelServiceProvider::class);
//
//        AliasLoader::getInstance()->alias('Excel', \Maatwebsite\Excel\Facades\Excel::class);
    }

    public function boot()
    {
        AdminController::extend(function ($controller) {
            $controller->bindEvent('controller.afterConstructor', function ($controller) {
                if (!$controller instanceof Menus) return;

                $toolbarWidget = $controller->widgets['toolbar'];
                if ($toolbarWidget instanceof \Admin\Widgets\Toolbar) {
                    $toolbarWidget->bindEvent('toolbar.extendButtons', function () use ($toolbarWidget) {
                        $toolbarWidget->addButtons([
                            'export' => [
                                'label' => 'lang:igniter.importexport::default.button_export_records',
                                'class' => 'btn btn-secondary',
                                'href' => 'igniter/importexport/importexport/export/menus',
                            ],
                        ]);
                    });
                }
            });
        });
//        \Event::listen('controller.afterConstructor', function ($controller) {
//            if (!$controller instanceof Menus) return;
//
//            $toolbarWidget = $this->controller->widgets['toolbar'];
//            if ($toolbarWidget instanceof \Admin\Widgets\Toolbar) {
//                $toolbarWidget->addButtons([
//                    'export' => [
//                        'label' => 'lang:igniter.importexport::default.button_export_records',
//                        'class' => 'btn btn-secondary',
//                        'href' => 'igniter/importexport/menus/export',
//                    ],
//                ]);
//            }
//        });
    }

    public function registerPermissions()
    {
        return [
            'Igniter.ImportExport' => [
                'description' => 'Use import/export tool',
                'group' => 'module',
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'tools' => [
                'child' => [
                    'importexport' => [
                        'priority' => 200,
                        'class' => 'importexport',
                        'href' => admin_url('igniter/importexport/importexport'),
                        'title' => 'Import/Export',
                        'permission' => 'Igniter.ImportExport',
                    ],
                ],
            ],
        ];
    }
}
