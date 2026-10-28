<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuotationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('role_name', 'super_admin')->first();
        $estimator = User::where('role_name', 'estimation')->first();
        $aliKhan = User::where('email', 'ali.khan@richmount.ae')->first();
        $ramesh = User::where('email', 'ramesh.nair@richmount.ae')->first();
        $tariq = User::where('email', 'tariq.mahmoud@richmount.ae')->first();

        // 1. Dubai Lead 1 Quotation (Draft) - Format 1
        $lead1 = Lead::where('lead_number', 'LD-2026-001')->first();
        if ($lead1) {
            $q1 = Quotation::create([
                'quote_number' => 'QT-DXB-2026-001',
                'lead_id' => $lead1->id,
                'branch_id' => 1, // Dubai
                'prepared_by_id' => $estimator?->id ?? 1,
                'quote_date' => '2026-10-11',
                'validity_days' => 30,
                'output_type' => 'tax',
                'status' => 'draft',
                'subtotal' => 280000.00,
                'discount_type' => 'amt',
                'discount_value' => 5000.00,
                'discount_amount' => 5000.00,
                'taxable_amount' => 275000.00,
                'vat_rate' => 0.05,
                'vat_amount' => 13750.00,
                'grand_total' => 288750.00,
                'technical_description' => "Supply and installation of gypsum board acoustic partitions with 50mm rockwool insulation, acoustic sealants, and Saint-Gobain finishing materials.",
                'terms_conditions' => "1. 30% advance with LPO, 60% running progressive invoices, 10% on handover.\n2. Validity: 30 days from issuance.\n3. Site electricity and crane access by client.",
                'internal_notes' => 'Negotiation expected around 3% additional commercial rebate.',
                'auto_saved_at' => now(),
            ]);

            $q1->lineItems()->createMany([
                [
                    'sort_order' => 1,
                    'description' => 'Gypsum acoustic dry lining partition (100mm system, double layer gypsum board with 50kg/m³ rockwool)',
                    'quantity' => 1200.00,
                    'unit' => 'm²',
                    'unit_price' => 110.00,
                    'total_amount' => 132000.00,
                ],
                [
                    'sort_order' => 2,
                    'description' => 'Acoustic modular ceiling tiles 600x600mm including concealed suspension grid & perimeter trims',
                    'quantity' => 850.00,
                    'unit' => 'm²',
                    'unit_price' => 95.00,
                    'total_amount' => 80750.00,
                ],
                [
                    'sort_order' => 3,
                    'description' => 'Frameless 12mm clear tempered glass doors with Dorma hardware and floor springs',
                    'quantity' => 18.00,
                    'unit' => 'nos',
                    'unit_price' => 3736.11,
                    'total_amount' => 67250.00,
                ],
            ]);

            $q1->histories()->create([
                'user_id' => $estimator?->id,
                'action' => 'created',
                'text' => 'Draft quotation created',
            ]);
        }

        // 2. Dubai Lead 2 Quotation (Pending Approval) - Format 1
        $lead2 = Lead::where('lead_number', 'LD-2026-002')->first();
        if ($lead2) {
            $q2 = Quotation::create([
                'quote_number' => 'QT-DXB-2026-002',
                'lead_id' => $lead2->id,
                'branch_id' => 1,
                'prepared_by_id' => $estimator?->id ?? 1,
                'quote_date' => '2026-10-13',
                'validity_days' => 45,
                'output_type' => 'tax',
                'status' => 'pending',
                'subtotal' => 420000.00,
                'discount_type' => 'amt',
                'discount_value' => 0.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 420000.00,
                'vat_rate' => 0.05,
                'vat_amount' => 21000.00,
                'grand_total' => 441000.00,
                'technical_description' => "Interlocking concrete block paving (80mm heavy duty) over compacted sub-base and geotextile separator membrane.",
                'terms_conditions' => "Payment terms: 20% advance upon contract signing, monthly interim payments subject to site measurements.",
                'internal_notes' => 'Submitted to Super Admin for margin approval.',
                'auto_saved_at' => now(),
            ]);

            $q2->lineItems()->createMany([
                [
                    'sort_order' => 1,
                    'description' => 'Excavation and preparation of subgrade up to 300mm depth including disposal off site',
                    'quantity' => 3500.00,
                    'unit' => 'm²',
                    'unit_price' => 35.00,
                    'total_amount' => 122500.00,
                ],
                [
                    'sort_order' => 2,
                    'description' => 'Heavy duty 80mm shot-blasted concrete interlock pavers on 50mm sharp sand bedding',
                    'quantity' => 3500.00,
                    'unit' => 'm²',
                    'unit_price' => 85.00,
                    'total_amount' => 297500.00,
                ],
            ]);

            $q2->histories()->create([
                'user_id' => $estimator?->id,
                'action' => 'submitted',
                'text' => 'Submitted quotation for Super Admin approval',
            ]);
        }

        // 3. Abu Dhabi Lead 3 Quotation (Sent to Customer) - Format 2 (Technical/Estidama format)
        $lead3 = Lead::where('lead_number', 'LD-2026-003')->first();
        if ($lead3) {
            $q3 = Quotation::create([
                'quote_number' => 'QT-AUH-2026-001',
                'lead_id' => $lead3->id,
                'branch_id' => 2, // Abu Dhabi
                'prepared_by_id' => $estimator?->id ?? 1,
                'approved_by_id' => $superAdmin?->id,
                'quote_date' => '2026-10-15',
                'validity_days' => 60,
                'output_type' => 'tax',
                'status' => 'sent',
                'subtotal' => 370000.00,
                'discount_type' => 'pct',
                'discount_value' => 2.50,
                'discount_amount' => 9250.00,
                'taxable_amount' => 360750.00,
                'vat_rate' => 0.05,
                'vat_amount' => 18037.50,
                'grand_total' => 378787.50,
                'technical_description' => "Estidama compliant variable refrigerant flow (VRF) air conditioning system and low smoke zero halogen (LSZH) electrical distribution.",
                'terms_conditions' => "Abu Dhabi Civil Defence approved equipment only. Testing & commissioning witnessed by third-party consultant included.",
                'internal_notes' => 'Sent to Aldar procurement team after Aisha and John reviewed.',
                'auto_saved_at' => now(),
            ]);

            $q3->lineItems()->createMany([
                [
                    'sort_order' => 1,
                    'description' => 'Daikin VRF Outdoor unit with heat pump system 24 HP inverter technology',
                    'quantity' => 3.00,
                    'unit' => 'nos',
                    'unit_price' => 45000.00,
                    'total_amount' => 135000.00,
                ],
                [
                    'sort_order' => 2,
                    'description' => 'VRF indoor 4-way cassette units with wired remote controllers and secondary drain pumps',
                    'quantity' => 22.00,
                    'unit' => 'nos',
                    'unit_price' => 6500.00,
                    'total_amount' => 143000.00,
                ],
                [
                    'sort_order' => 3,
                    'description' => 'Pre-insulated aluminium ducting, copper refrigerant piping, and electrical cabling package',
                    'quantity' => 1.00,
                    'unit' => 'lump sum',
                    'unit_price' => 92000.00,
                    'total_amount' => 92000.00,
                ],
            ]);

            $q3->histories()->create([
                'user_id' => $superAdmin?->id,
                'action' => 'approved',
                'text' => 'Quotation approved by John Doe',
            ]);
            $q3->histories()->create([
                'user_id' => $estimator?->id,
                'action' => 'sent',
                'text' => 'Official quotation PDF sent to customer (Sarah Al Nuaimi)',
            ]);
        }

        // 4. Sharjah Lead 4 Quotation (Awarded) - Format 3 (Milestone format)
        $lead4 = Lead::where('lead_number', 'LD-2026-004')->first();
        if ($lead4) {
            $q4 = Quotation::create([
                'quote_number' => 'QT-SHJ-2026-001',
                'lead_id' => $lead4->id,
                'branch_id' => 3, // Sharjah
                'prepared_by_id' => $estimator?->id ?? 1,
                'approved_by_id' => $superAdmin?->id,
                'quote_date' => '2026-10-17',
                'validity_days' => 30,
                'output_type' => 'tax',
                'status' => 'awarded',
                'subtotal' => 190000.00,
                'discount_type' => 'amt',
                'discount_value' => 0.00,
                'discount_amount' => 0.00,
                'taxable_amount' => 190000.00,
                'vat_rate' => 0.05,
                'vat_amount' => 9500.00,
                'grand_total' => 199500.00,
                'technical_description' => "Two-layer SBS torch-on bituminous membrane waterproofing system (4mm each) complete with protection board and drainage cell.",
                'terms_conditions' => "Milestones:\n1. 25% Advance payment\n2. 50% On completion of surface prep & membrane application\n3. 25% On flood test sign-off & warranty issuance",
                'internal_notes' => 'Awarded via PO #ARD-PO-2026-904. Ready for Project initiation.',
                'award_date' => '2026-10-24',
                'auto_saved_at' => now(),
            ]);

            $q4->lineItems()->createMany([
                [
                    'sort_order' => 1,
                    'description' => 'Surface preparation, shot blasting, and polyurethane primer application',
                    'quantity' => 2400.00,
                    'unit' => 'm²',
                    'unit_price' => 18.00,
                    'total_amount' => 43200.00,
                ],
                [
                    'sort_order' => 2,
                    'description' => 'Two layers of 4mm SBS modified bituminous membrane with polyester reinforcement',
                    'quantity' => 2400.00,
                    'unit' => 'm²',
                    'unit_price' => 48.00,
                    'total_amount' => 115200.00,
                ],
                [
                    'sort_order' => 3,
                    'description' => '3.2mm asphaltic protection board and 24-hour flood testing protocol with consultant signoff',
                    'quantity' => 2400.00,
                    'unit' => 'm²',
                    'unit_price' => 13.167,
                    'total_amount' => 31600.00,
                ],
            ]);

            $q4->histories()->create([
                'user_id' => $superAdmin?->id,
                'action' => 'awarded',
                'text' => 'Customer confirmed award and issued LPO',
            ]);
        }
    }
}
