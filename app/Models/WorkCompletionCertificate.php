<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkCompletionCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_number',
        'project_id',
        'certificate_date',
        'completion_date',
        'work_description',
        'retention_pct',
        'retention_amount',
        'engineer_id',
        'client_representative_name',
        'client_representative_title',
        'client_signed_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'certificate_date' => 'date',
        'completion_date' => 'date',
        'client_signed_at' => 'datetime',
        'retention_pct' => 'decimal:2',
        'retention_amount' => 'decimal:2',
    ];

    public static function nextCertificateNumber(): string
    {
        $year = date('Y');
        $prefix = "WCC-{$year}-";

        $latest = static::where('certificate_number', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->value('certificate_number');

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

    public function engineer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'engineer_id');
    }
}
