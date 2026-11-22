<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Exception;
use Igniter\Cart\Models\Menu;
use Igniter\Cart\Models\MenuItemOption;
use Igniter\Cart\Models\MenuItemOptionValue;
use Igniter\Cart\Models\MenuOption;
use Igniter\Cart\Models\MenuOptionValue;
use Illuminate\Support\Facades\Validator;
use Override;

/**
 * Imports menu items, categories and option groups parsed from another platform's export.
 *
 * @property bool $import_options
 */
class PlatformMenuImport extends MenuImport
{
    protected array $menuOptionCache = [];

    #[Override]
    public function importData(array $results): void
    {
        foreach ($results as $row => $data) {
            try {
                $validated = Validator::validate($data, [
                    'menu_name' => 'required|string|between:2,255',
                    'menu_description' => 'nullable|string|max:1028',
                    'menu_price' => 'nullable|numeric|min:0',
                    'categories' => 'nullable|array',
                    'categories.*' => 'string|between:1,128',
                    'menu_status' => 'nullable|boolean',
                    'option_groups' => 'nullable|array',
                    'option_groups.*.name' => 'required|string|max:255',
                    'option_groups.*.min' => 'required|integer|min:0',
                    'option_groups.*.max' => 'required|integer|min:1',
                    'option_groups.*.values' => 'required|array|min:1',
                    'option_groups.*.values.*.name' => 'required|string|between:2,255',
                    'option_groups.*.values.*.price' => 'required|numeric|min:0',
                ]);

                $menuItem = $this->update_existing ? $this->findDuplicateMenuItem($validated) : null;
                $menuItem ??= new Menu;
                $menuItem->menu_name = $validated['menu_name'];
                $menuItem->menu_description = $validated['menu_description'] ?? null;
                $menuItem->menu_price = $validated['menu_price'] ?? 0;
                $menuItem->menu_status = $validated['menu_status'] ?? true;
                $menuItem->save();

                $menuItem->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();

                if ($categoryIds = $this->resolveCategoryIds($validated['categories'] ?? [])) {
                    $menuItem->categories()->sync($categoryIds, false);
                }

                $this->assignLocations($menuItem);

                if ($this->import_options) {
                    foreach (array_values($validated['option_groups'] ?? []) as $priority => $group) {
                        $this->attachOptionGroup($menuItem, $group, $priority);
                    }
                }
            } catch (Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }

    /**
     * Attach an option group to the menu item, overriding prices that differ from the shared option.
     */
    protected function attachOptionGroup(Menu $menuItem, array $group, int $priority): void
    {
        $menuOption = $this->resolveMenuOption($group);

        /** @var MenuItemOption $menuItemOption */
        $menuItemOption = $menuItem->menu_options()->firstOrNew(['option_id' => $menuOption->getKey()]);
        $menuItemOption->fill([
            'priority' => $priority,
            'is_required' => $group['min'] > 0,
            'min_selected' => $group['min'],
            'max_selected' => $group['max'],
        ])->save();

        $optionValues = MenuOptionValue::query()
            ->where('option_id', $menuOption->getKey())
            ->get()
            ->keyBy(fn(MenuOptionValue $value): string => strtolower((string)$value->name));
        foreach (array_values($group['values']) as $valuePriority => $value) {
            if (!$optionValue = $optionValues->get(strtolower((string)$value['name']))) {
                continue;
            }

            MenuItemOptionValue::query()->updateOrCreate(
                ['menu_option_id' => $menuItemOption->getKey(), 'option_value_id' => $optionValue->getKey()],
                [
                    'priority' => $valuePriority,
                    'override_price' => (float)$optionValue->price === (float)$value['price'] ? null : $value['price'],
                ],
            );
        }
    }

    /**
     * Reuse an existing option with the same name and the same set of value names, otherwise
     * create a new one. Existing options are never modified, since they may be shared by other items.
     */
    protected function resolveMenuOption(array $group): MenuOption
    {
        $valueNames = collect($group['values'])->pluck('name')->map(fn($name): string => strtolower(trim((string)$name)))->sort()->values()->all();
        $signature = strtolower(trim((string)$group['name'])).'|'.implode('|', $valueNames);

        if (isset($this->menuOptionCache[$signature])) {
            return $this->menuOptionCache[$signature];
        }

        $candidateIds = MenuOption::query()->where('option_name', $group['name'])->pluck('option_id');
        $matchingId = MenuOptionValue::query()
            ->whereIn('option_id', $candidateIds)
            ->get()
            ->groupBy('option_id')
            ->search(fn($values): bool => $values
                ->pluck('name')
                ->map(fn($name): string => strtolower(trim((string)$name)))
                ->sort()->values()->all() === $valueNames);

        $menuOption = $matchingId !== false ? MenuOption::query()->find($matchingId) : null;

        if (!$menuOption) {
            $menuOption = new MenuOption;
            $menuOption->option_name = $group['name'];
            $menuOption->display_type = $group['max'] === 1 ? 'radio' : 'checkbox';
            $menuOption->save();

            $menuOption->addOptionValues(array_map(fn(array $value, int $priority): array => [
                'name' => $value['name'],
                'price' => $value['price'],
                'priority' => $priority,
            ], array_values($group['values']), array_keys(array_values($group['values']))));
        }

        return $this->menuOptionCache[$signature] = $menuOption;
    }
}
