<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transfer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transfer_number',
        'date',
        'from_user_id',
        'to_user_id',
        'branch_id',
        'created_by_id',
        'amount',
        'payment_method',
        'reference_number',
        'status',
        'rejection_reason',
        'approved_by_id',
        'approved_at',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return $query;
        }

        if ($user->isEngineer()) {
            return $query->where(function ($q) use ($user) {
                $q->where('to_user_id', $user->id)
                  ->orWhere('from_user_id', $user->id);
            });
        }

        return $query->where('branch_id', $user->branch_id);
    }

    public static function nextTransferNumber(): string
    {
        $year = date('Y');
        $prefix = "TRF-{$year}-";

        $latest = static::withTrashed()
            ->where('transfer_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('transfer_number');

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'pending' => 'amber',
            'approved' => 'primary',
            'rejected' => 'red',
            default => 'gray',
        };
    }
}
