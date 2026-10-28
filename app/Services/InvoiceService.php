<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    /**
     * Create progress or final tax invoice.
     */
    public function createInvoice(Project $project, array $data, User $author): Invoice
    {
        return DB::transaction(function () use ($project, $data, $author) {
            $isFinal = (bool) ($data['is_final'] ?? false);

            $invoice = Invoice::create([
                'invoice_number' => Invoice::nextInvoiceNumber(),
                'project_id' => $project->id,
                'quotation_id' => $project->quotation_id,
                'branch_id' => $project->branch_id,
                'created_by_id' => $author->id,
                'client_name' => $project->client_name,
                'client_trn' => $data['client_trn'] ?? null,
                'client_address' => $data['client_address'] ?? null,
                'client_po_number' => $project->client_po_number,
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(30)->toDateString(),
                'is_final' => $isFinal,
                'discount_amount' => $data['discount_amount'] ?? 0.00,
                'vat_rate' => $data['vat_rate'] ?? 0.05,
                'status' => 'sent',
                'payment_terms' => $data['payment_terms'] ?? 'Payment within 30 days of invoice date via bank transfer.',
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $invoice->lineItems()->create([
                        'sort_order' => $index + 1,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'nos',
                        'unit_price' => $price,
                        'total_amount' => round($qty * $price, 2),
                    ]);
                }
            } else {
                // Default line item if none provided
                $invoice->lineItems()->create([
                    'sort_order' => 1,
                    'description' => $isFinal ? "Final Settlement - {$project->name}" : "Progress Milestone Claim - {$project->name}",
                    'quantity' => 1,
                    'unit' => 'lump sum',
                    'unit_price' => $data['amount'] ?? $project->contract_value,
                    'total_amount' => $data['amount'] ?? $project->contract_value,
                ]);
            }

            $invoice->recalculateTotals();

            // Update project status if final invoice
            if ($isFinal && $project->status === 'completed') {
                $project->update(['status' => 'invoicepending']);
            }

            $project->activities()->create([
                'user_id' => $author->id,
                'kind' => 'invoiced',
                'text' => "Tax Invoice {$invoice->invoice_number} raised for {$invoice->grand_total} AED (" . ($isFinal ? 'Final' : 'Progress') . ")",
            ]);

            AuditService::log('created', $invoice, "Created Tax Invoice {$invoice->invoice_number} on project {$project->project_number}");

            \App\Jobs\SyncInvoiceToZohoJob::dispatch($invoice);

            return $invoice;
        });
    }

    /**
     * Record client payment against invoice.
     */
    public function recordPayment(Invoice $invoice, array $data, User $author): InvoicePayment
    {
        return DB::transaction(function () use ($invoice, $data, $author) {
            $payment = $invoice->payments()->create([
                'recorded_by_id' => $author->id,
                'payment_reference' => $data['payment_reference'] ?? ('PAY-' . strtoupper(substr(uniqid(), -6))),
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'amount' => $data['amount'],
                'payment_method' => $data['payment_method'] ?? 'bank_transfer',
                'bank_reference' => $data['bank_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $invoice->recalculateTotals();

            // If project was invoicepending and final invoice is now paid, close project
            $project = $invoice->project;
            if ($invoice->is_final && $invoice->status === 'paid' && in_array($project->status, ['completed', 'invoicepending'])) {
                $project->update([
                    'status' => 'closed',
                    'closed_at' => now(),
                ]);

                $project->activities()->create([
                    'user_id' => $author->id,
                    'kind' => 'closed',
                    'text' => "Final invoice {$invoice->invoice_number} fully settled. Project officially closed.",
                ]);
            }

            $project->activities()->create([
                'user_id' => $author->id,
                'kind' => 'payment',
                'text' => "Payment of {$payment->amount} AED received for Invoice {$invoice->invoice_number}",
            ]);

            AuditService::log('payment_received', $invoice, "Recorded payment {$payment->amount} AED for invoice {$invoice->invoice_number}");

            return $payment;
        });
    }
}
