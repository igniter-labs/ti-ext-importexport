<?php

declare(strict_types=1);

namespace IgniterLabs\ImportExport\Notifications;

use Igniter\User\Classes\Notification;
use Igniter\User\Models\User;
use IgniterLabs\ImportExport\Models\History;
use Override;

/**
 * Tells the admin who started an import that it completed or failed,
 * in the admin notifications menu and by email.
 */
class ImportFinishedNotification extends Notification
{
    #[Override]
    public function broadcast(array $users = []): static
    {
        $recipients = $users ?: $this->getRecipients();

        parent::broadcast($recipients);

        foreach ($recipients as $user) {
            $this->sendMail($user);
        }

        return $this;
    }

    #[Override]
    public function getRecipients(): array
    {
        $user = $this->history()?->user;

        return $user instanceof User && $user->isEnabled() ? [$user] : [];
    }

    #[Override]
    public function getTitle(): string
    {
        return lang($this->isFailed()
            ? 'igniterlabs.importexport::default.notify_import_failed_title'
            : 'igniterlabs.importexport::default.notify_import_completed_title');
    }

    #[Override]
    public function getMessage(): string
    {
        return sprintf(lang($this->isFailed()
            ? 'igniterlabs.importexport::default.notify_import_failed'
            : 'igniterlabs.importexport::default.notify_import_completed'), $this->recordLabel());
    }

    #[Override]
    public function getUrl(): string
    {
        return admin_url('igniterlabs/importexport/import_export');
    }

    #[Override]
    public function getIcon(): ?string
    {
        return $this->isFailed() ? 'fa-triangle-exclamation' : 'fa-file-import';
    }

    #[Override]
    public function getIconColor(): ?string
    {
        return $this->isFailed() ? 'danger' : 'success';
    }

    #[Override]
    public function getAlias(): string
    {
        return 'importexport-import-finished';
    }

    protected function sendMail(User $user): void
    {
        $user->mailSend($this->isFailed()
            ? 'igniterlabs.importexport::mail.import_failed'
            : 'igniterlabs.importexport::mail.import_completed', 'staff', [
                'import_label' => $this->recordLabel(),
                'import_summary' => (string)$this->history()?->error_message,
                'import_history_url' => $this->getUrl(),
            ]);
    }

    protected function history(): ?History
    {
        return $this->subject instanceof History ? $this->subject : null;
    }

    protected function isFailed(): bool
    {
        return $this->history()?->status === History::STATUS_FAILED;
    }

    protected function recordLabel(): string
    {
        return (string)($this->history()?->label ?: $this->history()?->code);
    }
}
