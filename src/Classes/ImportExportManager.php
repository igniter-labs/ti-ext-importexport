<?php

namespace IgniterLabs\ImportExport\Classes;

use Igniter\System\Classes\ExtensionManager;

/**
 * ImportExport Manager
 */
class ImportExportManager
{
    protected static $importExportCache;

    public function getRecordConfig($type, $name, $default = null)
    {
        return array_get($this->listImportExportsForType($type), $name, $default);
    }

    public function listImportExportsForType($type)
    {
        return array_get($this->listImportExports(), $type, []);
    }

    //
    // Registration
    //

    /**
     * Returns a list of the registered import/exports.
     * @return array
     */
    public function listImportExports()
    {
        if (self::$importExportCache === null) {
            (new static)->loadImportExports();
        }

        return self::$importExportCache;
    }

    /**
     * Loads registered import/exports from extensions
     * @return void
     */
    public function loadImportExports()
    {
        if (!self::$importExportCache) {
            self::$importExportCache = [];
        }

        $registeredResources = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerImportExport');
        foreach ($registeredResources as $extensionCode => $records) {
            $this->registerImportExports($extensionCode, $records);
        }
    }

    /**
     * Registers the import/exports.
     */
    public function registerImportExports($extensionCode, array $definitions)
    {
        foreach ($definitions as $type => $definition) {
            if (!in_array($type, ['import', 'export'])) {
                continue;
            }

            $this->registerImportExportsForType($type, $extensionCode, $definition);
        }
    }

    public function registerImportExportsForType($type, $extensionCode, array $definitions)
    {
        $defaultDefinitions = [
            'label' => null,
            'description' => null,
            'model' => null,
            'configFile' => null,
        ];

        foreach ($definitions as $name => $definition) {
            $name = str_replace('.', '-', $extensionCode.'.'.$name);

            static::$importExportCache[$type][$name] = array_merge($defaultDefinitions, $definition);
        }
    }
}
