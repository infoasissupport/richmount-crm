<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ZohoSyncLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'entity_type',
        'entity_id',
        'reference',
        'action',
        'status',
        'zoho_id',
        'message',
        'payload',
        'response',
        'attempts',
        'synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'attempts' => 'integer',
        'synced_at' => 'datetime',
    ];

    /**
     * Color tone for badge display in the UI.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'synced' => 'green',
            'syncing' => 'blue',
            'queued' => 'amber',
            'failed' => 'red',
            default => 'gray',
        };
    }
}
