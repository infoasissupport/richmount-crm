<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'expense_number',
        'project_id',
        'branch_id',
        'user_id',
        'category',
        'vendor_name',
        'vendor_trn',
        'bill_number',
        'date',
        'type',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'grand_total',
        'receipt_file',
        'ocr_data',
        'description',
        'status',
        'rejection_reason',
        'approved_by_id',
        'approved_at',
        'zoho_expense_id',
        'zoho_status',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'ocr_data' => 'array',
    ];

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return $query;
        }

        if ($user->isEngineer()) {
            return $query->where('user_id', $user->id);
        }

        return $query->where('branch_id', $user->branch_id);
    }

    public static function nextExpenseNumber(): string
    {
        $year = date('Y');
        $prefix = "EXP-{$year}-";

        $latest = static::withTrashed()
            ->where('expense_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('expense_number');

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
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
