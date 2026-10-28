<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number',
        'project_id',
        'branch_id',
        'vendor_name',
        'vendor_trn',
        'vendor_contact',
        'po_date',
        'delivery_date',
        'subtotal',
        'vat_rate',
        'vat_amount',
        'grand_total',
        'status',
        'prepared_by_id',
        'approved_by_id',
        'terms',
        'notes',
    ];

    protected $casts = [
        'po_date' => 'date',
        'delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'vat_rate' => 'decimal:4',
        'vat_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public static function nextPoNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";

        $latest = static::withTrashed()
            ->where('po_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('po_number');

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
        $vatRate = (float) $this->vat_rate;
        $vatAmount = round($subtotal * $vatRate, 2);
        $grandTotal = $subtotal + $vatAmount;

        $this->update([
            'subtotal' => $subtotal,
            'vat_amount' => $vatAmount,
            'grand_total' => $grandTotal,
        ]);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class)->orderBy('sort_order');
    }
}
