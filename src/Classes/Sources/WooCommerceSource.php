<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Classes\Sources;

use Override;

/**
 * WooCommerce: native Products CSV export (Products > Export) and
 * Analytics > Customers CSV download.
 */
class WooCommerceSource extends ImportSource
{
    protected array $formats = [
        'parseMenu' => 'csv',
        'parseCustomers' => 'csv',
    ];

    #[Override]
    public function label(): string
    {
        return 'WooCommerce';
    }

    #[Override]
    public function parseMenu(string $path): array
    {
        $products = $this->readCsv($path);

        $variations = [];
        foreach ($products as $product) {
            if (strtolower((string)$this->pick($product, 'type')) === 'variation') {
                $variations[$this->normalizeParentRef((string)$this->pick($product, 'parent'))][] = $product;
            }
        }

        $rows = [];
        foreach ($products as $product) {
            $type = strtolower((string)$this->pick($product, 'type'));
            $types = array_map(trim(...), explode(',', $type ?: 'simple'));
            if (array_intersect($types, ['variation', 'grouped']) !== []) {
                continue;
            }

            $price = $this->toPrice($this->pick($product, 'regular price', 'sale price') ?? 0);
            $optionGroups = [];

            if (in_array('variable', $types, true)) {
                $productVariations = array_merge(
                    $variations['id:'.$this->pick($product, 'id')] ?? [],
                    ($sku = $this->pick($product, 'sku')) ? ($variations['sku:'.strtolower($sku)] ?? []) : [],
                );

                [$price, $optionGroups] = $this->mapVariations($product, $productVariations, $price);
            }

            $rows[] = [
                'menu_name' => (string)$this->pick($product, 'name'),
                'menu_description' => $this->cleanText($this->pick($product, 'short description', 'description')),
                'menu_price' => $price,
                'categories' => $this->parseCategories((string)$this->pick($product, 'categories')),
                'menu_status' => $this->pick($product, 'published') === '1',
                'option_groups' => $optionGroups,
            ];
        }

        return $rows;
    }

    #[Override]
    public function parseCustomers(string $path): array
    {
        return array_map($this->mapCustomerCsvRow(...), $this->readCsv($path));
    }

    protected function normalizeParentRef(string $parent): string
    {
        return str_starts_with($parent, 'id:') ? $parent : 'sku:'.strtolower($parent);
    }

    /**
     * Variations become one required group per attribute. Price differences are applied to the
     * first attribute's values, which is exact for single-attribute products.
     */
    protected function mapVariations(array $product, array $variations, float $fallbackPrice): array
    {
        if ($variations === []) {
            return [$fallbackPrice, []];
        }

        $prices = array_map(fn(array $variation): float => $this->toPrice($this->pick($variation, 'regular price', 'sale price') ?? 0), $variations);
        $basePrice = min($prices);

        $groups = [];
        foreach ($this->attributeNumbers($product) as $position => $number) {
            $name = (string)$this->pick($product, sprintf('attribute %d name', $number));
            $values = [];
            foreach ($variations as $index => $variation) {
                $value = $this->pick($variation, sprintf('attribute %d value(s)', $number));
                if ($value === null) {
                    continue;
                }

                $delta = $position === 0 ? $prices[$index] - $basePrice : 0;
                $values[$value] = min($values[$value] ?? PHP_FLOAT_MAX, $delta);
            }

            $groups[] = $this->makeOptionGroup($name, 1, 1, array_map(
                fn(string $value, float $price): array => ['name' => $value, 'price' => $price],
                array_map(strval(...), array_keys($values)), $values,
            ));
        }

        return [$basePrice, array_values(array_filter($groups))];
    }

    /**
     * @return list<int>
     */
    protected function attributeNumbers(array $product): array
    {
        $numbers = [];
        foreach (array_keys($product) as $header) {
            if (preg_match('/^attribute (\d+) name$/', (string)$header, $matches) && $this->pick($product, $header)) {
                $numbers[] = (int)$matches[1];
            }
        }

        sort($numbers);

        return $numbers;
    }

    /**
     * Categories are comma separated, hierarchies use ">" (e.g. "Food > Pizza, Drinks"). The leaf is used.
     *
     * @return list<string>
     */
    protected function parseCategories(string $value): array
    {
        $categories = preg_split('/(?<!\\\\),/', $value) ?: [];

        return collect($categories)
            ->map(fn(string $category): string => trim(str_replace('\\,', ',', last(explode('>', $category)))))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
