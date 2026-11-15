<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Override;

/**
 * GloriaFood: menu JSON (Fetch Menu API format), clients CSV export,
 * and restaurant details from accepted orders JSON (restaurant_* fields).
 */
class GloriaFoodSource extends ImportSource
{
    protected array $formats = [
        'parseMenu' => 'json',
        'parseCustomers' => 'csv',
        'parseLocations' => 'json',
    ];

    #[Override]
    public function label(): string
    {
        return 'GloriaFood';
    }

    #[Override]
    public function parseMenu(string $path): array
    {
        $data = $this->readJson($path);
        $categories = $data['categories'] ?? $data['menu']['categories'] ?? [];

        $rows = [];
        foreach ($categories as $category) {
            $categoryName = trim((string)($category['name'] ?? ''));
            $categoryGroups = $category['groups'] ?? [];

            foreach ($category['items'] ?? [] as $item) {
                $sizes = array_map(fn(array $size): array => [
                    'name' => (string)($size['name'] ?? ''),
                    'price' => $this->toPrice($size['price'] ?? 0),
                ], $item['sizes'] ?? []);

                [$price, $sizeGroup] = $this->makePricedChoiceGroup('Size', $sizes, $this->toPrice($item['price'] ?? 0));

                $groups = array_merge($categoryGroups, $item['groups'] ?? []);
                foreach ($item['sizes'] ?? [] as $size) {
                    $groups = array_merge($groups, $size['groups'] ?? []);
                }

                $optionGroups = array_values(array_filter(array_merge(
                    [$sizeGroup],
                    array_map($this->mapGroup(...), collect($groups)->unique('name')->all()),
                )));

                $rows[] = [
                    'menu_name' => trim((string)($item['name'] ?? '')),
                    'menu_description' => $this->cleanText($item['description'] ?? null),
                    'menu_price' => $price,
                    'categories' => $categoryName !== '' ? [$categoryName] : [],
                    'menu_status' => !($item['hidden'] ?? false) && !($category['hidden'] ?? false),
                    'option_groups' => $optionGroups,
                ];
            }
        }

        return $rows;
    }

    #[Override]
    public function parseCustomers(string $path): array
    {
        return array_map($this->mapCustomerCsvRow(...), $this->readCsv($path));
    }

    #[Override]
    public function parseLocations(string $path): array
    {
        $data = $this->readJson($path);
        $records = $data['orders'] ?? $data['restaurants'] ?? (array_is_list($data) ? $data : [$data]);

        return collect($records)
            ->filter(fn($record): bool => is_array($record) && filled($record['restaurant_name'] ?? null))
            ->unique(fn(array $record): string => (string)($record['restaurant_id'] ?? $record['restaurant_name']))
            ->map(fn(array $record): array => [
                'location_name' => trim((string)$record['restaurant_name']),
                'location_telephone' => $record['restaurant_phone'] ?? null,
                'location_address_1' => $record['restaurant_street'] ?? null,
                'location_city' => $record['restaurant_city'] ?? null,
                'location_state' => $record['restaurant_state'] ?? null,
                'location_postcode' => $record['restaurant_zipcode'] ?? null,
                'location_country' => $record['restaurant_country'] ?? null,
                'location_lat' => $record['restaurant_latitude'] ?? null,
                'location_lng' => $record['restaurant_longitude'] ?? null,
            ])
            ->values()
            ->all();
    }

    protected function mapGroup(array $group): ?array
    {
        $min = (int)($group['force_min'] ?? 0);
        if (($group['required'] ?? false) && $min < 1) {
            $min = 1;
        }

        return $this->makeOptionGroup(
            (string)($group['name'] ?? ''),
            $min,
            (int)($group['force_max'] ?? 0),
            array_map(fn(array $option): array => [
                'name' => (string)($option['name'] ?? ''),
                'price' => $this->toPrice($option['price'] ?? 0),
            ], $group['options'] ?? []),
        );
    }
}
