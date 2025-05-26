<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes;

use Igniter\System\Classes\ExtensionManager;

/**
 * ImportExport Manager
 */
class ImportExportManager
{
    protected array $importExportCache = [];

    public function getRecordConfig($type, $name, $default = null)
    {
        return array_get($this->listImportExportsForType($type), $name, $default);
    }

    public function getRecordLabel($type, $name, $default = null)
    {
        return array_get($this->getRecordConfig($type, $name), 'label', $default);
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
     */
    public function listImportExports(): array
    {
        if (!$this->importExportCache) {
            $this->loadImportExports();
        }

        return $this->importExportCache;
    }

    /**
     * Loads registered import/exports from extensions
     */
    public function loadImportExports(): void
    {
        $registeredResources = resolve(ExtensionManager::class)->getRegistrationMethodValues('registerImportExport');
        foreach ($registeredResources as $extensionCode => $records) {
            $this->registerImportExports($extensionCode, $records);
        }
    }

    /**
     * Registers the import/exports.
     */
    public function registerImportExports(string $extensionCode, array $definitions): void
    {
        foreach ($definitions as $type => $definition) {
            if (!in_array($type, ['import', 'export'])) {
                continue;
            }

            $this->registerImportExportsForType($type, $extensionCode, $definition);
        }
    }

    public function registerImportExportsForType($type, string $extensionCode, array $definitions): void
    {
        $defaultDefinitions = [
            'label' => null,
            'description' => null,
            'model' => null,
            'configFile' => null,
        ];

        foreach ($definitions as $name => $definition) {
            $name = str_replace('.', '-', $extensionCode.'.'.$name);

            $this->importExportCache[$type][$name] = array_merge($defaultDefinitions, $definition);
        }
    }
}
