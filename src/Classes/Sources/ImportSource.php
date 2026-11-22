<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Exception;
use Igniter\Flame\Exception\FlashException;
use League\Csv\Reader as CsvReader;
use Throwable;

/**
 * Parses another ordering platform's export file into rows the import models understand.
 *
 * Menu rows (consumed by PlatformMenuImport):
 * @phpstan-type OptionValue array{name: string, price: float}
 * @phpstan-type OptionGroup array{name: string, min: int, max: int, values: list<OptionValue>}
 * @phpstan-type MenuRow array{menu_name: string, menu_description: ?string, menu_price: float, categories: list<string>, menu_status: bool, option_groups: list<OptionGroup>}
 *
 * Customer rows (consumed by CustomerImport):
 * @phpstan-type CustomerRow array{email: ?string, first_name: ?string, last_name: ?string, full_name: ?string, telephone: ?string, addresses: list<array<string, ?string>>}
 *
 * Location rows (consumed by LocationImport):
 * @phpstan-type LocationRow array<string, mixed>
 */
abstract class ImportSource
{
    /**
     * Map of supported parse methods to the file extension they expect, e.g. ['parseMenu' => 'json'].
     *
     * @var array<string, string>
     */
    protected array $formats = [];

    abstract public function label(): string;

    public function supports(string $method): bool
    {
        return array_key_exists($method, $this->formats);
    }

    public function format(string $method): ?string
    {
        return $this->formats[$method] ?? null;
    }

    /**
     * @return list<string>
     */
    public function acceptedExtensions(string $method): array
    {
        return match ($this->formats[$method] ?? null) {
            'json' => ['json', 'txt'],
            'csv' => ['csv', 'txt'],
            default => [],
        };
    }

    /**
     * @return list<MenuRow>
     */
    public function parseMenu(string $path): array
    {
        return $this->unsupported('parseMenu');
    }

    /**
     * @return list<CustomerRow>
     */
    public function parseCustomers(string $path): array
    {
        return $this->unsupported('parseCustomers');
    }

    /**
     * @return list<LocationRow>
     */
    public function parseLocations(string $path): array
    {
        return $this->unsupported('parseLocations');
    }

    /**
     * Parses the file with the given method, converting any failure into a friendly error.
     */
    public function parse(string $method, string $path): array
    {
        throw_unless($this->supports($method), new FlashException(sprintf(
            lang('igniterlabs.importexport::default.error_unsupported_source'), $this->label(),
        )));

        try {
            return $this->{$method}($path);
        } catch (Throwable $ex) {
            throw new FlashException(sprintf(
                lang('igniterlabs.importexport::default.error_invalid_source_file'), $this->label(), $ex->getMessage(),
            ), 'danger', 406, $ex instanceof Exception ? $ex : null);
        }
    }

    protected function unsupported(string $method): never
    {
        throw new FlashException(sprintf(lang('igniterlabs.importexport::default.error_unsupported_source'), $this->label()));
    }

    //
    // Helpers
    //

    protected function readJson(string $path): array
    {
        $contents = (string)file_get_contents($path);
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);

        $data = json_decode((string)$contents, true, 512, JSON_THROW_ON_ERROR);

        throw_unless(is_array($data), new FlashException('The file does not contain a JSON object.'));

        return $data;
    }

    /**
     * Reads a CSV file into rows keyed by lower-cased, trimmed header names.
     *
     * @return list<array<string, string>>
     */
    protected function readCsv(string $path): array
    {
        $reader = CsvReader::createFromPath($path, 'r');
        $reader->skipInputBOM();

        $rows = [];
        $headers = null;
        foreach ($reader->getRecords() as $record) {
            if ($headers === null) {
                $headers = array_map(fn($header): string => strtolower(trim((string)$header)), $record);

                continue;
            }

            if (count(array_filter($record, fn($value): bool => trim((string)$value) !== '')) === 0) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad(array_slice($record, 0, count($headers)), count($headers), ''));
        }

        throw_if($headers === null, new FlashException('The CSV file is empty.'));

        return $rows;
    }

    /**
     * Returns the first non-empty value from the row for any of the given keys.
     */
    protected function pick(array $row, string ...$keys): ?string
    {
        foreach ($keys as $key) {
            $value = $row[$key] ?? null;
            if (is_scalar($value) && ($value = trim((string)$value)) !== '') {
                return $value;
            }
        }

        return null;
    }

    protected function toPrice(mixed $value, int $divideBy = 1): float
    {
        if (is_string($value)) {
            $value = preg_replace('/[^\d.\-]/', '', $value);
        }

        return round(max(0, (float)$value / $divideBy), 4);
    }

    protected function cleanText(mixed $value, int $limit = 1028): ?string
    {
        $value = trim(html_entity_decode(strip_tags((string)$value), ENT_QUOTES | ENT_HTML5));
        $value = (string)preg_replace('/\s+/', ' ', $value);

        return $value === '' ? null : mb_substr($value, 0, $limit);
    }

    /**
     * Builds an option group. Unlimited maximums become the number of values,
     * and short value names are prefixed with the group name to satisfy validation.
     *
     * @param list<array{name: string, price: float}> $values
     * @return OptionGroup|null
     */
    protected function makeOptionGroup(string $name, int $min, int $max, array $values): ?array
    {
        $name = trim($name) ?: 'Options';

        $values = collect($values)
            ->filter(fn(array $value): bool => trim($value['name']) !== '')
            ->map(fn(array $value): array => [
                'name' => mb_strlen($valueName = trim($value['name'])) < 2 ? $name.' '.$valueName : $valueName,
                'price' => round(max(0, $value['price']), 4),
            ])
            ->unique('name')
            ->values()
            ->all();

        if ($values === []) {
            return null;
        }

        $max = $max <= 0 ? count($values) : min($max, count($values));
        $min = max(0, min($min, $max));

        return ['name' => mb_substr($name, 0, 255), 'min' => $min, 'max' => $max, 'values' => $values];
    }

    /**
     * Converts a list of full prices (e.g. sizes) into a base price and an option group of price differences.
     *
     * @param list<array{name: string, price: float}> $choices
     * @return array{0: float, 1: OptionGroup|null}
     */
    protected function makePricedChoiceGroup(string $name, array $choices, float $fallbackPrice = 0): array
    {
        if (count($choices) < 2) {
            return [$choices[0]['price'] ?? $fallbackPrice, null];
        }

        $basePrice = min(array_column($choices, 'price'));

        $group = $this->makeOptionGroup($name, 1, 1, array_map(fn(array $choice): array => [
            'name' => $choice['name'],
            'price' => $choice['price'] - $basePrice,
        ], $choices));

        return [$basePrice, $group];
    }

    /**
     * Maps a customer CSV row using common header names across platforms.
     *
     * @return CustomerRow
     */
    protected function mapCustomerCsvRow(array $row): array
    {
        $country = $this->pick($row, 'country code', 'country', 'country / region', 'country/region');
        if ($country && preg_match('/\(([A-Za-z]{2,3})\)\s*$/', $country, $matches)) {
            $country = $matches[1];
        }

        $address = array_filter([
            'address_1' => $this->pick($row, 'address 1', 'address line 1', 'address', 'street', 'street address', 'delivery address'),
            'address_2' => $this->pick($row, 'address 2', 'address line 2'),
            'city' => $this->pick($row, 'city', 'town'),
            'state' => $this->pick($row, 'state', 'region', 'county', 'province'),
            'postcode' => $this->pick($row, 'postcode', 'postal code', 'post code', 'zip', 'zip code', 'zipcode', 'eircode'),
            'country' => $country,
        ]);

        return [
            'email' => $this->pick($row, 'email', 'e-mail', 'email address', 'customer email'),
            'first_name' => $this->pick($row, 'first name', 'firstname', 'first_name'),
            'last_name' => $this->pick($row, 'last name', 'lastname', 'last_name', 'surname'),
            'full_name' => $this->pick($row, 'name', 'full name', 'customer name', 'customer'),
            'telephone' => $this->pick($row, 'phone', 'phone number', 'telephone', 'mobile', 'customer phone'),
            'addresses' => isset($address['address_1']) ? [$address] : [],
        ];
    }
}
