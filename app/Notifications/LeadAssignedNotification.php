<?php

namespace App\Notifications;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LeadAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Lead $lead,
        public ?User $assignedBy = null
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
        $assignerName = $this->assignedBy ? $this->assignedBy->name : 'System';
        $client = $this->lead->company_name ?: ($this->lead->customer_name ?: 'Client');
        $subject = $this->lead->subject ? " · {$this->lead->subject}" : '';

        return [
            'type' => 'lead',
            'lead_id' => $this->lead->id,
            'lead_number' => $this->lead->lead_number,
            'title' => "New Lead Assigned: {$this->lead->lead_number}",
            'text' => "{$client}{$subject} assigned to you by {$assignerName}",
            'link' => route('leads.show', $this->lead),
            'assigned_by' => $assignerName,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
