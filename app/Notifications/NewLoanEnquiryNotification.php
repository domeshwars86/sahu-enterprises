<?php

namespace App\Notifications;

use App\Models\LoanEnquiry;
use Illuminate\Notifications\Notification;

class NewLoanEnquiryNotification extends Notification
{
    public function __construct(public LoanEnquiry $enquiry) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['title' => 'New loan enquiry', 'message' => 'Enquiry '.$this->enquiry->reference_number.' is ready for review.', 'url' => '/admin/enquiries/'.$this->enquiry->id, 'enquiry_id' => $this->enquiry->id];
    }
}
