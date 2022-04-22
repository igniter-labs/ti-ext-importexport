<?php

namespace IgniterLabs\ImportExport\Models;

use Igniter\Flame\Database\Model;
use Igniter\Flame\Exception\ApplicationException;
use Igniter\Flame\Support\Facades\File;
use Illuminate\Support\Facades\Response;
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
     * @param $columns
     */
    abstract public function exportData($columns);

    /**
     * Export data based on column names and labels.
     * The $columns array should be in the format of:
     *
     *   [
     *       'db_column_name1' => 'Column label',
     *       'db_column_name2' => 'Another label',
     *       ...
     *   ]
     * @param $columns
     * @param $options
     * @return string
     */
    public function export($columns, $options)
    {
        $data = $this->exportData(array_keys($columns));

        return $this->processExportData($columns, $data, $options);
    }

    /**
     * Download a previously compiled export file.
     * @param $name
     * @param null $outputName
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function download($name, $outputName = null)
    {
        if (!preg_match('/^ti-export-[0-9a-z]*$/i', $name))
            throw new ApplicationException(lang('igniterlabs.importexport::default.error_file_not_found'));

        $csvPath = temp_path().'/'.$name;
        if (!file_exists($csvPath))
            throw new ApplicationException(lang('igniterlabs.importexport::default.error_file_not_found'));

        return Response::download($csvPath, $outputName)->deleteFileAfterSend(TRUE);
    }

    /**
     * Converts a data collection to a CSV file.
     * @param $columns
     * @param $results
     * @param $options
     * @return string
     */
    protected function processExportData($columns, $results, $options)
    {
        if (!$results)
            throw new ApplicationException(lang('igniterlabs.importexport::default.error_empty_data'));

        $columns = $this->exportExtendColumns($columns);

        $csvWriter = $this->prepareCsvWriter($options, $columns, $results);

        $csvName = 'ti-export-'.md5(get_class($this));
        $csvPath = temp_path().'/'.$csvName;
        $output = $csvWriter->__toString();

        File::put($csvPath, $output);

        return $csvName;
    }

    /**
     * Used to override column definitions at export time.
     * @param $columns
     * @return array
     */
    protected function exportExtendColumns($columns)
    {
        return $columns;
    }

    protected function prepareCsvWriter($options, $columns, $results)
    {
        $defaultOptions = [
            'firstRowTitles' => TRUE,
            'useOutput' => FALSE,
            'fileName' => 'export.csv',
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
        ];

        $options = array_merge($defaultOptions, $options);

        $csvWriter = CsvWriter::createFromFileObject(new SplTempFileObject);

        $csvWriter->setOutputBOM(CsvWriter::BOM_UTF8);

        if (!is_null($options['delimiter']))
            $csvWriter->setDelimiter($options['delimiter']);

        if (!is_null($options['enclosure']))
            $csvWriter->setEnclosure($options['enclosure']);

        if (!is_null($options['escape']))
            $csvWriter->setEscape($options['escape']);

        // Insert headers
        if ($options['firstRowTitles'])
            $csvWriter->insertOne($this->getColumnHeaders($columns));

        // Insert records
        foreach ($results as $result) {
            $csvWriter->insertOne($this->processExportRow($columns, $result));
        }

        if ($options['useOutput'])
            $csvWriter->output($options['fileName']);

        return $csvWriter;
    }

    protected function getColumnHeaders($columns)
    {
        $headers = [];
        foreach ($columns as $key => $label) {
            $headers[] = lang($label);
        }

        return $headers;
    }

    protected function processExportRow($columns, $record)
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
     * @param $data
     * @param string $delimiter
     * @return string
     */
    protected function encodeArrayValue($data, $delimiter = '|')
    {
        $newData = [];
        foreach ($data as $value) {
            if (is_array($value)) {
                $newData[] = 'Array';
            }
            else {
                $newData[] = str_replace($delimiter, '\\'.$delimiter, $value);
            }
        }

        return implode($delimiter, $newData);
    }
}
