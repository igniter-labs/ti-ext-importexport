---
title: "Import/Export"
section: "extensions"
sortOrder: 999
---

## Installation

You can install the extension via composer using the following command:

```bash
composer require igniterlabs/ti-ext-importexport -W
```

## Getting started

In the admin area, you can import or export records. Navigate to the _Tools > Import/Export_ admin pages.

- To import data, you can select the type of data you want to import, choose a file, and then click the import button. The system will process the file and import the data into the database.
- To export data, you can select the type of data you want to export, and then click the export button. The system will generate a CSV file containing the exported data.
- You can also define custom import/export types, see the [Usage](#usage) section below for more details.

## Usage

This section explains how to integrate the Import/Export extension into your own extension if you need to create custom import/export types. The Import/Export extension provides a simple API for managing import and export operations.

### Defining import types

You can define import types by creating an import definition file and a model class that extends `IgniterLabs\ImportExport\Models\ImportModel`. This class should implement the `importData` method to handle the import logic for inserting/updating data into the database. The base class handles file uploads and data processing.

```php
use IgniterLabs\ImportExport\Models\ImportModel;

class MyCustomImport extends ImportModel
{
    protected $table = 'db_table_name'; // Specify the database table to import data into

    public function importData(array $data): void
    {
        // Process the data and insert/update records in the database
        foreach ($data as $record) {
            try {
                // Validate the record before processing
                $validated = Validator::validate($record, [
                    'id' => 'required|integer',
                    'name' => 'required|string|max:255',
                    'description' => 'nullable|string',
                ]);
            } catch (\Exception $e) {
                // Log an error if validation fails
                $this->logError($record, $e->getMessage());
                continue; // Skip to the next record
            }

            // Example: Insert or update logic
            if ($this->update_existing) {
                $record = Model::where('id', $record['id'])->first();
            }

            $record ??= new Model();

            $record->fill($record)->save();
            $record->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();
        }
    }
}
```

Methods like `logCreated`, `logUpdated`, and `logError` can be used to log changes made during the import process. You can pass the `$rowNumber` and `$errorMessage` parameters to the `logError` method to log errors encountered during the import. Both `logCreated` and `logUpdated` methods do not accept any parameters, as they automatically log the creation or update of records.

Here's an example of an import definition file `customimport.php` that registers the custom import type:

```php
return [
    'columns' => [
        'id' => 'ID',
        'name' => 'Name',
        'description' => 'Description',
    ],
    'fields' => [
        'update_existing' => [
            'label' => 'Update existing items',
            'type' => 'switch',
            'default' => true,
        ],
    ],
];
```

This file should be placed in the `resources/models` directory of your extension. The `columns` array defines the columns that will be available for import, and the `fields` array defines any additional fields that can be configured during the import process.

### Defining export types

You can define export types similarly to import types by creating an export definition file and a model class that extends `IgniterLabs\ImportExport\Models\ExportModel`. This class should implement `exportData` method to handle the export logic for fetching your specific data type from the database. The base class handles CSV file generation and download.

```php

use IgniterLabs\ImportExport\Models\ExportModel;

class MyCustomExport extends ExportModel
{
    protected $table = 'db_table_name'; // Specify the database table to export data from

    public $relation[
        // Define any relationships if needed
    ];

    public function exportData(array $columns, array $options = []): array
    {
        // Define the query to fetch the data to be exported
        $query = $this->newQuery();
        
        if ($offset = array_get($options, 'offset')) {
            $query->offset($offset);
        }

        if ($limit = array_get($options, 'limit')) {
            $query->limit($limit);
        }

        // Fetch the data to be exported
        return $query->get()->toArray(); // Return an array of records to be exported
    }
}
```

The `$columns` parameter specifies which columns to include in the export. The `$options` parameter specifies additional options for the export, such as `offset` or `limit` for pagination.

Here's an example of an export definition file `customexport.php` that registers the custom export type:

```php
return [
    'columns' => [
        'id' => 'ID',
        'name' => 'Name',
        'description' => 'Description',
    ],
];
```

This file should be placed in the `resources/models` directory of your extension. The `columns` array defines the columns that will be available for export.

### Registering import/export types

You can register your custom import and export types in the `registerImportExportTypes` method of your [Extension class](https://tastyigniter.com/docs/extend/extensions#extension-class). Here is an example:

```php
public function registerImportExport(): array
{
    return [
        'import' => [
            'customimport' => [
                'label' => 'My Custom Import',
                'model' => \Author\Extension\Models\MyCustomImport::class,
                'configFile' => 'author.extension::/models/customimport',
                'permissions' => 'Author.Extension.ManageImports',
            ],
        'export' => [
            'customexport' => [
                'label' => 'My Custom Export',
                'model' => \Author\Extension\Models\MyCustomExport::class,
                'configFile' => 'author.extension::/models/customexport',
                'permissions' => 'Author.Extension.ManageExports',
            ],
        ],
    ];
}
```

This method returns an array of import and export types, where each type is defined by its label, model class, configuration file, and permissions required to access it.