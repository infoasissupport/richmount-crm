<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'project_id',
        'quotation_id',
        'branch_id',
        'created_by_id',
        'client_name',
        'client_trn',
        'client_address',
        'client_po_number',
        'invoice_date',
        'due_date',
        'is_final',
        'subtotal',
        'discount_amount',
        'taxable_amount',
        'vat_rate',
        'vat_amount',
        'grand_total',
        'paid_amount',
        'balance_due',
        'status',
        'zoho_invoice_id',
        'zoho_status',
        'payment_terms',
        'notes',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'is_final' => 'boolean',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'taxable_amount' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return $query;
        }

        if ($user->isEngineer()) {
            return $query->whereHas('project', function ($q) use ($user) {
                $q->where('engineer_id', $user->id)
                  ->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query->where('branch_id', $user->branch_id);
    }

    public static function nextInvoiceNumber(): string
    {
        $year = date('Y');
        $prefix = "INV-{$year}-";

        $latest = static::withTrashed()
            ->where('invoice_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('invoice_number');

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function recalculateTotals(): void
    {
        $subtotal = (float) $this->lineItems()->sum('total_amount');
        $discount = (float) $this->discount_amount;
        $taxable = max(0, $subtotal - $discount);
        $vatRate = (float) $this->vat_rate;
        $vatAmount = round($taxable * $vatRate, 2);
        $grandTotal = $taxable + $vatAmount;
        $paid = (float) $this->payments()->sum('amount');
        $balance = max(0, $grandTotal - $paid);

        $status = $this->status;
        if ($paid > 0 && $balance <= 0.001) {
            $status = 'paid';
        } elseif ($paid > 0 && $balance > 0) {
            $status = 'partially_paid';
        }

        $this->update([
            'subtotal' => $subtotal,
            'taxable_amount' => $taxable,
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotal,
            'paid_amount' => $paid,
            'balance_due' => $balance,
            'status' => $status,
        ]);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->lineItems();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->latest();
    }

    public function statusTone(): string
    {
        return match ($this->status) {
            'draft' => 'amber',
            'sent' => 'blue',
            'partially_paid' => 'purple',
            'paid' => 'primary',
            'overdue' => 'red',
            'void' => 'gray',
            default => 'gray',
        };
    }
}
