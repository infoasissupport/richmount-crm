<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $accountant = User::where('role_name', 'accountant')->first();
        $engineers = User::where('role_name', 'engineer')->get();
        $aliKhan = $engineers->get(0) ?? $superAdmin;
        $ramesh = $engineers->get(1) ?? $superAdmin;
        $engineer3 = $engineers->get(2) ?? $superAdmin;

        // 1. Project 1 (Sharjah Waterproofing - inprogress with PO)
        $q4 = Quotation::where('quote_number', 'QT-SHJ-2026-001')->first();
        $lead4 = Lead::where('lead_number', 'LD-2026-004')->first();

        $p1 = Project::create([
            'project_number' => 'PRJ-2026-0001',
            'name' => 'Aljada Community Clubhouse Waterproofing',
            'lead_id' => $lead4?->id,
            'quotation_id' => $q4?->id,
            'branch_id' => 3, // Sharjah
            'engineer_id' => $engineer3?->id,
            'created_by_id' => $accountant?->id,
            'client_name' => 'Arada Developments',
            'client_po_number' => 'ARD-PO-2026-904',
            'client_po_date' => '2026-10-18',
            'client_po_amount' => 199500.00,
            'contract_value' => 199500.00,
            'start_date' => '2026-10-20',
            'expected_end_date' => '2026-12-15',
            'status' => 'inprogress',
            'progress_pct' => 45,
            'description' => 'Two-layer SBS torch-on bituminous membrane waterproofing system (4mm each) complete with protection board and drainage cell.',
        ]);

        $p1->activities()->createMany([
            [
                'user_id' => $accountant?->id,
                'kind' => 'created',
                'text' => 'Project initiated from awarded quotation QT-SHJ-2026-001',
            ],
            [
                'user_id' => $accountant?->id,
                'kind' => 'po_recorded',
                'text' => 'Client PO #ARD-PO-2026-904 recorded for AED 199,500.00. Project is now Open.',
            ],
            [
                'user_id' => $engineer3?->id,
                'kind' => 'status',
                'text' => 'Site mobilized. Surface preparation commenced. Status changed to In Progress.',
            ],
        ]);

        // Add a Vendor Purchase Order to Project 1
        $po1 = $p1->purchaseOrders()->create([
            'po_number' => 'PO-2026-0001',
            'branch_id' => 3,
            'vendor_name' => 'Bitumode Waterproofing Industries LLC',
            'vendor_trn' => '100345678900003',
            'vendor_contact' => 'Ahmed Zaher (+971 6 534 8899)',
            'po_date' => '2026-10-21',
            'delivery_date' => '2026-10-25',
            'vat_rate' => 0.05,
            'status' => 'approved',
            'prepared_by_id' => $engineer3?->id,
            'approved_by_id' => $superAdmin?->id,
            'terms' => '30 days payment from delivery date. Mill test certificate required upon delivery.',
        ]);

        $po1->lineItems()->createMany([
            [
                'sort_order' => 1,
                'description' => 'SBS Bituminous Waterproofing Membrane 4mm (10m x 1m rolls)',
                'quantity' => 260.00,
                'unit' => 'rolls',
                'unit_price' => 140.00,
                'total_amount' => 36400.00,
            ],
            [
                'sort_order' => 2,
                'description' => 'Quick-drying solvent based bituminous primer drums (20L)',
                'quantity' => 25.00,
                'unit' => 'drums',
                'unit_price' => 180.00,
                'total_amount' => 4500.00,
            ],
        ]);
        $po1->recalculateTotals();

        // 2. Project 2 (Abu Dhabi MEP - PO Pending)
        $q3 = Quotation::where('quote_number', 'QT-AUH-2026-001')->first();
        $lead3 = Lead::where('lead_number', 'LD-2026-003')->first();

        $p2 = Project::create([
            'project_number' => 'PRJ-2026-0002',
            'name' => 'Yas Island Retail Expansion - HVAC Package',
            'lead_id' => $lead3?->id,
            'quotation_id' => $q3?->id,
            'branch_id' => 2, // Abu Dhabi
            'engineer_id' => $ramesh?->id,
            'created_by_id' => $accountant?->id,
            'client_name' => 'Aldar Properties PJSC',
            'contract_value' => 378787.50,
            'status' => 'popending', // Waiting for Client PO
            'description' => 'Estidama compliant VRF system installation.',
        ]);

        $p2->activities()->create([
            'user_id' => $accountant?->id,
            'kind' => 'created',
            'text' => 'Project initiated by Meera Joseph. Waiting for formal client LPO.',
        ]);

        // 3. Project 3 (Dubai Fitout - Completed with WCC)
        $p3 = Project::create([
            'project_number' => 'PRJ-2026-0003',
            'name' => 'Dubai Hills Corporate Office Fitout',
            'branch_id' => 1, // Dubai
            'engineer_id' => $aliKhan?->id,
            'created_by_id' => $superAdmin?->id,
            'client_name' => 'Emaar Properties PJSC',
            'client_po_number' => 'EMR-PO-8812',
            'client_po_date' => '2026-09-01',
            'client_po_amount' => 245000.00,
            'contract_value' => 245000.00,
            'start_date' => '2026-09-05',
            'expected_end_date' => '2026-10-15',
            'actual_end_date' => '2026-10-16',
            'status' => 'completed',
            'progress_pct' => 100,
            'description' => 'Turnkey interior acoustic partitioning and glass sliding door systems.',
            'completion_report' => 'Site inspection conducted with Emaar engineering consultants. Snags cleared and final handover accepted.',
        ]);

        $p3->workCompletionCertificates()->create([
            'certificate_number' => 'WCC-2026-0001',
            'certificate_date' => '2026-10-16',
            'completion_date' => '2026-10-16',
            'work_description' => 'Acoustic wall linings, ceiling grids, and tempered glass partitions installed and certified.',
            'retention_pct' => 10.00,
            'retention_amount' => 24500.00,
            'engineer_id' => $aliKhan?->id,
            'client_representative_name' => 'Eng. Fahad Al Qubaisi',
            'client_representative_title' => 'Senior Project Consultant',
            'client_signed_at' => '2026-10-16 14:30:00',
            'status' => 'signed',
        ]);

        $p3->activities()->create([
            'user_id' => $aliKhan?->id,
            'kind' => 'completed',
            'text' => 'Work Completion Certificate WCC-2026-0001 signed by Eng. Fahad Al Qubaisi. Ready for final invoice.',
        ]);
    }
}
