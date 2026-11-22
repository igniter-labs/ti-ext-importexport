<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Models;

use IgniterLabs\ImportExport\Models\History;

it('reports import progress', function(): void {
    $history = History::factory()->make(['status' => History::STATUS_PROCESSING, 'total_rows' => 200, 'processed_rows' => 50]);

    expect($history->isInProgress())->toBeTrue()
        ->and($history->progress_percent)->toBe(25)
        ->and(History::factory()->make(['status' => History::STATUS_QUEUED])->progress_percent)->toBeNull()
        ->and(History::factory()->make(['status' => History::STATUS_COMPLETED])->isInProgress())->toBeFalse();
});

it('builds an import result message', function(): void {
    expect(History::buildImportResultMessage(['created' => 2, 'updated' => 1, 'errorCount' => 1, 'errors' => [4 => 'Bad row']]))
        ->toBe(implode(PHP_EOL, ['Created (2)', 'Updated (1)', 'Errors (1)', 'Row 4 - Bad row']))
        ->and(History::buildImportResultMessage([]))->toBe(implode(PHP_EOL, ['Created (0)', 'Updated (0)', 'Errors (0)']));
});
