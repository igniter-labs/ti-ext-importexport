<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Jobs;

use Exception;
use Igniter\Cart\Models\Menu;
use Igniter\Flame\Support\Facades\File;
use Igniter\System\Mail\AnonymousTemplateMailable;
use Igniter\User\Models\User;
use IgniterLabs\ImportExport\Classes\ImportExportManager;
use IgniterLabs\ImportExport\Jobs\ProcessImportJob;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Notifications\ImportFinishedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

beforeEach(function(): void {
    Mail::fake();
    Notification::fake();
});

function queuedImportHistory(string $code, string $contents, array $attemptedData = []): History
{
    $history = History::factory()->create([
        'user_id' => User::factory()->superUser()->create(['status' => true])->getKey(),
        'type' => 'import',
        'code' => $code,
        'status' => History::STATUS_QUEUED,
        'attempted_data' => $attemptedData,
    ]);

    File::put($history->getCsvPath(), $contents);

    return $history;
}

it('runs a csv import, records progress and notifies the admin', function(): void {
    $rows = collect(range(1, 30))->map(fn(int $i): string => sprintf('Queued Item %02d,%s', $i, $i))->implode("\n");
    $history = queuedImportHistory('igniterlabs-importexport-menus', "Title,Cost\n".$rows."\n", [
        'options' => ['update_existing' => '1'],
        'columns' => [['menu_name', 'Title'], ['menu_price', 'Cost']],
        'csv' => ['delimiter' => ',', 'enclosure' => '"', 'escape' => '\\'],
    ]);

    (new ProcessImportJob($history->getKey()))->handle(resolve(ImportExportManager::class));

    $history->refresh();
    expect($history->status)->toBe(History::STATUS_COMPLETED)
        ->and($history->total_rows)->toBe(30)
        ->and($history->processed_rows)->toBe(30)
        ->and($history->progress_percent)->toBe(100)
        ->and($history->finished_at)->not->toBeNull()
        ->and($history->error_message)->toContain('Created (30)')
        ->and($history->csvExists())->toBeFalse()
        ->and(Menu::query()->where('menu_name', 'like', 'Queued Item %')->count())->toBe(30);

    Notification::assertSentTo($history->user, ImportFinishedNotification::class);
    Mail::assertQueued(AnonymousTemplateMailable::class, fn($mail): bool => $mail->getTemplateCode() === 'igniterlabs.importexport::mail.import_completed');
});

it('runs a platform source import', function(): void {
    $history = queuedImportHistory(
        'igniterlabs-importexport-ubereats_menus',
        File::get(__DIR__.'/../_fixtures/platforms/ubereats_menu.json'),
        ['options' => ['update_existing' => '1', 'import_options' => '1']],
    );

    (new ProcessImportJob($history->getKey()))->handle(resolve(ImportExportManager::class));

    expect($history->refresh()->status)->toBe(History::STATUS_COMPLETED)
        ->and($history->error_message)->toContain('Errors (0)')
        ->and(Menu::query()->where('menu_name', 'Cheeseburger')->exists())->toBeTrue();
});

it('skips imports that are missing or no longer queued', function(): void {
    $history = queuedImportHistory('igniterlabs-importexport-menus', "Name\nSkipped\n");
    $history->update(['status' => History::STATUS_PROCESSING]);

    (new ProcessImportJob($history->getKey()))->handle(resolve(ImportExportManager::class));
    (new ProcessImportJob(999999))->handle(resolve(ImportExportManager::class));

    expect($history->refresh()->status)->toBe(History::STATUS_PROCESSING);
    Notification::assertNothingSent();
});

it('throws when the import type is no longer registered', function(): void {
    $history = queuedImportHistory('unknown-import-type', "Name\nX\n");

    expect(fn() => (new ProcessImportJob($history->getKey()))->handle(resolve(ImportExportManager::class)))
        ->toThrow(RuntimeException::class);
});

it('marks the import failed, removes the file and notifies the admin', function(): void {
    $history = queuedImportHistory('igniterlabs-importexport-menus', "Name\nX\n");

    (new ProcessImportJob($history->getKey()))->failed(new Exception('Database went away'));
    (new ProcessImportJob(999999))->failed(new Exception('Ignored'));

    $history->refresh();
    expect($history->status)->toBe(History::STATUS_FAILED)
        ->and($history->error_message)->toBe('The import failed: Database went away')
        ->and($history->finished_at)->not->toBeNull()
        ->and($history->csvExists())->toBeFalse();

    Notification::assertSentTo($history->user, ImportFinishedNotification::class);
    Mail::assertQueued(AnonymousTemplateMailable::class, fn($mail): bool => $mail->getTemplateCode() === 'igniterlabs.importexport::mail.import_failed');
});
