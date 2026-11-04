<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_project_financial_report_lists_projects_with_view_details_action(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $branch = Branch::firstOrFail();

        $project = Project::create([
            'project_number' => 'PRJ-REPORT-001',
            'name' => 'Financial Report Project',
            'branch_id' => $branch->id,
            'client_name' => 'Alpha Client',
            'contract_value' => 20000,
            'status' => 'open',
        ]);

        $this->actingAs($accountant);

        $response = $this->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Project Job-Costing & Profit Margin Analysis');
        $response->assertSee(route('reports.project', $project), false);
        $response->assertSee('View Details', false);
    }

    public function test_profitability_report_paginates_projects_without_changing_summary_totals(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $branch = Branch::firstOrFail();

        foreach (range(1, 11) as $index) {
            Project::create([
                'project_number' => sprintf('PRJ-PAGE-%02d', $index),
                'name' => sprintf('Pagination Regression Project %02d', $index),
                'branch_id' => $branch->id,
                'client_name' => 'Pagination Regression Client',
                'contract_value' => 100,
                'status' => 'open',
            ]);
        }

        $this->actingAs($accountant);

        $response = $this->get(route('reports.index', [
            'search' => 'Pagination Regression',
            'projects_page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('11 projects');
        $response->assertSee('projects_page=2', false);
        $response->assertViewHas('projectRows', fn ($projectRows) =>
            $projectRows->total() === 11
            && $projectRows->currentPage() === 2
            && $projectRows->count() === 1
        );
    }

    public function test_engineer_cash_ledgers_paginate_history_independently(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $engineer = User::where('role_name', 'engineer')->firstOrFail();
        $branch = Branch::firstOrFail();

        foreach (range(1, 11) as $index) {
            Expense::create([
                'expense_number' => sprintf('EXP-CASH-PAGE-%02d', $index),
                'branch_id' => $branch->id,
                'user_id' => $engineer->id,
                'category' => 'materials',
                'vendor_name' => 'Cash Pagination Supplier',
                'date' => now()->subDays($index),
                'type' => 'cash',
                'subtotal' => 50,
                'vat_rate' => 0.05,
                'vat_amount' => 2.5,
                'grand_total' => 52.5,
                'description' => 'Cash pagination expense',
                'status' => 'approved',
            ]);
        }

        $pageName = 'cash_page_' . $engineer->id;
        $this->actingAs($accountant);

        $response = $this->get(route('reports.index', [
            'type' => 'cash',
            $pageName => 2,
        ]));

        $response->assertOk();
        $response->assertSee($pageName . '=2', false);
        $response->assertViewHas('data', function ($ledgers) use ($engineer): bool {
            $ledger = $ledgers->first(fn (array $ledger): bool => $ledger['engineer']->id === $engineer->id);

            return $ledger !== null
                && $ledger['history']->total() >= 11
                && $ledger['history']->currentPage() === 2;
        });
    }

    public function test_project_detail_report_displays_financial_summary_and_transactions(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $branch = Branch::firstOrFail();
        $user = User::where('role_name', 'engineer')->firstOrFail();

        $project = Project::create([
            'project_number' => 'PRJ-DETAIL-001',
            'name' => 'Detailed Project',
            'branch_id' => $branch->id,
            'engineer_id' => $user->id,
            'client_name' => 'Beta Client',
            'contract_value' => 10000,
            'status' => 'inprogress',
        ]);

        $invoice = Invoice::create([
            'invoice_number' => 'INV-2026-0001',
            'project_id' => $project->id,
            'branch_id' => $branch->id,
            'created_by_id' => $accountant->id,
            'client_name' => 'Beta Client',
            'invoice_date' => now()->subDays(5),
            'subtotal' => 8000,
            'vat_rate' => 0.05,
            'vat_amount' => 400,
            'grand_total' => 8400,
            'status' => 'paid',
        ]);

        InvoicePayment::create([
            'invoice_id' => $invoice->id,
            'recorded_by_id' => $accountant->id,
            'payment_reference' => 'REF-100',
            'payment_date' => now()->subDays(4),
            'amount' => 8400,
            'payment_method' => 'bank_transfer',
        ]);

        Expense::create([
            'expense_number' => 'EXP-2026-0001',
            'project_id' => $project->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'category' => 'materials',
            'vendor_name' => 'BuildMart',
            'date' => now()->subDays(2),
            'type' => 'cash',
            'subtotal' => 3000,
            'vat_rate' => 0.05,
            'vat_amount' => 150,
            'grand_total' => 3150,
            'description' => 'Concrete and cement',
            'status' => 'approved',
        ]);

        $this->actingAs($accountant);

        $response = $this->get(route('reports.project', $project));

        $response->assertOk();
        $response->assertSee('Project Financial Overview');
        $response->assertSee('Contract Amount');
        $response->assertSee('Total Invoiced');
        $response->assertSee('Total Received');
        $response->assertSee('Total Expenses');
        $response->assertSee('Outstanding Amount');
        $response->assertSee('Net Profit');
        $response->assertSee('Profit Margin');
        $response->assertSee('Project Income Details');
        $response->assertSee('Project Expense Details');
    }

    public function test_project_detail_report_paginates_income_and_expenses_independently(): void
    {
        $accountant = User::where('role_name', 'accountant')->firstOrFail();
        $branch = Branch::firstOrFail();
        $user = User::where('role_name', 'engineer')->firstOrFail();
        $project = Project::create([
            'project_number' => 'PRJ-PAGES-001',
            'name' => 'Paginated Project',
            'branch_id' => $branch->id,
            'engineer_id' => $user->id,
            'client_name' => 'Pagination Client',
            'contract_value' => 10000,
            'status' => 'inprogress',
        ]);

        foreach (range(1, 11) as $index) {
            Invoice::create([
                'invoice_number' => sprintf('INV-PAGE-%02d', $index),
                'project_id' => $project->id,
                'branch_id' => $branch->id,
                'created_by_id' => $accountant->id,
                'client_name' => 'Pagination Client',
                'invoice_date' => now()->subDays($index),
                'subtotal' => 100,
                'vat_rate' => 0.05,
                'vat_amount' => 5,
                'grand_total' => 105,
                'status' => 'sent',
            ]);

            Expense::create([
                'expense_number' => sprintf('EXP-PAGE-%02d', $index),
                'project_id' => $project->id,
                'branch_id' => $branch->id,
                'user_id' => $user->id,
                'category' => 'materials',
                'vendor_name' => 'Pagination Supplier',
                'date' => now()->subDays($index),
                'type' => 'cash',
                'subtotal' => 50,
                'vat_rate' => 0.05,
                'vat_amount' => 2.5,
                'grand_total' => 52.5,
                'description' => 'Pagination expense',
                'status' => 'approved',
            ]);
        }

        $this->actingAs($accountant);

        $response = $this->get(route('reports.project', [
            'project' => $project,
            'income_page' => 2,
            'expense_page' => 2,
        ]));

        $response->assertOk();
        $response->assertSee('INV-PAGE-11');
        $response->assertDontSee('INV-PAGE-01');
        $response->assertSee('EXP-PAGE-11');
        $response->assertDontSee('EXP-PAGE-01');
        $response->assertSee('income_page=2', false);
        $response->assertSee('expense_page=2', false);
    }
}
