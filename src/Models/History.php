<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Models;

use Igniter\Flame\Database\Factories\HasFactory;
use Igniter\Flame\Database\Model;
use Igniter\Flame\Support\Facades\File;
use Igniter\User\Models\User;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property string|null $uuid
 * @property string|null $code
 * @property string|null $type
 * @property string|null $status
 * @property Carbon|null $finished_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static History create(array $attributes = [])
 * @mixin Model
 */
class History extends Model
{
    use HasFactory;

    protected $table = 'importexport_history';

    protected $guarded = [];

    public $timestamps = true;

    public $relation = [
        'belongsTo' => [
            'user' => [User::class, 'foreignKey' => 'user_id'],
        ],
    ];

    public $casts = [
        'attempted_data' => 'json',
    ];

    public function markCompleted(array $attributes = [])
    {
        return $this->update(array_merge(['status' => 'completed'], $attributes));
    }

    protected function beforeCreate()
    {
        $this->uuid = (string)Str::uuid();
    }

    protected function beforeDelete()
    {
        $csvPath = $this->getCsvPath();
        if (File::exists($csvPath)) {
            File::delete($csvPath);
        }
    }

    public function getLabelAttribute()
    {
        return resolve(ImportExportManager::class)->getRecordLabel($this->type, $this->code);
    }

    public function getDownloadUrlAttribute(): ?string
    {
        if (!$this->exists || $this->type !== 'export' || $this->status !== 'completed') {
            return null;
        }

        return admin_url(sprintf('igniterlabs/importexport/import_export/download/%s/%s', $this->code, $this->uuid));
    }

    public function getCsvPath(): string
    {
        return temp_path(sprintf('importexport/%s.csv', $this->uuid));
    }

    public function csvExists(): bool
    {
        return File::exists($this->getCsvPath());
    }
}
