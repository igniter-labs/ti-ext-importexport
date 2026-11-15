<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Tests\Notifications;

use Igniter\Cart\Models\Menu;
use Igniter\User\Models\User;
use IgniterLabs\ImportExport\Models\History;
use IgniterLabs\ImportExport\Notifications\ImportFinishedNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

it('describes a completed import', function(): void {
    $history = History::factory()->create(['type' => 'import', 'code' => 'igniterlabs-importexport-menus', 'status' => History::STATUS_COMPLETED]);

    $notification = ImportFinishedNotification::make()->subject($history);

    expect($notification->getTitle())->toBe('Import completed')
        ->and($notification->getMessage())->toBe('Import Menu Items has finished importing.')
        ->and($notification->getUrl())->toBe(admin_url('igniterlabs/importexport/import_export'))
        ->and($notification->getIcon())->toBe('fa-file-import')
        ->and($notification->getIconColor())->toBe('success')
        ->and($notification->getAlias())->toBe('importexport-import-finished');
});

it('describes a failed import', function(): void {
    $history = History::factory()->create(['type' => 'import', 'code' => 'unregistered-code', 'status' => History::STATUS_FAILED]);

    $notification = ImportFinishedNotification::make()->subject($history);

    expect($notification->getTitle())->toBe('Import failed')
        ->and($notification->getMessage())->toBe('unregistered-code could not be completed.')
        ->and($notification->getIcon())->toBe('fa-triangle-exclamation')
        ->and($notification->getIconColor())->toBe('danger');
});

it('only notifies the enabled admin who started the import', function(): void {
    Mail::fake();
    Notification::fake();

    $enabled = User::factory()->create(['status' => true]);
    $disabled = User::factory()->create(['status' => false]);

    $completed = History::factory()->create(['type' => 'import', 'status' => History::STATUS_COMPLETED, 'user_id' => $enabled->getKey()]);
    $fromDisabled = History::factory()->create(['type' => 'import', 'status' => History::STATUS_COMPLETED, 'user_id' => $disabled->getKey()]);
    $withoutUser = History::factory()->create(['type' => 'import', 'status' => History::STATUS_COMPLETED, 'user_id' => null]);

    expect(ImportFinishedNotification::make()->subject($completed)->getRecipients())->toHaveCount(1)
        ->and(ImportFinishedNotification::make()->subject($fromDisabled)->getRecipients())->toBe([])
        ->and(ImportFinishedNotification::make()->subject($withoutUser)->getRecipients())->toBe([])
        ->and(ImportFinishedNotification::make()->subject(Menu::factory()->create())->getRecipients())->toBe([]);

    ImportFinishedNotification::make()->subject($fromDisabled)->broadcast();
    Notification::assertNothingSent();
    Mail::assertNothingQueued();

    ImportFinishedNotification::make()->subject($completed)->broadcast();
    Notification::assertSentTo($enabled, ImportFinishedNotification::class);
    Mail::assertQueuedCount(1);
});
