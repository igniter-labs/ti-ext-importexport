<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Override;

/**
 * Uber Eats: Menu API JSON (menus/categories/items/modifier_groups) and store JSON.
 * Prices are in the smallest currency unit (cents).
 */
class UberEatsSource extends ImportSource
{
    protected array $formats = [
        'parseMenu' => 'json',
        'parseLocations' => 'json',
    ];

    #[Override]
    public function label(): string
    {
        return 'Uber Eats';
    }

    #[Override]
    public function parseMenu(string $path): array
    {
        $data = $this->readJson($path);

        $items = collect($data['items'] ?? [])->keyBy('id');
        $modifierGroups = collect($data['modifier_groups'] ?? [])->keyBy('id');

        $itemCategories = [];
        foreach ($data['categories'] ?? [] as $category) {
            foreach ($category['entities'] ?? [] as $entity) {
                $itemCategories[(string)($entity['id'] ?? '')][] = $this->translate($category['title'] ?? null);
            }
        }

        $rows = [];
        foreach ($itemCategories as $itemId => $categoryNames) {
            if (!$item = $items->get($itemId)) {
                continue;
            }

            $optionGroups = [];
            foreach ($item['modifier_group_ids']['ids'] ?? [] as $groupId) {
                if ($group = $modifierGroups->get($groupId)) {
                    $optionGroups[] = $this->mapModifierGroup($group, $items->all());
                }
            }

            $rows[] = [
                'menu_name' => $this->translate($item['title'] ?? null),
                'menu_description' => $this->cleanText($this->translate($item['description'] ?? null)),
                'menu_price' => $this->toPrice($item['price_info']['price'] ?? 0, 100),
                'categories' => array_values(array_unique(array_filter($categoryNames))),
                'menu_status' => !$this->isSuspended($item),
                'option_groups' => array_values(array_filter($optionGroups)),
            ];
        }

        return $rows;
    }

    #[Override]
    public function parseLocations(string $path): array
    {
        $data = $this->readJson($path);
        $stores = $data['stores'] ?? (array_is_list($data) ? $data : [$data]);

        return collect($stores)
            ->filter(fn($store): bool => is_array($store) && filled($store['name'] ?? null))
            ->map(fn(array $store): array => [
                'location_name' => trim((string)$store['name']),
                'location_email' => $store['contact_emails'][0] ?? $store['contact']['email'] ?? null,
                'location_telephone' => $store['contact']['phone_number'] ?? $store['phone_number'] ?? null,
                'location_address_1' => $store['location']['address'] ?? null,
                'location_address_2' => $store['location']['address_2'] ?? null,
                'location_city' => $store['location']['city'] ?? null,
                'location_state' => $store['location']['state'] ?? null,
                'location_postcode' => $store['location']['postal_code'] ?? null,
                'location_country' => $store['location']['country'] ?? null,
                'location_lat' => $store['location']['latitude'] ?? null,
                'location_lng' => $store['location']['longitude'] ?? null,
                'location_status' => strtolower((string)($store['status'] ?? 'active')) !== 'inactive',
            ])
            ->values()
            ->all();
    }

    protected function mapModifierGroup(array $group, array $items): ?array
    {
        $quantity = $group['quantity_info']['quantity'] ?? [];

        $values = [];
        foreach ($group['modifier_options'] ?? [] as $option) {
            if ($optionItem = $items[$option['id'] ?? ''] ?? null) {
                $values[] = [
                    'name' => $this->translate($optionItem['title'] ?? null),
                    'price' => $this->toPrice($optionItem['price_info']['price'] ?? 0, 100),
                ];
            }
        }

        return $this->makeOptionGroup(
            $this->translate($group['title'] ?? null),
            (int)($quantity['min_permitted'] ?? 0),
            (int)($quantity['max_permitted'] ?? 0),
            $values,
        );
    }

    protected function isSuspended(array $item): bool
    {
        $suspendUntil = (int)($item['suspension_info']['suspension']['suspend_until'] ?? 0);

        return $suspendUntil > now()->getTimestamp();
    }

    /**
     * Uber Eats text fields are {"translations": {"en_us": "..."}}. English is preferred.
     */
    protected function translate(mixed $text): string
    {
        if (!is_array($text)) {
            return trim((string)$text);
        }

        $translations = $text['translations'] ?? $text;
        foreach (['en_us', 'en_gb', 'en'] as $locale) {
            if (filled($translations[$locale] ?? null)) {
                return trim((string)$translations[$locale]);
            }
        }

        return trim((string)(collect($translations)->first(fn($value): bool => is_string($value) && $value !== '') ?? ''));
    }
}
