<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\Cart\Models\Category;
use Igniter\Flame\Database\Model;
use Override;

/**
 * MenuExport Model Class
 *
 * @property int $menu_id
 * @property string $menu_name
 * @property string $menu_description
 * @property string $menu_price
 * @property int $minimum_qty
 * @property bool $menu_status
 * @property int $menu_priority
 * @property string|null $created_at
 * @property string|null $updated_at
 * @property-read mixed $menu_categories
 * @mixin Model
 */
class MenuExport extends ExportModel
{
    protected $table = 'menus';

    protected $primaryKey = 'menu_id';

    public $relation = [
        'belongsToMany' => [
            'menu_categories' => [Category::class, 'table' => 'menu_categories', 'foreignKey' => 'menu_id'],
        ],
    ];

    protected $appends = [
        'categories',
    ];

    #[Override]
    public function exportData(array $columns, array $options = []): array
    {
        $query = self::make()->with([
            'menu_categories',
        ]);

        if ($offset = array_get($options, 'offset')) {
            $query->offset($offset);
        }

        if ($limit = array_get($options, 'limit')) {
            $query->limit($limit);
        }

        return $query->get()->toArray();
    }

    public function getCategoriesAttribute(): string
    {
        return $this->encodeArrayValue($this->menu_categories?->pluck('name')->all() ?? []);
    }
}
