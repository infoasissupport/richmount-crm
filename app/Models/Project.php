<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_number',
        'name',
        'lead_id',
        'quotation_id',
        'branch_id',
        'engineer_id',
        'created_by_id',
        'client_name',
        'client_po_number',
        'client_po_date',
        'client_po_amount',
        'client_po_file',
        'contract_value',
        'start_date',
        'expected_end_date',
        'actual_end_date',
        'closed_at',
        'status',
        'progress_pct',
        'description',
        'completion_report',
        'hold_reason',
        'zoho_project_id',
        'zoho_status',
    ];

    protected $casts = [
        'client_po_date' => 'date',
        'client_po_amount' => 'decimal:2',
        'contract_value' => 'decimal:2',
        'start_date' => 'date',
        'expected_end_date' => 'date',
        'actual_end_date' => 'date',
        'closed_at' => 'datetime',
        'progress_pct' => 'integer',
    ];

    /**
     * Scope projects accessible by the authenticated user.
     */
    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isAccountant() || $user->all_branches) {
            return $query;
        }

        if ($user->isEngineer()) {
            return $query->where(function ($q) use ($user) {
                $q->where('engineer_id', $user->id)
                  ->orWhere('branch_id', $user->branch_id);
            });
        }

        return $query->where('branch_id', $user->branch_id);
    }

    /**
     * Generate next Project number sequence: PRJ-YYYY-XXXX
     */
    public static function nextProjectNumber(): string
    {
        $year = date('Y');
        $prefix = "PRJ-{$year}-";

        $latest = static::withTrashed()
            ->where('project_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('project_number');

        if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
            $seq = intval($matches[1]) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    public function hasClientPo(): bool
    {
        return !empty($this->client_po_number);
    }

    public function canRecordExpenses(): bool
    {
        return in_array($this->status, ['open', 'inprogress']);
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function assignedEngineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ProjectDocument::class)->latest();
    }

    public function activities(): HasMany
    {
        return $this->hasMany(ProjectActivity::class)->latest();
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class)->latest();
    }

    public function workCompletionCertificates(): HasMany
    {
        return $this->hasMany(WorkCompletionCertificate::class)->latest();
    }

    public function latestWorkCompletionCertificate(): HasOne
    {
        return $this->hasOne(WorkCompletionCertificate::class)->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->latest();
    }

    /**
     * Total approved expenses on this project.
     */
    public function totalExpenses(): float
    {
        return (float) $this->expenses()->where('status', 'approved')->sum('grand_total');
    }

    /**
     * Total invoiced amount on this project.
     */
    public function totalInvoiced(): float
    {
        return (float) $this->invoices()->whereNotIn('status', ['void', 'draft'])->sum('grand_total');
    }

    /**
     * UI Status pill representation matching static prototype CSS.
     */
    public function statusTone(): string
    {
        return match ($this->status) {
            'popending' => 'amber',
            'open' => 'blue',
            'inprogress' => 'primary',
            'onhold' => 'red',
            'completed' => 'purple',
            'invoicepending' => 'pink',
            'closed' => 'gray',
            default => 'gray',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'popending' => 'PO Pending',
            'open' => 'Open',
            'inprogress' => 'In Progress',
            'onhold' => 'On Hold',
            'completed' => 'Work Completed',
            'invoicepending' => 'Invoice Pending',
            'closed' => 'Closed',
            default => ucfirst($this->status),
        };
    }
}
