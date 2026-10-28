<?php

namespace App\Services;

use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function createPo(Project $project, array $data, User $author): PurchaseOrder
    {
        return DB::transaction(function () use ($project, $data, $author) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::nextPoNumber(),
                'project_id' => $project->id,
                'branch_id' => $project->branch_id,
                'vendor_name' => $data['vendor_name'],
                'vendor_trn' => $data['vendor_trn'] ?? null,
                'vendor_contact' => $data['vendor_contact'] ?? null,
                'po_date' => $data['po_date'] ?? now()->toDateString(),
                'delivery_date' => $data['delivery_date'] ?? null,
                'vat_rate' => $data['vat_rate'] ?? 0.05,
                'status' => 'approved', // Auto-approved or draft
                'prepared_by_id' => $author->id,
                'approved_by_id' => $author->id,
                'terms' => $data['terms'] ?? '30 days payment upon delivery & site engineer verification.',
                'notes' => $data['notes'] ?? null,
            ]);

            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $index => $item) {
                    $qty = (float) ($item['quantity'] ?? 1);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $po->lineItems()->create([
                        'sort_order' => $index + 1,
                        'description' => $item['description'],
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? 'nos',
                        'unit_price' => $price,
                        'total_amount' => round($qty * $price, 2),
                    ]);
                }
            }

            $po->recalculateTotals();

            $project->activities()->create([
                'user_id' => $author->id,
                'kind' => 'po_created',
                'text' => "Vendor Purchase Order {$po->po_number} created for {$po->vendor_name} ({$po->grand_total} AED)",
            ]);

            AuditService::log('created', $po, "Created Purchase Order {$po->po_number} on project {$project->project_number}");

            return $po;
        });
    }
}
