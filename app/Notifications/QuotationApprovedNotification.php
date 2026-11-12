<?php

namespace App\Notifications;

use App\Models\Quotation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Quotation $quotation,
        public User $approver
    ) {}

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
        $client = $this->quotation->lead?->customer_name
            ?: ($this->quotation->lead?->company_name ?: 'Client');
        $grandTotal = number_format((float) $this->quotation->grand_total, 2);

        return [
            'type' => 'quote',
            'quotation_id' => $this->quotation->id,
            'quote_number' => $this->quotation->quote_number,
            'title' => "Quotation Approved: {$this->quotation->quote_number}",
            'text' => "Quotation {$this->quotation->quote_number} for {$client} (AED {$grandTotal}) approved by {$this->approver->name}",
            'link' => route('quotations.builder', ['id' => $this->quotation->id]),
            'approved_by' => $this->approver->name,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
