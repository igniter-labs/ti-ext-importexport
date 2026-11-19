<p align="center">
    <a href="https://github.com/igniter-labs/ti-ext-importexport/actions"><img src="https://github.com/igniter-labs/ti-ext-importexport/actions/workflows/pipeline.yml/badge.svg" alt="Build Status"></a>
    <a href="https://packagist.org/packages/igniterlabs/ti-ext-importexport"><img src="https://img.shields.io/packagist/dt/igniterlabs/ti-ext-importexport" alt="Total Downloads"></a>
    <a href="https://packagist.org/packages/igniterlabs/ti-ext-importexport"><img src="https://img.shields.io/packagist/v/igniterlabs/ti-ext-importexport" alt="Latest Stable Version"></a>
    <a href="https://packagist.org/packages/igniterlabs/ti-ext-importexport"><img src="https://img.shields.io/github/license/igniter-labs/ti-ext-importexport" alt="License"></a>
</p>

## Introduction

This extension allows you to import or export TastyIgniter records, such as menu items, customers, reservations, and orders. You can export records to a CSV file, make changes to the data, and then import the updated records back into TastyIgniter.

## Features

### Import & export

- Import and export TastyIgniter records as CSV files from the admin area (**Tools > Import/Export**).
- Built-in imports and exports for **menu items**, **customers** (with addresses) and **locations**. Exported customer and location files can be imported again without remapping columns.
- Import CSV files with any column headers: map each file column to a TastyIgniter field, with matching headers pre-selected.
- Choose which columns to include on each import or export.
- Update existing records on import (for example, refresh menu items by name or customers by email) or create new ones.
- Assign imported menu items to one or more locations.
- Imports run in the background with live progress on the history page, and an email and admin notification when they finish or fail.

### Migrate from other platforms

- Import from the export files of **GloriaFood**, **WooCommerce**, **Uber Eats**, **Just Eat** and **Flipdish**. No API keys needed.
- Menu items, categories and option groups (sizes, extras, modifiers) with per-item prices.
- Customers and their addresses (GloriaFood, WooCommerce, Flipdish).
- Locations and store details (GloriaFood, Uber Eats, Just Eat, Flipdish).
- Preview what was found in the file before importing.

### Extensibility

- Register custom import and export types from your own extensions via `registerImportExport()`.
- Define column schemas, import options, and permissions for each record type.
- Extend `ImportModel` and `ExportModel` base classes that handle file I/O, CSV parsing, and statistics.

## Documentation

More documentation can be found on [here](https://tastyigniter.com/docs/extensions/importexport).

## Reporting issues

If you encounter a bug in this extension, please report it using the [Issue Tracker](https://github.com/tastyigniter/TastyIgniter/issues) on GitHub.

## Contributing

Contributions are welcome! Please read [TastyIgniter's contributing guide](https://tastyigniter.com/docs/resources/contribution-guide).

## Security vulnerabilities

For reporting security vulnerabilities, please see [our security policy](https://github.com/igniter-labs/ti-ext-importexport/security/policy).

## License

TastyIgniter ImportExport extension is open-source software licensed under the [MIT license](https://github.com/igniter-labs/ti-ext-importexport/blob/master/LICENSE.md).
