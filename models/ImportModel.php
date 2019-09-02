<?php

namespace Igniter\ImportExport\Models;

use Igniter\Flame\Database\Attach\HasMedia;
use League\Csv\Reader as CsvReader;
use Model;

/**
 * Import Model
 */
abstract class ImportModel extends Model
{
    use HasMedia;

    protected $guarded = [];

    public $mediable = ['import_file'];

    /**
     * Called when data has being imported.
     * The $results array should be in the format of:
     *
     *    [
     *        'db_name1' => 'Some value',
     *        'db_name2' => 'Another value'
     *    ],
     *    [...]
     * @param $results
     */
    abstract public function importData($results);

    /**
     * Import data based on column names matching header indexes in the CSV.
     * The $matches array should be in the format of:
     *
     *    [
     *        0 => [db_column_name1, db_column_name2],
     *        1 => [db_column_name3],
     *        ...
     *    ]
     *
     * The key (0, 1) is the column index in the CSV and the value
     * is another array of target database column names.
     * @param $matches
     * @param array $options
     * @return
     */
    public function import($matches, $options = [])
    {
        $path = $this->getImportFilePath();
        $data = $this->processImportData($path, $matches, $options);

        return $this->importData($data);
    }

    public function getImportFilePath()
    {
        $file = $this->import_file()->orderBy('id', 'desc')->first();

        if (!$file)
            return null;

        return $file->getLocalPath();
    }

    /**
     * Converts column index to database column map to an array containing
     * database column names and values pulled from the CSV file. Eg:
     *
     *   [0 => [first_name], 1 => [last_name]]
     *
     * Will return:
     *
     *   [first_name => Chef, last_name => Sam],
     *   [first_name => John, last_name => Doe],
     *   [...]
     *
     * @param $filePath
     * @param $matches
     * @param $options
     * @return array
     */
    protected function processImportData($filePath, $matches, $options)
    {
        $csvReader = $this->prepareCsvReader($options, $filePath, $matches);

        $result = [];
        $contents = $csvReader->fetch();
        foreach ($contents as $row) {
            $result[] = $this->processImportRow($row, $matches);
        }

        return $result;
    }

    protected function prepareCsvReader($options, $filePath)
    {
        $defaultOptions = [
            'firstRowTitles' => TRUE,
            'delimiter' => null,
            'enclosure' => null,
            'escape' => null,
            'encoding' => null,
        ];

        $options = array_merge($defaultOptions, $options);

        $csvReader = CsvReader::createFromPath($filePath, 'r');

        // Filter out empty rows
        $csvReader->addFilter(function (array $row) {
            return count($row) > 1 || reset($row) !== null;
        });

        if (!is_null($options['delimiter']))
            $csvReader->setDelimiter($options['delimiter']);

        if (!is_null($options['enclosure']))
            $csvReader->setEnclosure($options['enclosure']);

        if (!is_null($options['escape']))
            $csvReader->setEscape($options['escape']);

        if ($options['firstRowTitles'])
            $csvReader->setOffset(1);

        if (!is_null($options['encoding']) AND $csvReader->isActiveStreamFilter()) {
            $csvReader->appendStreamFilter(sprintf(
                '%s%s:%s',
                TranscodeFilter::FILTER_NAME,
                strtolower($options['encoding']),
                'utf-8'
            ));
        }

        return $csvReader;
    }

    /**
     * Converts a single row of CSV data to the column map.
     * @param $rowData
     * @param $matches
     * @return array
     */
    protected function processImportRow($rowData, $matches)
    {
        $newRow = [];

        foreach ($matches as $columnIndex => $dbNames) {
            $value = array_get($rowData, $columnIndex);
            foreach ((array)$dbNames as $dbName) {
                $newRow[$dbName] = $value;
            }
        }

        return $newRow;
    }

    public function getEncodingOptions()
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

        $translated = array_map(function ($option) {
            return lang('igniter.importexport::default.encodings.'.str_slug($option, '_'));
        }, $options);

        return array_combine($options, $translated);
    }
}
