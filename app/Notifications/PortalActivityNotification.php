<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PortalActivityNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $title, private readonly string $message, private readonly string $kind = 'info') {}
    public function via(object $notifiable): array { return ['database']; }
    public function toArray(object $notifiable): array { return ['title' => $this->title, 'message' => $this->message, 'kind' => $this->kind]; }
}
