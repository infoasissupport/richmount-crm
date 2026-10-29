<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_number',
        'lead_id',
        'branch_id',
        'prepared_by_id',
        'approved_by_id',
        'quote_date',
        'validity_days',
        'output_type',
        'status',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'taxable_amount',
        'vat_rate',
        'vat_amount',
        'grand_total',
        'technical_description',
        'terms_conditions',
        'internal_notes',
        'rejection_reason',
        'auto_saved_at',
        'award_date',
    ];

    protected function casts(): array
    {
        return [
            'quote_date' => 'date',
            'award_date' => 'date',
            'auto_saved_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'discount_value' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'taxable_amount' => 'decimal:2',
            'vat_rate' => 'decimal:4',
            'vat_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
        ];
    }

    /**
     * Scope accessible quotations by user role.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isAccountant()) {
            return $query;
        }

        if ($user->isEstimation()) {
            return $user->all_branches
                ? $query
                : $query->where('branch_id', $user->branch_id);
        }

        if ($user->isEngineer()) {
            return $query->where(function ($q) use ($user) {
                $q->where('prepared_by_id', $user->id)
                  ->orWhereHas('lead', fn($lq) => $lq->where('assigned_engineer_id', $user->id));
            });
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Only each lead's latest quotation: newest created_at, the higher id breaking ties (ids follow insert order).
     * The choice is made across all of the lead's (non-deleted) quotations before any other filter, so every
     * field shown — number, total, date, status — comes from that one record. Quote numbers are not used:
     * revisions keep their number (…_R1) and clones take the next free one.
     */
    public function scopeLatestPerLead(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('id'), function ($latest) {
            $latest->select('q2.id')
                ->from('quotations as q2')
                ->whereColumn('q2.lead_id', 'quotations.lead_id')
                ->whereNull('q2.deleted_at')
                ->orderByDesc('q2.created_at')
                ->orderByDesc('q2.id')
                ->limit(1);
        });
    }

    /**
     * Generate sequential quotation number.
     */
    public static function nextQuoteNumber(): string
    {
        $year = date('Y');
        $prefix = "RM-QT-{$year}-";

        $latest = static::withTrashed()
            ->where('quote_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest->quote_number, $matches)) {
            $seq = ((int) $matches[1]) + 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Recalculate quotation subtotal, discount, tax, and total.
     */
    public function recalculateTotals(): void
    {
        $subtotal = $this->lineItems()->sum('total_amount');
        $discountAmount = 0.00;

        if ($this->discount_type === 'pct') {
            $discountAmount = round(($subtotal * ($this->discount_value / 100)), 2);
        } else {
            $discountAmount = min($subtotal, (float) $this->discount_value);
        }

        $taxable = max(0, $subtotal - $discountAmount);
        $vat = round($taxable * (float) $this->vat_rate, 2);
        $grandTotal = round($taxable + $vat, 2);

        $this->updateQuietly([
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'taxable_amount' => $taxable,
            'vat_amount' => $vat,
            'grand_total' => $grandTotal,
        ]);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
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
        return $this->hasMany(QuotationLineItem::class)->orderBy('sort_order');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(QuotationAttachment::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(QuotationHistory::class)->latest();
    }

    public function project(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Project::class);
    }

    /**
     * "UAE DIRHAMS ONE THOUSAND ... AND FIFTY FILS ONLY" for the quotation document
     * (same wording as the builder preview's amountWords()).
     */
    public static function amountInWords(float $amount): string
    {
        $cents = (int) round($amount * 100);
        $aed = intdiv($cents, 100);
        $fils = $cents % 100;

        return 'UAE DIRHAMS ' . ($aed ? self::numberWords($aed) : 'ZERO')
            . ($fils ? ' AND ' . self::numberWords($fils) . ' FILS' : '') . ' ONLY';
    }

    protected static function numberWords(int $n): string
    {
        $ones = ['', 'ONE', 'TWO', 'THREE', 'FOUR', 'FIVE', 'SIX', 'SEVEN', 'EIGHT', 'NINE', 'TEN', 'ELEVEN', 'TWELVE',
            'THIRTEEN', 'FOURTEEN', 'FIFTEEN', 'SIXTEEN', 'SEVENTEEN', 'EIGHTEEN', 'NINETEEN'];
        $tens = ['', '', 'TWENTY', 'THIRTY', 'FORTY', 'FIFTY', 'SIXTY', 'SEVENTY', 'EIGHTY', 'NINETY'];

        if ($n < 20) {
            return $ones[$n];
        }
        if ($n < 100) {
            return $tens[intdiv($n, 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        }
        if ($n < 1000) {
            return $ones[intdiv($n, 100)] . ' HUNDRED' . ($n % 100 ? ' AND ' . self::numberWords($n % 100) : '');
        }
        foreach ([[1_000_000_000, 'BILLION'], [1_000_000, 'MILLION'], [1_000, 'THOUSAND']] as [$value, $word]) {
            if ($n >= $value) {
                $rest = $n % $value;

                return self::numberWords(intdiv($n, $value)) . ' ' . $word
                    . ($rest ? ($rest < 100 ? ' AND ' : ' ') . self::numberWords($rest) : '');
            }
        }

        return '';
    }
}
