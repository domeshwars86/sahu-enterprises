<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use Illuminate\Notifications\Notification;

class NewContactMessageNotification extends Notification
{
    public function __construct(public ContactMessage $contactMessage) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New contact enquiry',
            'message' => 'Contact request '.$this->contactMessage->reference_number.' is ready for review.',
            'url' => '/admin/contact-messages',
            'contact_message_id' => $this->contactMessage->id,
        ];
    }
}
