<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\Flame\Database\Model;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement as CsvStatement;

/**
 * Import Model
 */
abstract class ImportModel extends Model
{
    protected $guarded = [];

    /**
     * @var array Import statistics store.
     */
    protected $resultStats = [
        'updated' => 0,
        'created' => 0,
        'errorCount' => 0,
        'errors' => [],
    ];

    /**
     * Called when data has being imported.
     * The $results array should be in the format of:
     *
     *    [
     *        'db_name1' => 'Some value',
     *        'db_name2' => 'Another value'
     *    ],
     *    [...]
     */
    abstract public function importData(array $results): void;

    /**
     * Import data based on column names matching header indexes in the CSV.
     */
    public function import($columns, $options = [], ?string $importCsvFile = null): void
    {
        $data = $this->processImportData($importCsvFile, $columns, $options);

        $this->importData($data);
    }

    /**
     * Converts column index to database column map to an array containing
     * database column names and values pulled from the CSV file.
     */
    protected function processImportData(string $filePath, $columns, $options): array
    {
        $csvReader = $this->prepareCsvReader($options, $filePath);

        $result = [];
        $csvStatement = new CsvStatement;
        $contents = $csvStatement->process($csvReader);
        foreach ($contents as $row) {
            $result[] = $this->processImportRow($row, $columns);
        }

        return $result;
    }

    protected function prepareCsvReader($options, string $filePath): CsvReader
    {
        $defaultOptions = [
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
        ];

        $options = array_merge($defaultOptions, $options);

        $csvReader = CsvReader::createFromPath($filePath, 'r+');

        if (!is_null($options['delimiter'])) {
            $csvReader->setDelimiter($options['delimiter']);
        }

        if (!is_null($options['enclosure'])) {
            $csvReader->setEnclosure($options['enclosure']);
        }

        if (!is_null($options['escape'])) {
            $csvReader->setEscape($options['escape']);
        }

        $csvReader->setHeaderOffset(0);

        return $csvReader;
    }

    /**
     * Converts a single row of CSV data to the column map.
     */
    protected function processImportRow($rowData, $columns): array
    {
        $newRow = [];

        foreach ($columns as [$dbName, $fileColumn]) {
            $newRow[$dbName] = array_get($rowData, $fileColumn);
        }

        return $newRow;
    }

    protected function decodeArrayValue($value, string $delimiter = '|'): array
    {
        if (!str_contains((string)$value, $delimiter)) {
            return [$value];
        }

        $data = preg_split('~(?<!\\\)'.preg_quote($delimiter, '~').'~', (string)$value);
        $newData = [];

        foreach ($data as $_value) {
            $newData[] = str_replace('\\'.$delimiter, $delimiter, $_value);
        }

        return $newData;
    }

    public function getEncodingOptions(): array
    {
        $options = [
            'utf-8',
            'us-ascii',
            'iso-8859-1',
            'iso-8859-2',
            'iso-8859-3',
            'iso-8859-4',
            'iso-8859-5',
            'iso-8859-6',
            'iso-8859-7',
            'iso-8859-8',
            'iso-8859-0',
            'iso-8859-10',
            'iso-8859-11',
            'iso-8859-13',
            'iso-8859-14',
            'iso-8859-15',
            'Windows-1251',
            'Windows-1252',
        ];

        $translated = array_map(fn($option): string => lang('igniterlabs.importexport::default.encodings.'.str_slug($option, '_')), $options);

        return array_combine($options, $translated);
    }

    //
    // Result logging
    //
    public function getResultStats(): array
    {
        return $this->resultStats;
    }

    protected function logUpdated(): void
    {
        $this->resultStats['updated']++;
    }

    protected function logCreated(): void
    {
        $this->resultStats['created']++;
    }

    protected function logError($rowIndex, $message): void
    {
        $this->resultStats['errorCount']++;
        $this->resultStats['errors'][$rowIndex] = $message;
    }
}
