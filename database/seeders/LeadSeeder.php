<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
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
        $hassan = User::where('email', 'hassan.balooshi@richmount.ae')->first();

        $leadsData = [
            [
                'lead_number' => 'LD-2026-001',
                'lead_date' => '2026-10-10',
                'customer_name' => 'Rashid Al Mansoori',
                'company_name' => 'Al Futtaim Group LLC',
                'rfq_number' => 'RFQ-AFG-9021',
                'subject' => 'Commercial Fitout & MEP Package - Festival City',
                'department' => 'interior',
                'branch_id' => 1, // Dubai
                'assigned_engineer_id' => $aliKhan?->id,
                'created_by_id' => $estimator?->id,
                'source' => 'Website',
                'estimated_value' => 285000.00,
                'status' => 'Contacted',
                'stage' => 'draft',
                'stage_date' => now()->subDays(10),
                'contact_person' => 'Rashid Al Mansoori',
                'phone' => '+971 50 123 4567',
                'email' => 'rashid.m@alfuttaim.com',
                'notes' => 'Client requires turnkey acoustic ceiling, glass partitions, and specialized lighting with LEED compliance.',
            ],
            [
                'lead_number' => 'LD-2026-002',
                'lead_date' => '2026-10-12',
                'customer_name' => 'Vikram Shenoy',
                'company_name' => 'Emaar Properties PJSC',
                'rfq_number' => 'RFQ-EMR-8812',
                'subject' => 'Downtown Boulevard Paving & Structural Reinforcement',
                'department' => 'construction',
                'branch_id' => 1, // Dubai
                'assigned_engineer_id' => $aliKhan?->id,
                'created_by_id' => $superAdmin?->id,
                'source' => 'Referral',
                'estimated_value' => 450000.00,
                'status' => 'Proposal',
                'stage' => 'draft',
                'stage_date' => now()->subDays(8),
                'contact_person' => 'Vikram Shenoy',
                'phone' => '+971 52 345 6789',
                'email' => 'vikram.s@emaar.ae',
                'notes' => 'Heavy duty interlocking pavers and drainage trenching along Boulevard perimeter.',
            ],
            [
                'lead_number' => 'LD-2026-003',
                'lead_date' => '2026-10-14',
                'customer_name' => 'Sarah Al Nuaimi',
                'company_name' => 'Aldar Properties PJSC',
                'rfq_number' => 'RFQ-ALD-3341',
                'subject' => 'Yas Island Retail Expansion - HVAC & Electrical Installation',
                'department' => 'mep',
                'branch_id' => 2, // Abu Dhabi
                'assigned_engineer_id' => $ramesh?->id,
                'created_by_id' => $estimator?->id,
                'source' => 'Exhibition',
                'estimated_value' => 380000.00,
                'status' => 'Proposal',
                'stage' => 'sent',
                'stage_date' => now()->subDays(5),
                'contact_person' => 'Sarah Al Nuaimi',
                'phone' => '+971 54 456 7890',
                'email' => 'snuaimi@aldar.com',
                'notes' => 'Strict Abu Dhabi Civil Defence standards and Estidama 2 Pearl requirements.',
            ],
            [
                'lead_number' => 'LD-2026-004',
                'lead_date' => '2026-10-16',
                'customer_name' => 'Omar Al Qasimi',
                'company_name' => 'Arada Developments',
                'rfq_number' => 'RFQ-ARD-1102',
                'subject' => 'Aljada Community Clubhouse Renovation & Waterproofing',
                'department' => 'renovation',
                'branch_id' => 3, // Sharjah
                'assigned_engineer_id' => $tariq?->id,
                'created_by_id' => $estimator?->id,
                'source' => 'Phone',
                'estimated_value' => 195000.00,
                'status' => 'Won',
                'stage' => 'awarded',
                'stage_date' => now()->subDays(3),
                'contact_person' => 'Omar Al Qasimi',
                'phone' => '+971 56 789 0123',
                'email' => 'omar.q@arada.com',
                'notes' => 'Full membrane waterproofing warranty (10 years) requested with polyurethane topcoat.',
            ],
            [
                'lead_number' => 'LD-2026-005',
                'lead_date' => '2026-10-18',
                'customer_name' => 'Khaled Bin Zayed',
                'company_name' => 'Damac Properties',
                'rfq_number' => 'RFQ-DMC-4450',
                'subject' => 'Damac Hills 2 Golf Club Terrace Extension',
                'department' => 'construction',
                'branch_id' => 1, // Dubai
                'assigned_engineer_id' => $aliKhan?->id,
                'created_by_id' => $superAdmin?->id,
                'source' => 'Website',
                'estimated_value' => 320000.00,
                'status' => 'New',
                'stage' => 'new',
                'stage_date' => now()->subDays(2),
                'contact_person' => 'Khaled Bin Zayed',
                'phone' => '+971 50 998 8776',
                'email' => 'khaled.bz@damacgroup.com',
                'notes' => 'Steel pergola framework with tempered laminated safety glass and deck flooring.',
            ],
            [
                'lead_number' => 'LD-2026-006',
                'lead_date' => '2026-10-20',
                'customer_name' => 'Abdullah Al Marzouqi',
                'company_name' => 'ADNOC Logistics & Services',
                'rfq_number' => 'RFQ-ADN-5561',
                'subject' => 'Mussafah Warehouse Anti-Corrosive Coating & Fire Stopping',
                'department' => 'construction',
                'branch_id' => 2, // Abu Dhabi
                'assigned_engineer_id' => $hassan?->id,
                'created_by_id' => $estimator?->id,
                'source' => 'Email',
                'estimated_value' => 520000.00,
                'status' => 'Negotiation',
                'stage' => 'sent',
                'stage_date' => now()->subDays(1),
                'contact_person' => 'Abdullah Al Marzouqi',
                'phone' => '+971 52 887 7665',
                'email' => 'amarzouqi@adnoc.ae',
                'notes' => 'High chemical resistance epoxy coating for industrial containment zones.',
            ],
            [
                'lead_number' => 'LD-2026-007',
                'lead_date' => '2026-10-22',
                'customer_name' => 'Priya Nair',
                'company_name' => 'Sharjah Asset Management',
                'rfq_number' => 'RFQ-SAM-2021',
                'subject' => 'Souq Al Jubail Cold Storage Thermal Insulation Repair',
                'department' => 'renovation',
                'branch_id' => 3, // Sharjah
                'assigned_engineer_id' => null, // Unassigned to show board
                'created_by_id' => $superAdmin?->id,
                'source' => 'Website',
                'estimated_value' => 145000.00,
                'status' => 'New',
                'stage' => 'new',
                'stage_date' => now(),
                'contact_person' => 'Priya Nair',
                'phone' => '+971 55 667 7889',
                'email' => 'priya.n@sharjahasset.ae',
                'notes' => 'Immediate site inspection requested due to condensation issues in freezer units.',
            ],
            [
                'lead_number' => 'LD-2026-008',
                'lead_date' => '2026-10-24',
                'customer_name' => 'Marcus Weber',
                'company_name' => 'Sobha Realty LLC',
                'rfq_number' => 'RFQ-SBH-7734',
                'subject' => 'Sobha Hartland Villa Podium Waterproofing & Deck Tiles',
                'department' => 'construction',
                'branch_id' => 1, // Dubai
                'assigned_engineer_id' => null, // Unassigned
                'created_by_id' => $estimator?->id,
                'source' => 'Social Media',
                'estimated_value' => 260000.00,
                'status' => 'New',
                'stage' => 'new',
                'stage_date' => now(),
                'contact_person' => 'Marcus Weber',
                'phone' => '+971 50 334 4556',
                'email' => 'marcus.w@sobharealty.com',
                'notes' => 'High end porcelain tiles over acoustic pedestal system.',
            ],
        ];

        foreach ($leadsData as $data) {
            $lead = Lead::create($data);

            // Add activities
            $lead->activities()->create([
                'user_id' => $lead->created_by_id,
                'kind' => 'created',
                'text' => "Lead created from {$lead->source} source",
            ]);

            if ($lead->assigned_engineer_id) {
                $lead->activities()->create([
                    'user_id' => $lead->created_by_id,
                    'kind' => 'assigned',
                    'text' => "Assigned to {$lead->assignedEngineer?->name}",
                ]);
            }
        }
    }
}
