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
     * Leads offered in the "Create Quotation" lead picker: not yet awarded or lost, newest first (50).
     */
    public function scopeQuotable(Builder $query): Builder
    {
        return $query->whereNotIn('stage', ['awarded', 'lost'])->latest()->take(50);
    }

    /**
     * Scope to enforce role visibility rules.
     * The engineer sees only assigned leads. When marked Sent/Closed, it disappears from engineer view.
     * The accountant sees only awarded leads.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isAccountant()) {
            return $query->where(function ($q) {
                $q->where('stage', 'awarded')
                  ->orWhere('status', 'Won');
            });
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

    /**
     * The lead's current quotation — same rule as Quotation::scopeLatestPerLead (newest created_at, then id).
     */
    public function latestQuotation(): HasOne
    {
        return $this->hasOne(Quotation::class)->latestOfMany(['created_at', 'id']);
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
     * Display label for the lead status.
     */
    public function statusLabel(): string
    {
        return match (strtolower(trim((string) $this->status))) {
            'rejected', 'revision req.', 'revision req', 'revision required' => 'Revision Req.',
            'pending', 'pending approval' => 'Pending Approval',
            'draft' => 'Draft',
            'proposal' => 'Proposal',
            'new' => 'New',
            'opened' => 'Opened',
            'contacted' => 'Contacted',
            'negotiation' => 'Negotiation',
            'won', 'awarded' => 'Won',
            'closed', 'sent' => 'Closed',
            'lost' => 'Lost',
            default => ucwords($this->status),
        };
    }

    /**
     * Pill tone for the lead status (matches the .pill-* classes).
     */
    public function statusTone(): string
    {
        return match (strtolower(trim((string) $this->status))) {
            'new' => 'green',
            'opened' => 'blue',
            'contacted' => 'blue',
            'draft', 'proposal' => 'amber',
            'pending', 'pending approval' => 'purple',
            'approved' => 'green',
            'rejected', 'revision req.', 'revision req', 'revision required' => 'red',
            'negotiation' => 'purple',
            'sent', 'closed' => 'gray',
            'won', 'awarded' => 'primary',
            'lost' => 'red',
            default => 'gray',
        };
    }

    /**
     * Mark the lead as Opened if opened by an engineer and created by an estimation user while in New status.
     */
    public function markOpenedByEngineer(User $user): bool
    {
        if ($user->isEngineer() && strtolower((string) $this->status) === 'new') {
            $creator = $this->createdBy;
            if ($creator && $creator->isEstimation()) {
                $this->update(['status' => 'Opened']);

                $this->activities()->create([
                    'user_id' => $user->id,
                    'kind' => 'status',
                    'text' => "Lead opened by assigned engineer {$user->name}",
                ]);

                \App\Services\AuditService::log(
                    'status_change',
                    $this,
                    "Lead {$this->lead_number} status changed from New to Opened upon view by engineer {$user->name}"
                );

                return true;
            }
        }

        return false;
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
