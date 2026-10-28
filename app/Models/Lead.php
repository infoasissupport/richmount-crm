<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'lead_number',
        'lead_date',
        'customer_name',
        'company_name',
        'rfq_number',
        'subject',
        'department',
        'branch_id',
        'assigned_engineer_id',
        'created_by_id',
        'source',
        'estimated_value',
        'status',
        'stage',
        'stage_date',
        'contact_person',
        'phone',
        'email',
        'notes',
        'lost_reason',
    ];

    protected function casts(): array
    {
        return [
            'lead_date' => 'date',
            'stage_date' => 'datetime',
            'estimated_value' => 'decimal:2',
        ];
    }

    /**
     * Scope to enforce role visibility rules.
     * The engineer sees only assigned leads. When marked Sent/Closed, it disappears from engineer view.
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
            return $query->where('assigned_engineer_id', $user->id)
                ->where('status', '!=', 'Closed')
                ->whereNotIn('stage', ['sent', 'awarded']);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Generate the next sequential lead number.
     */
    public static function nextLeadNumber(): string
    {
        $year = date('Y');
        $prefix = "LD-{$year}-";

        $latest = static::withTrashed()
            ->where(function ($q) use ($year) {
                $q->where('lead_number', 'like', "LD-{$year}-%")
                  ->orWhere('lead_number', 'like', "RM-LD-{$year}-%");
            })
            ->orderByDesc('id')
            ->first();

        $seq = 1001;
        if ($latest && preg_match('/-(\d+)$/', $latest->lead_number, $matches)) {
            $seq = max(1001, ((int) $matches[1]) + 1);
        }

        return $prefix . $seq;
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_engineer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class);
    }

    public function latestQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->latestOfMany();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeadAttachment::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->latest();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Pill tone for the lead status (matches the .pill-* classes).
     */
    public function statusTone(): string
    {
        return match (strtolower($this->status)) {
            'new' => 'green',
            'contacted' => 'blue',
            'proposal' => 'amber',
            'negotiation' => 'purple',
            'won' => 'primary',
            default => 'gray',
        };
    }

    /**
     * Display name for the department slug.
     */
    public function departmentLabel(): string
    {
        return match ($this->department) {
            'construction' => 'Construction',
            'interior' => 'Interior & Fit-Out',
            'renovation' => 'Renovation',
            'mep' => 'MEP Works',
            default => ucfirst((string) $this->department) ?: '—',
        };
    }

    /**
     * Icon and pill tone for the lead source.
     *
     * @return array{0: string, 1: string}
     */
    public function sourceStyle(): array
    {
        return match ($this->source) {
            'Email' => ['mail', 'red'],
            'Website' => ['globe', 'blue'],
            'Social Media' => ['share', 'purple'],
            'Referral' => ['users', 'green'],
            'Phone' => ['phone', 'amber'],
            'Exhibition' => ['award', 'pink'],
            default => ['info', 'gray'],
        };
    }
}
