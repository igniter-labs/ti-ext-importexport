<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Exception;
use Igniter\Cart\Models\Category;
use Igniter\Cart\Models\Menu;
use Igniter\Flame\Database\Model;
use Illuminate\Support\Facades\Validator;
use Override;

/**
 * MenuImport Model Class
 *
 * @property int $menu_id
 * @property string $menu_name
 * @property string $menu_description
 * @property string $menu_price
 * @property int $minimum_qty
 * @property bool $menu_status
 * @property int $menu_priority
 * @property bool $update_existing
 * @property string|null $created_at
 * @property string|null $updated_at
 * @mixin Model
 */
class MenuImport extends ImportModel
{
    protected $table = 'menus';

    protected $primaryKey = 'menu_id';

    protected $categoryNameCache = [];

    #[Override]
    public function importData(array $results): void
    {
        foreach ($results as $row => $data) {
            try {
                $validated = Validator::validate($data, [
                    'menu_id' => 'nullable|integer',
                    'menu_name' => 'required|string|between:2,255',
                    'menu_description' => 'nullable|string|between:2,1028',
                    'menu_price' => 'nullable|numeric|min:0',
                    'categories' => 'nullable|string|between:2,1028',
                    'minimum_qty' => 'nullable|integer|min:1',
                    'menu_status' => 'boolean',
                ]);

                $menuItem = new Menu;
                if ($this->update_existing) {
                    $menuItem = $this->findDuplicateMenuItem($validated) ?: $menuItem;
                }

                $except = ['menu_id', 'categories'];
                foreach (array_except($validated, $except) as $attribute => $value) {
                    $menuItem->{$attribute} = $value ?: null;
                }

                $menuItem->save();
                $menuItem->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();

                $encodedCategoryNames = array_get($validated, 'categories');
                if ($encodedCategoryNames && ($categoryIds = $this->getCategoryIdsForMenuItem($encodedCategoryNames))) {
                    $menuItem->categories()->sync($categoryIds, false);
                }
            } catch (Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }

    protected function findDuplicateMenuItem($data)
    {
        if ($id = array_get($data, 'menu_id')) {
            return Menu::find($id);
        }

        return Menu::query()->firstWhere('menu_name', array_get($data, 'menu_name'));
    }

    protected function getCategoryIdsForMenuItem($encodedCategoryNames): array
    {
        $ids = [];
        $categoryNames = $this->decodeArrayValue($encodedCategoryNames);
        foreach ($categoryNames as $name) {
            if (strlen($name = trim((string)$name)) < 1) {
                continue;
            }

            if (isset($this->categoryNameCache[$name])) {
                $ids[] = $this->categoryNameCache[$name];
            } else {
                /** @var Category $category */
                $category = Category::query()->firstOrCreate(['name' => $name]);
                $category->wasRecentlyCreated ? $this->logCreated() : $this->logUpdated();

                $ids[] = $this->categoryNameCache[$name] = $category->category_id;
            }
        }

        return $ids;
    }
}
