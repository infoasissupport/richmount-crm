<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyCard extends Model
{
    protected $fillable = [
        'card_name',
        'last_four',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function displayLabel(): string
    {
        if ($this->last_four && ! str_contains($this->card_name, $this->last_four)) {
            return "{$this->card_name} (•••• {$this->last_four})";
        }

        return $this->card_name;
    }
}
