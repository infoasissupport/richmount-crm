<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolesAndPermissionsSpecTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $estimationUser;
    protected User $engineer;
    protected User $accountant;
    protected Branch $branch1;
    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->branch1 = Branch::first();
        $this->branch2 = Branch::skip(1)->first() ?? Branch::create([
            'name' => 'Abu Dhabi Branch',
            'code' => 'AUH',
            'country' => 'UAE',
            'currency' => 'AED',
            'is_active' => true,
        ]);

        $this->superAdmin = User::where('role_name', 'super_admin')->first();

        $this->estimationUser = User::firstOrCreate(
            ['email' => 'estimator.test@richmount.ae'],
            [
                'name' => 'Test Estimator',
                'password' => bcrypt('password'),
                'branch_id' => $this->branch1->id,
                'role_name' => 'estimation',
                'all_branches' => false,
                'is_active' => true,
            ]
        );
        $this->estimationUser->assignRole('estimation');
        $this->estimationUser->update(['branch_id' => $this->branch1->id, 'all_branches' => false]);

        $this->engineer = User::where('role_name', 'engineer')->first();
        $this->engineer->update(['branch_id' => $this->branch1->id, 'all_branches' => false]);

        $this->accountant = User::where('role_name', 'accountant')->first();
    }

    /**
     * Role 1: Super Admin has full access to everything.
     */
    public function test_super_admin_has_full_access_to_settings_users_branches_zoho(): void
    {
        $this->actingAs($this->superAdmin);

        $this->get('/settings')->assertOk();
        $this->post('/settings/approver', ['quote_approver' => 'admin'])->assertRedirect();

        $this->get('/users')->assertOk();
        $this->get('/branches')->assertOk();
        $this->get('/zoho')->assertOk();
        $this->get('/finance')->assertOk();
        $this->get('/reports')->assertOk();
    }

    /**
     * Role 2: Estimation User - lead & quote management, branch-restricted.
     */
    public function test_estimation_user_is_branch_restricted_for_leads_and_quotes(): void
    {
        $this->actingAs($this->estimationUser);

        // Can access leads in own branch
        $ownLead = Lead::create([
            'lead_number' => Lead::nextLeadNumber(),
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Branch 1 Customer',
            'company_name' => 'Branch 1 Corp',
            'subject' => 'Interior Fitout',
            'department' => 'interior',
            'branch_id' => $this->branch1->id,
            'created_by_id' => $this->estimationUser->id,
            'source' => 'Direct',
            'status' => 'New',
            'stage' => 'new',
        ]);
        $this->get("/leads/{$ownLead->id}")->assertOk();

        // Cannot view or access lead in another branch
        $otherLead = Lead::create([
            'lead_number' => 'LD-' . date('Y') . '-9999',
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Branch 2 Customer',
            'company_name' => 'Branch 2 Corp',
            'subject' => 'Branch 2 Scope',
            'department' => 'interior',
            'branch_id' => $this->branch2->id,
            'created_by_id' => $this->superAdmin->id,
            'source' => 'Direct',
            'status' => 'New',
            'stage' => 'new',
        ]);
        $this->get("/leads/{$otherLead->id}")->assertForbidden();

        // Can manage quotes in own branch
        $ownQuote = Quotation::create([
            'quote_number' => Quotation::nextQuoteNumber(),
            'lead_id' => $ownLead->id,
            'branch_id' => $this->branch1->id,
            'prepared_by_id' => $this->estimationUser->id,
            'status' => 'draft',
            'quote_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'vat_amount' => 50,
            'grand_total' => 1050,
        ]);
        $this->get("/quotations/builder?id={$ownQuote->id}")->assertOk();

        // Cannot manage or view quote in another branch
        $otherQuote = Quotation::create([
            'quote_number' => 'RM-QT-' . date('Y') . '-9999',
            'lead_id' => $otherLead->id,
            'branch_id' => $this->branch2->id,
            'prepared_by_id' => $this->superAdmin->id,
            'status' => 'draft',
            'quote_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 2000,
            'vat_amount' => 100,
            'grand_total' => 2100,
        ]);
        $this->get("/quotations/builder?id={$otherQuote->id}")->assertForbidden();

        // Estimation User cannot access settings, users, zoho, or finance approval center
        $this->get('/settings')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/zoho')->assertForbidden();
        $this->get('/finance')->assertForbidden();
    }

    /**
     * Role 3: Project Engineer - assigned leads, quote preparation, Expense Module.
     */
    public function test_project_engineer_access_boundaries(): void
    {
        $this->actingAs($this->engineer);

        // 1. Leads: only assigned leads
        $assignedLead = Lead::create([
            'lead_number' => Lead::nextLeadNumber(),
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Assigned Client',
            'company_name' => 'Assigned Co',
            'subject' => 'Acoustic Ceiling',
            'department' => 'interior',
            'branch_id' => $this->branch1->id,
            'assigned_engineer_id' => $this->engineer->id,
            'created_by_id' => $this->superAdmin->id,
            'source' => 'Direct',
            'status' => 'In Progress',
            'stage' => 'draft',
        ]);
        $this->get("/leads/{$assignedLead->id}")->assertOk();

        $unassignedLead = Lead::create([
            'lead_number' => 'LD-' . date('Y') . '-8888',
            'lead_date' => now()->toDateString(),
            'customer_name' => 'Unassigned Client',
            'company_name' => 'Unassigned Co',
            'subject' => 'Glass Partitions',
            'department' => 'interior',
            'branch_id' => $this->branch1->id,
            'assigned_engineer_id' => null,
            'created_by_id' => $this->superAdmin->id,
            'source' => 'Direct',
            'status' => 'New',
            'stage' => 'new',
        ]);
        $this->get("/leads/{$unassignedLead->id}")->assertForbidden();

        // 2. Quotes: quote preparation (create, submit), but NOT approve/send/award
        $quote = Quotation::create([
            'quote_number' => Quotation::nextQuoteNumber(),
            'lead_id' => $assignedLead->id,
            'branch_id' => $this->branch1->id,
            'prepared_by_id' => $this->engineer->id,
            'status' => 'draft',
            'quote_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000,
            'vat_amount' => 50,
            'grand_total' => 1050,
        ]);
        // Can edit draft quote prepared by self
        $this->get("/quotations/builder?id={$quote->id}")->assertOk();

        // Cannot approve quote
        $quote->update(['status' => 'pending']);
        $this->post("/quotations/{$quote->id}/approve")->assertForbidden();

        // Cannot send quote
        $quote->update(['status' => 'approved']);
        $this->post("/quotations/{$quote->id}/send")->assertForbidden();

        // Cannot award quote
        $this->post("/quotations/{$quote->id}/award")->assertForbidden();

        // 3. Expense module: can access expenses
        $this->get('/expenses')->assertOk();

        // Cannot approve expenses or access Finance approval center
        $this->get('/finance')->assertForbidden();

        // Cannot access Settings, Users, Branches, Zoho
        $this->get('/settings')->assertForbidden();
        $this->get('/users')->assertForbidden();
        $this->get('/zoho')->assertForbidden();
    }

    /**
     * Role 4: Accountant - transaction approval, Zoho Books sync, project activation, invoicing, reports.
     */
    public function test_accountant_access_boundaries(): void
    {
        $this->actingAs($this->accountant);

        // 1. Transaction Approval
        $this->get('/finance')->assertOk();

        $pendingExpense = Expense::create([
            'expense_number' => Expense::nextExpenseNumber(),
            'branch_id' => $this->branch1->id,
            'user_id' => $this->engineer->id,
            'category' => 'Materials',
            'type' => 'card',
            'date' => now()->toDateString(),
            'status' => 'pending',
            'subtotal' => 150.00,
            'vat_amount' => 7.50,
            'grand_total' => 157.50,
        ]);
        $this->post("/finance/expenses/{$pendingExpense->id}/approve")->assertRedirect();
        $this->assertEquals('approved', $pendingExpense->fresh()->status);

        // 2. Zoho Books Sync
        $this->get('/zoho')->assertOk();

        // 3. Project Activation (open project from popending status)
        $lead = Lead::first();
        $quote = Quotation::first();
        $project = Project::create([
            'project_number' => Project::nextProjectNumber(),
            'name' => 'PO Test Project',
            'client_name' => 'Test Client Corp',
            'lead_id' => $lead->id,
            'quotation_id' => $quote->id,
            'branch_id' => $this->branch1->id,
            'engineer_id' => $this->engineer->id,
            'status' => 'popending',
            'contract_value' => 50000.00,
            'start_date' => now()->toDateString(),
        ]);
        $this->post("/projects/{$project->id}/po", [
            'client_po_number' => 'PO-TEST-1234',
            'client_po_date' => now()->toDateString(),
            'notes' => 'Client PO received by accountant',
        ])->assertRedirect();
        $this->assertEquals('open', $project->fresh()->status);

        // 4. Invoicing
        $this->post("/projects/{$project->id}/invoices", [
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => 1000.00,
            'vat_amount' => 50.00,
            'total_amount' => 1050.00,
        ])->assertRedirect();

        // 5. Reports
        $this->get('/reports')->assertOk();
        $this->get('/reports/export?type=profitability')->assertOk();

        // Accountant cross-branch access
        $this->post(route('branches.switch'), ['branch_id' => 'all'])->assertRedirect();
        $this->assertEquals('all', session('active_branch_id'));

        // Accountant cannot manage users or settings
        $this->get('/users')->assertForbidden();
        $this->get('/settings')->assertForbidden();
    }
}
