<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Jobs;

use Igniter\Flame\Support\Facades\File;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Classes\Sources\ImportSource;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Models\ImportModel;
use IgniterLabs\ImportExport\Notifications\ImportFinishedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Runs a prepared import in the background, recording progress on the history record
 * and notifying the admin who started it when it completes or fails.
 *
 * The history record's attempted_data holds what the import needs:
 *  - options: values from the import options form (ImportSecondary)
 *  - columns: [[db column, file column], ...] for CSV imports
 *  - csv: delimiter/enclosure/escape for CSV imports
 */
class ProcessImportJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Imports are not retried, a retry could create duplicate records.
     */
    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $historyId) {}

    public function handle(ImportExportManager $manager): void
    {
        /** @var History|null $history */
        $history = History::query()->find($this->historyId);
        if (!$history || $history->status !== History::STATUS_QUEUED) {
            return;
        }

        $config = $manager->getRecordConfig('import', $history->code);
        throw_unless($config && ($modelClass = $config['model'] ?? null), new RuntimeException(sprintf(
            lang('igniterlabs.importexport::default.error_missing_model'), $history->code,
        )));

        $history->update(['status' => History::STATUS_PROCESSING, 'processed_rows' => 0]);

        /** @var ImportModel $importModel */
        $importModel = new $modelClass;
        $importModel->fill((array)array_get($history->attempted_data, 'options', []));
        $importModel->onProgress(fn(int $processed, int $total): bool => $history->updateProgress($processed, $total));

        if ($sourceClass = $config['source'] ?? null) {
            /** @var ImportSource $source */
            $source = resolve($sourceClass);
            $importModel->importFromSource($source, $config['sourceMethod'] ?? 'parseMenu', $history->getCsvPath());
        } else {
            $importModel->import(
                (array)array_get($history->attempted_data, 'columns', []),
                (array)array_get($history->attempted_data, 'csv', []),
                $history->getCsvPath(),
            );
        }

        $history->markImportCompleted($importModel->getResultStats());

        File::delete($history->getCsvPath());

        ImportFinishedNotification::make()->subject($history)->broadcast();
    }

    public function failed(Throwable $exception): void
    {
        /** @var History|null $history */
        $history = History::query()->find($this->historyId);
        if (!$history) {
            return;
        }

        $history->markFailed(sprintf(lang('igniterlabs.importexport::default.text_import_failed'), $exception->getMessage()));

        File::delete($history->getCsvPath());

        ImportFinishedNotification::make()->subject($history)->broadcast();
    }
}
