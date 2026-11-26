<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CrmAlertNotification extends Notification
{
    /** @param array{title: string, message: string, url: string} $alert */
    public function __construct(private readonly array $alert) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, string> */
    public function toArray(object $notifiable): array
    {
        return $this->alert;
    }
}
