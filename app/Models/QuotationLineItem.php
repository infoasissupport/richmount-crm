<?php

namespace App\Models;

use App\Support\RichText;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationLineItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'sort_order',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'total_amount',
        'photo_path',
        'photos',
    ];

    protected function casts(): array
    {
        return [
            'photos' => 'array', // up to 3 paths on the public disk (quotation_photos/...)
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    /**
     * The builder's rich text editor sends HTML; only bold / italic / underline / colours are kept
     * (App\Support\RichText), on every save path (create, update, autosave, clone).
     */
    protected function description(): Attribute
    {
        return Attribute::make(set: fn (?string $value) => RichText::clean($value));
    }
}
