<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Override;

/**
 * Just Eat: menu JSON (categories/items/variations/modifierGroups/modifiers) and restaurant JSON.
 */
class JustEatSource extends ImportSource
{
    protected array $formats = [
        'parseMenu' => 'json',
        'parseLocations' => 'json',
    ];

    #[Override]
    public function label(): string
    {
        return 'Just Eat';
    }

    #[Override]
    public function parseMenu(string $path): array
    {
        $data = $this->readJson($path);

        $items = collect($data['items'] ?? [])->keyBy('id');
        $modifierGroups = collect($data['modifierGroups'] ?? [])->keyBy('id');
        $modifiers = collect($data['modifiers'] ?? [])->keyBy('id');

        $categories = $data['categories'] ?? [];
        foreach ($data['menus'] ?? [] as $menu) {
            $categories = array_merge($categories, $menu['categories'] ?? []);
        }

        $itemCategories = [];
        foreach ($categories as $category) {
            foreach ($category['itemIds'] ?? [] as $itemId) {
                $itemCategories[(string)$itemId][] = trim((string)($category['name'] ?? ''));
            }
        }

        $rows = [];
        foreach ($itemCategories as $itemId => $categoryNames) {
            if (!$item = $items->get($itemId)) {
                continue;
            }

            $variations = $item['variations'] ?? [];
            [$price, $variationGroup] = $this->makePricedChoiceGroup(
                'Size',
                array_map(fn(array $variation): array => [
                    'name' => (string)($variation['name'] ?? ''),
                    'price' => $this->toPrice($variation['basePrice'] ?? 0),
                ], $variations),
                $this->toPrice($item['basePrice'] ?? $item['price'] ?? 0),
            );

            $groupIds = collect($variations)->pluck('modifierGroupsIds')->flatten()->merge($item['modifierGroupsIds'] ?? [])->unique();
            $optionGroups = $groupIds
                ->map(fn($groupId): ?array => ($group = $modifierGroups->get($groupId)) ? $this->mapModifierGroup($group, $modifiers->all()) : null)
                ->prepend($variationGroup)
                ->filter()
                ->values()
                ->all();

            $rows[] = [
                'menu_name' => trim((string)($item['name'] ?? '')),
                'menu_description' => $this->cleanText($item['description'] ?? null),
                'menu_price' => $price,
                'categories' => array_values(array_unique(array_filter($categoryNames))),
                'menu_status' => (bool)($item['isAvailable'] ?? true),
                'option_groups' => $optionGroups,
            ];
        }

        return $rows;
    }

    #[Override]
    public function parseLocations(string $path): array
    {
        $data = $this->readJson($path);
        $restaurants = $data['restaurants'] ?? (array_is_list($data) ? $data : [$data]);

        return collect($restaurants)
            ->filter(fn($restaurant): bool => is_array($restaurant) && filled($restaurant['name'] ?? null))
            ->map(function(array $restaurant): array {
                $address = $restaurant['address'] ?? [];
                $lines = $address['lines'] ?? array_filter([$address['firstLine'] ?? null]);
                $coordinates = $address['location']['coordinates'] ?? $restaurant['location']['coordinates'] ?? null;

                return [
                    'location_name' => trim((string)$restaurant['name']),
                    'location_email' => $restaurant['email'] ?? null,
                    'location_telephone' => $restaurant['phoneNumber'] ?? null,
                    'description' => $this->cleanText($restaurant['description'] ?? null),
                    'location_address_1' => $lines[0] ?? null,
                    'location_address_2' => $lines[1] ?? null,
                    'location_city' => $address['city'] ?? null,
                    'location_postcode' => $address['postalCode'] ?? $address['postcode'] ?? null,
                    'location_country' => $address['country'] ?? $restaurant['country'] ?? null,
                    // GeoJSON order is [longitude, latitude]
                    'location_lat' => $coordinates[1] ?? $address['latitude'] ?? null,
                    'location_lng' => $coordinates[0] ?? $address['longitude'] ?? null,
                ];
            })
            ->values()
            ->all();
    }

    protected function mapModifierGroup(array $group, array $modifiers): ?array
    {
        $values = [];
        foreach ($group['modifiers'] ?? [] as $modifier) {
            $modifier = is_array($modifier) ? ($modifiers[$modifier['id'] ?? ''] ?? $modifier) : ($modifiers[$modifier] ?? null);
            if ($modifier) {
                $values[] = [
                    'name' => (string)($modifier['name'] ?? ''),
                    'price' => $this->toPrice($modifier['additionPrice'] ?? $modifier['price'] ?? 0),
                ];
            }
        }

        return $this->makeOptionGroup(
            (string)($group['name'] ?? ''),
            (int)($group['minChoices'] ?? 0),
            (int)($group['maxChoices'] ?? 0),
            $values,
        );
    }
}
