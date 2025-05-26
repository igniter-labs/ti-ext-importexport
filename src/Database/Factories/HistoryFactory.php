<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Database\Factories;

use Igniter\Flame\Database\Factories\Factory;
use IgniterLabs\ImportExport\Models\History;
use Override;

class HistoryFactory extends Factory
{
    protected $model = History::class;

    #[Override]
    public function definition(): array
    {
        return [
            'user_id' => 1,
            'uuid' => $this->faker->uuid(),
            'type' => $this->faker->randomElement(['import', 'export']),
            'code' => $this->faker->slug(3),
            'status' => 'pending',
        ];
    }
}
