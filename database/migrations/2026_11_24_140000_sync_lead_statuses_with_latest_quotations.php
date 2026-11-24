<?php

use App\Models\Lead;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $leads = Lead::with(['quotations' => fn ($q) => $q->latest('created_at')->latest('id')])->get();

        foreach ($leads as $lead) {
            $latestQuote = $lead->quotations->first();
            if (! $latestQuote) {
                continue;
            }

            $newStatus = match ($latestQuote->status) {
                'rejected' => 'Rejected',
                'pending' => 'Pending Approval',
                'approved' => 'Approved',
                'sent' => 'Closed',
                'awarded' => 'Won',
                'draft' => in_array(strtolower((string) $lead->status), ['new', 'opened', 'draft']) ? 'Proposal' : $lead->status,
                default => null,
            };

            if ($newStatus && $lead->status !== $newStatus) {
                $lead->update(['status' => $newStatus]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
