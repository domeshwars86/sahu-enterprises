<?php

namespace App\Notifications;

use App\Models\BusinessSetting;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Schema;

class CrmReminderNotification extends Notification
{
    /** @param array{title: string, message: string, url: string} $reminder */
    public function __construct(private readonly array $reminder) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $emailRemindersEnabled = Schema::hasTable('business_settings')
            && BusinessSetting::query()->where('key', 'crm_reminder_email_enabled')->value('value') === '1';
        if ($emailRemindersEnabled && config('mail.default') !== 'log' && filled($notifiable->email ?? null)) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->reminder;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->reminder['title'].' · Sahu Enterprises')
            ->greeting('Hello '.$notifiable->name)
            ->line($this->reminder['message'])
            ->action('Open CRM', url($this->reminder['url']))
            ->line('This is an operational reminder for work assigned to your account.');
    }
}
