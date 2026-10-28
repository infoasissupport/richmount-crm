<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\QuotationHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuotationService
{
    /**
     * Create a new draft quotation for a lead.
     */
    public function createDraft(Lead $lead, array $data, User $author): Quotation
    {
        return DB::transaction(function () use ($lead, $data, $author) {
            $quote = Quotation::create([
                'quote_number' => Quotation::nextQuoteNumber(),
                'lead_id' => $lead->id,
                'branch_id' => $lead->branch_id,
                'prepared_by_id' => $author->id,
                'quote_date' => $data['quote_date'] ?? now()->toDateString(),
                'validity_days' => $data['validity_days'] ?? 30,
                'output_type' => $data['output_type'] ?? 'tax',
                'status' => 'draft',
                'discount_type' => $data['discount_type'] ?? 'amt',
                'discount_value' => $data['discount_value'] ?? 0.00,
                'vat_rate' => $data['vat_rate'] ?? 0.05,
                'technical_description' => $data['technical_description'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'auto_saved_at' => now(),
            ]);

            // Add line items if provided
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $desc = trim($item['description'] ?? '');
                    $price = (float) ($item['unit_price'] ?? 0);
                    $qty = (float) ($item['quantity'] ?? 1);
                    if ($desc === '' && $price == 0.0) {
                        continue;
                    }
                    $quote->lineItems()->create([
                        'sort_order' => $index + 1,
                        'description' => $desc !== '' ? $desc : 'Item specifications & scope',
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'nos',
                        'unit_price' => $price,
                        'total_amount' => round($qty * $price, 2),
                    ]);
                }
            }

            $quote->recalculateTotals();

            // Lead stage update
            if ($lead->stage === 'new') {
                $lead->update([
                    'stage' => 'draft',
                    'stage_date' => now(),
                ]);
            }

            QuotationHistory::create([
                'quotation_id' => $quote->id,
                'user_id' => $author->id,
                'action' => 'created',
                'text' => "Quotation {$quote->quote_number} created as draft",
            ]);

            AuditService::log('created', $quote, "Created quotation draft {$quote->quote_number}");

            return $quote;
        });
    }

    /**
     * Auto-save or update draft line items and totals.
     */
    public function autosaveDraft(Quotation $quotation, array $data): Quotation
    {
        return DB::transaction(function () use ($quotation, $data) {
            $quotation->update([
                'quote_date' => $data['quote_date'] ?? $quotation->quote_date,
                'validity_days' => $data['validity_days'] ?? $quotation->validity_days,
                'output_type' => $data['output_type'] ?? $quotation->output_type,
                'discount_type' => $data['discount_type'] ?? $quotation->discount_type,
                'discount_value' => $data['discount_value'] ?? $quotation->discount_value,
                'vat_rate' => $data['vat_rate'] ?? $quotation->vat_rate,
                'technical_description' => $data['technical_description'] ?? $quotation->technical_description,
                'terms_conditions' => $data['terms_conditions'] ?? $quotation->terms_conditions,
                'internal_notes' => $data['internal_notes'] ?? $quotation->internal_notes,
                'auto_saved_at' => now(),
            ]);

            if (isset($data['items']) && is_array($data['items'])) {
                $quotation->lineItems()->delete();
                foreach ($data['items'] as $index => $item) {
                    if (empty(trim($item['description'] ?? ''))) {
                        continue;
                    }
                    $qty = (float) ($item['quantity'] ?? 1);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $quotation->lineItems()->create([
                        'sort_order' => $index + 1,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'nos',
                        'unit_price' => $price,
                        'total_amount' => round($qty * $price, 2),
                    ]);
                }
            }

            $quotation->recalculateTotals();

            return $quotation;
        });
    }

    /**
     * Clone an existing quotation into a new draft.
     */
    public function cloneQuote(Quotation $source, User $author, ?int $targetLeadId = null): Quotation
    {
        return DB::transaction(function () use ($source, $author, $targetLeadId) {
            $clone = $source->replicate([
                'quote_number',
                'status',
                'approved_by_id',
                'rejection_reason',
                'award_date',
                'created_at',
                'updated_at',
            ]);

            if ($targetLeadId) {
                $targetLead = Lead::findOrFail($targetLeadId);
                $clone->lead_id = $targetLead->id;
                $clone->branch_id = $targetLead->branch_id;
            }

            $clone->quote_number = Quotation::nextQuoteNumber();
            $clone->prepared_by_id = $author->id;
            $clone->status = 'draft';
            $clone->quote_date = now()->toDateString();
            $clone->auto_saved_at = now();
            $clone->save();

            // Clone line items
            foreach ($source->lineItems as $item) {
                $clone->lineItems()->create($item->replicate(['quotation_id', 'created_at', 'updated_at'])->toArray());
            }

            $clone->recalculateTotals();

            QuotationHistory::create([
                'quotation_id' => $clone->id,
                'user_id' => $author->id,
                'action' => 'cloned',
                'text' => "Cloned from quotation {$source->quote_number}",
            ]);

            AuditService::log('cloned', $clone, "Cloned quotation {$clone->quote_number} from {$source->quote_number}");

            return $clone;
        });
    }

    /**
     * Submit draft quotation for estimation review/approval.
     */
    public function submitForApproval(Quotation $quotation, User $author): Quotation
    {
        $quotation->update([
            'status' => 'pending',
        ]);

        QuotationHistory::create([
            'quotation_id' => $quotation->id,
            'user_id' => $author->id,
            'action' => 'submitted',
            'text' => 'Quotation submitted for approval',
        ]);

        AuditService::log('submitted', $quotation, "Submitted quotation {$quotation->quote_number} for approval");

        return $quotation;
    }

    /**
     * Approve quotation.
     */
    public function approve(Quotation $quotation, User $approver): Quotation
    {
        $quotation->update([
            'status' => 'approved',
            'approved_by_id' => $approver->id,
            'rejection_reason' => null,
        ]);

        QuotationHistory::create([
            'quotation_id' => $quotation->id,
            'user_id' => $approver->id,
            'action' => 'approved',
            'text' => "Quotation approved by {$approver->name}",
        ]);

        AuditService::log('approved', $quotation, "Approved quotation {$quotation->quote_number}");

        return $quotation;
    }

    /**
     * Reject / request revision.
     */
    public function reject(Quotation $quotation, string $reason, User $approver): Quotation
    {
        $quotation->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        QuotationHistory::create([
            'quotation_id' => $quotation->id,
            'user_id' => $approver->id,
            'action' => 'rejected',
            'text' => "Quotation rejected: {$reason}",
        ]);

        AuditService::log('rejected', $quotation, "Rejected quotation {$quotation->quote_number} - {$reason}");

        return $quotation;
    }

    /**
     * Mark quotation as Sent to customer.
     * Crucial rule: Updates lead stage to 'sent' and status to 'Closed', hiding it from engineer's view.
     */
    public function sendToCustomer(Quotation $quotation, User $sender, array $options = []): Quotation
    {
        return DB::transaction(function () use ($quotation, $sender, $options) {
            $quotation->update([
                'status' => 'sent',
            ]);

            // Update lead — engineer sees only assigned leads, once sent lead becomes Closed
            $lead = $quotation->lead;
            $lead->update([
                'stage' => 'sent',
                'status' => 'Closed',
                'stage_date' => now(),
            ]);

            $mode = $options['share_mode'] ?? 'Email';
            $recipient = $options['recipient'] ?? ($lead->email ?? 'customer');

            QuotationHistory::create([
                'quotation_id' => $quotation->id,
                'user_id' => $sender->id,
                'action' => 'sent',
                'text' => "Quotation {$quotation->quote_number} sent to {$recipient} via {$mode}",
                'metadata' => [
                    'share_mode' => $mode,
                    'recipient' => $recipient,
                    'output_mode' => $options['output_mode'] ?? 'Total + Tax',
                    'message' => $options['message'] ?? null,
                ],
            ]);

            AuditService::log('sent', $quotation, "Marked quotation {$quotation->quote_number} as Sent via {$mode}");

            return $quotation;
        });
    }

    /**
     * Mark quotation as Awarded.
     */
    public function markAwarded(Quotation $quotation, User $user): Quotation
    {
        return DB::transaction(function () use ($quotation, $user) {
            $quotation->update([
                'status' => 'awarded',
                'award_date' => now()->toDateString(),
            ]);

            // Update lead
            $lead = $quotation->lead;
            $lead->update([
                'stage' => 'awarded',
                'status' => 'Won',
                'stage_date' => now(),
            ]);

            QuotationHistory::create([
                'quotation_id' => $quotation->id,
                'user_id' => $user->id,
                'action' => 'awarded',
                'text' => "Quotation marked as Awarded ({$quotation->grand_total} AED)",
            ]);

            AuditService::log('awarded', $quotation, "Awarded quotation {$quotation->quote_number}");

            return $quotation;
        });
    }

    /**
     * Reopen an approved or sent quotation back into draft for editing.
     */
    public function reopenQuote(Quotation $quotation, User $user, string $reason, ?int $engineerId = null): Quotation
    {
        return DB::transaction(function () use ($quotation, $user, $reason, $engineerId) {
            if (!in_array($quotation->status, ['approved', 'sent'])) {
                throw new \InvalidArgumentException('Only approved or sent quotations can be reopened for editing.');
            }

            $wasSent = $quotation->status === 'sent';
            $oldNumber = $quotation->quote_number;

            if ($wasSent) {
                // If previously sent to customer, create revision number (e.g. QT-2026-0805_R1, _R2)
                $base = preg_replace('/_R\d+$/', '', $quotation->quote_number);
                if (preg_match('/_R(\d+)$/', $quotation->quote_number, $matches)) {
                    $rev = ((int)$matches[1]) + 1;
                } else {
                    $rev = 1;
                }
                $quotation->quote_number = "{$base}_R{$rev}";
                $quotation->quote_date = now()->toDateString();
            }

            $quotation->status = 'draft';
            $quotation->approved_by_id = null;
            $quotation->rejection_reason = null;
            $quotation->auto_saved_at = now();

            // Reassign lead engineer if specified
            if ($engineerId) {
                $engineer = User::findOrFail($engineerId);
                $quotation->lead?->update(['assigned_engineer_id' => $engineer->id]);
            }

            $quotation->save();

            // Sync lead stage back to draft
            if ($quotation->lead && in_array($quotation->lead->stage, ['approved', 'sent'])) {
                $quotation->lead->update(['stage' => 'draft']);
            }

            $actionText = $wasSent
                ? "Quotation revised ({$oldNumber} -> {$quotation->quote_number}) and moved to draft for editing: {$reason}"
                : "Quotation moved to draft for editing: {$reason}";

            QuotationHistory::create([
                'quotation_id' => $quotation->id,
                'user_id' => $user->id,
                'action' => 'reopened',
                'text' => $actionText,
            ]);

            AuditService::log('reopened', $quotation, $actionText);

            return $quotation;
        });
    }
}
