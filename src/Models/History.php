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
 * @property int|null $total_rows
 * @property int $processed_rows
 * @property string|null $error_message
 * @property array|null $attempted_data
 * @property int|null $user_id
 * @property-read User|null $user
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
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'finished_at' => 'datetime',
    ];

    public const string STATUS_QUEUED = 'queued';

    public const string STATUS_PROCESSING = 'processing';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_FAILED = 'failed';

    public function markCompleted(array $attributes = [])
    {
        return $this->update(array_merge(['status' => self::STATUS_COMPLETED], $attributes));
    }

    /**
     * Records the import result summary and marks the import as completed.
     */
    public function markImportCompleted(array $resultStats): bool
    {
        return $this->markCompleted([
            'error_message' => static::buildImportResultMessage($resultStats),
            'finished_at' => now(),
        ]);
    }

    public function markFailed(string $message): bool
    {
        return $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'finished_at' => now(),
        ]);
    }

    public function updateProgress(int $processed, int $total): bool
    {
        return $this->update(['processed_rows' => $processed, 'total_rows' => $total]);
    }

    /**
     * Whether the import is waiting in the queue or currently running.
     */
    public function isInProgress(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING], true);
    }

    public function getProgressPercentAttribute(): ?int
    {
        if (!$this->total_rows) {
            return null;
        }

        return (int)min(100, floor($this->processed_rows / $this->total_rows * 100));
    }

    public static function buildImportResultMessage(array $importResults): string
    {
        $importResults = (object)array_merge(['created' => 0, 'updated' => 0, 'errorCount' => 0, 'errors' => []], $importResults);

        $resultMessage = implode(PHP_EOL, [
            sprintf(lang('igniterlabs.importexport::default.text_import_created'), $importResults->created),
            sprintf(lang('igniterlabs.importexport::default.text_import_updated'), $importResults->updated),
            sprintf(lang('igniterlabs.importexport::default.text_import_errors'), $importResults->errorCount),
        ]);

        if ($importResults->errorCount) {
            $resultMessage .= PHP_EOL;
            $resultMessage .= collect($importResults->errors)->map(fn($message, $row): string => sprintf(lang('igniterlabs.importexport::default.text_import_row'), $row, $message))->implode(PHP_EOL);
        }

        return $resultMessage;
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
