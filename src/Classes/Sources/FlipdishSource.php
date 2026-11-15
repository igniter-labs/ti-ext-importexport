<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Override;

/**
 * Flipdish: menu JSON (MenuSections/MenuItems/MenuItemOptionSets), stores JSON and Portal customers CSV.
 */
class FlipdishSource extends ImportSource
{
    protected array $formats = [
        'parseMenu' => 'json',
        'parseCustomers' => 'csv',
        'parseLocations' => 'json',
    ];

    #[Override]
    public function label(): string
    {
        return 'Flipdish';
    }

    #[Override]
    public function parseMenu(string $path): array
    {
        $data = $this->readJson($path);
        $menu = $data['Data'] ?? $data;

        $rows = [];
        foreach ($menu['MenuSections'] ?? [] as $section) {
            $sectionName = trim((string)($section['Name'] ?? ''));
            $sectionActive = ($section['IsAvailable'] ?? true) && !($section['IsHiddenFromUsers'] ?? false);

            foreach ($section['MenuItems'] ?? [] as $item) {
                $price = $this->toPrice($item['Price'] ?? 0);
                $optionGroups = [];

                foreach ($item['MenuItemOptionSets'] ?? [] as $optionSet) {
                    $choices = array_map(fn(array $choice): array => [
                        'name' => (string)($choice['Name'] ?? ''),
                        'price' => $this->toPrice($choice['Price'] ?? 0),
                    ], array_values(array_filter(
                        $optionSet['MenuItemOptionSetItems'] ?? [],
                        fn(array $choice): bool => (bool)($choice['IsAvailable'] ?? true),
                    )));

                    // Master option sets hold the full item price (e.g. sizes) when the item price is zero
                    if (($optionSet['IsMasterOptionSet'] ?? false) && $price == 0 && $choices !== []) {
                        [$price, $group] = $this->makePricedChoiceGroup((string)($optionSet['Name'] ?? ''), $choices);
                    } else {
                        $group = $this->makeOptionGroup(
                            (string)($optionSet['Name'] ?? ''),
                            (int)($optionSet['MinSelectCount'] ?? 0),
                            (int)($optionSet['MaxSelectCount'] ?? 0),
                            $choices,
                        );
                    }

                    $optionGroups[] = $group;
                }

                $rows[] = [
                    'menu_name' => trim((string)($item['Name'] ?? '')),
                    'menu_description' => $this->cleanText($item['Description'] ?? null),
                    'menu_price' => $price,
                    'categories' => $sectionName !== '' ? [$sectionName] : [],
                    'menu_status' => $sectionActive && ($item['IsAvailable'] ?? true) && !($item['IsHiddenFromUsers'] ?? false),
                    'option_groups' => array_values(array_filter($optionGroups)),
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
        $stores = $data['Data'] ?? $data;
        $stores = array_is_list($stores) ? $stores : [$stores];

        return collect($stores)
            ->filter(fn($store): bool => is_array($store) && filled($store['Name'] ?? null))
            ->map(function(array $store): array {
                $address = $store['Address'] ?? [];
                $coordinates = $address['Coordinates'] ?? $store['Coordinates'] ?? [];

                return [
                    'location_name' => trim((string)$store['Name']),
                    'location_email' => $store['EmailAddress'] ?? null,
                    'location_telephone' => $store['PhoneNumber'] ?? null,
                    'location_address_1' => $address['Line1'] ?? $address['DisplayCode'] ?? null,
                    'location_city' => $address['City'] ?? null,
                    'location_postcode' => $address['Postcode'] ?? null,
                    'location_country' => $address['CountryCode'] ?? $address['Country'] ?? null,
                    'location_lat' => $coordinates['Latitude'] ?? null,
                    'location_lng' => $coordinates['Longitude'] ?? null,
                    'location_status' => !($store['IsArchived'] ?? false),
                ];
            })
            ->values()
            ->all();
    }
}
