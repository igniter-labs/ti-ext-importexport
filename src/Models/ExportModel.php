<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\Flame\Database\Model;
use League\Csv\Writer as CsvWriter;
use SplTempFileObject;

/**
 * Export Model
 */
abstract class ExportModel extends Model
{
    /**
     * Called when data is being exported.
     * The return value should be an array in the format of:
     *
     *   [
     *       'db_column_name1' => 'Column label',
     *       'db_column_name2' => 'Another label',
     *   ],
     *   [...]
     */
    abstract public function exportData(array $columns, array $options = []): array;

    /**
     * Export data based on column names and labels.
     * The $columns array should be in the format of:
     */
    public function export($columns, $options): CsvWriter
    {
        $data = $this->exportData(array_keys($columns), $options);

        return $this->processExportData($columns, $data, $options);
    }

    /**
     * Converts a data collection to a CSV file.
     */
    protected function processExportData($columns, $results, $options): CsvWriter
    {
        $columns = $this->exportExtendColumns($columns);

        return $this->prepareCsvWriter($options, $columns, $results);
    }

    /**
     * Used to override column definitions at export time.
     * @return array
     */
    protected function exportExtendColumns($columns)
    {
        return $columns;
    }

    protected function prepareCsvWriter($options, $columns, $results): CsvWriter
    {
        $options = array_merge([
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
        ], $options);

        $csvWriter = CsvWriter::createFromFileObject(new SplTempFileObject);

        $csvWriter->setOutputBOM(CsvWriter::BOM_UTF8);

        if (!is_null($options['delimiter'])) {
            $csvWriter->setDelimiter($options['delimiter']);
        }

        if (!is_null($options['enclosure'])) {
            $csvWriter->setEnclosure($options['enclosure']);
        }

        if (!is_null($options['escape'])) {
            $csvWriter->setEscape($options['escape']);
        }

        // Insert headers
        $csvWriter->insertOne($columns);

        // Insert records
        foreach ($results as $result) {
            $csvWriter->insertOne($this->processExportRow($columns, $result));
        }

        return $csvWriter;
    }

    protected function processExportRow($columns, $record): array
    {
        $results = [];
        foreach ($columns as $column => $label) {
            $results[] = array_get($record, $column);
        }

        return $results;
    }

    /**
     * Implodes a single dimension array using pipes (|)
     * Multi dimensional arrays are not allowed.
     */
    protected function encodeArrayValue($data, string $delimiter = '|'): string
    {
        $newData = [];
        foreach ($data as $value) {
            $newData[] = is_array($value) ? 'Array' : str_replace($delimiter, '\\'.$delimiter, $value);
        }

        return implode($delimiter, $newData);
    }
}
